import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { ClipboardList, FileText, Filter, RotateCcw, ShieldCheck } from 'lucide-react';
import { getApplicationStatuses, listGroupApplications } from '@/api/admin-applications';
import { Badge, Button, EmptyState, Pagination, SearchInput, Select, Skeleton, statusTone, TableShell, Td, Th } from '@/components/ui';
import { TenantPage } from '@/components/layout';
import { formatDate } from '@/lib/format';

const fallbackStatuses = [
    'pending_review',
    'under_review',
    'requires_resubmission',
    'ready_for_review',
    'approved',
    'rejected',
];

export function AdminApplicationsPage() {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const statuses = useQuery({ queryKey: ['admin', 'application-statuses'], queryFn: getApplicationStatuses });
    const applications = useQuery({
        queryKey: ['admin', 'applications', search, status, page],
        queryFn: () => listGroupApplications({ search: search || undefined, status: status || undefined, page, per_page: 10 }),
    });

    const statusOptions = statuses.data?.length
        ? statuses.data
        : fallbackStatuses.map((value) => ({ value, label: t(`status.${value}`, { defaultValue: value.replaceAll('_', ' ') }) }));

    const resetFilters = () => {
        setSearch('');
        setStatus('');
        setPage(1);
    };

    return (
        <TenantPage
            title={t('adminApplications.title')}
            subtitle={t('adminApplications.subtitle')}
            actions={<Badge tone="info"><ClipboardList size={13} /> {t('nav.applications')}</Badge>}
        >
            <div className="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex w-full flex-col gap-2 sm:flex-row lg:max-w-2xl">
                    <div className="w-full sm:max-w-md">
                        <SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('adminApplications.searchPlaceholder')} />
                    </div>
                    <div className="flex items-center gap-2">
                        <Filter size={15} className="hidden text-ink-400 sm:block" />
                        <Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} className="min-w-40">
                            <option value="">{t('adminApplications.allStatuses')}</option>
                            {statusOptions.map((option) => (
                                <option key={option.value} value={option.value}>{t(`status.${option.value}`, { defaultValue: option.label })}</option>
                            ))}
                        </Select>
                    </div>
                </div>
                {search || status ? (
                    <Button variant="ghost" size="sm" onClick={resetFilters}><RotateCcw size={14} /> {t('common.clearFilters')}</Button>
                ) : null}
            </div>

            {applications.isLoading ? (
                <div className="space-y-3">{[1, 2, 3, 4, 5].map((item) => <Skeleton key={item} className="h-20" />)}</div>
            ) : applications.data && applications.data.data.length > 0 ? (
                <>
                    <TableShell>
                        <table className="min-w-full">
                            <thead className="bg-ink-50">
                                <tr>
                                    <Th>{t('adminApplications.reference')}</Th>
                                    <Th>{t('adminApplications.group')}</Th>
                                    <Th>{t('adminApplications.owner')}</Th>
                                    <Th>{t('adminApplications.submitted')}</Th>
                                    <Th>{t('adminApplications.documents')}</Th>
                                    <Th>{t('common.status')}</Th>
                                    <Th className="text-end">{t('common.actions')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {applications.data.data.map((application) => (
                                    <tr key={application.id} className="hover:bg-ink-50/60">
                                        <Td>
                                            <span className="font-mono text-xs font-semibold text-ink-700">{application.reference}</span>
                                        </Td>
                                        <Td>
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                                    <ShieldCheck size={16} />
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate font-semibold text-ink-900">{application.group_name ?? '—'}</p>
                                                    <p className="truncate text-xs text-ink-500">{application.subdomain}.theaterlink</p>
                                                </div>
                                            </div>
                                        </Td>
                                        <Td>
                                            <p className="text-sm text-ink-800">{application.owner?.name ?? '—'}</p>
                                            <p className="text-xs text-ink-500">{application.owner?.email}</p>
                                        </Td>
                                        <Td className="whitespace-nowrap text-xs text-ink-500">{formatDate(application.submitted_at)}</Td>
                                        <Td>
                                            <div className="flex items-center gap-2 text-sm text-ink-700">
                                                <FileText size={14} className="text-ink-400" />
                                                {t('adminApplications.documentsProgress', {
                                                    accepted: application.documents_accepted,
                                                    total: application.documents_count,
                                                })}
                                            </div>
                                        </Td>
                                        <Td>
                                            <Badge tone={statusTone(application.status)}>
                                                {t(`status.${application.status}`, { defaultValue: application.status.replaceAll('_', ' ') })}
                                            </Badge>
                                        </Td>
                                        <Td>
                                            <div className="flex justify-end">
                                                <Link to={`/admin/applications/${application.id}`} className="btn-secondary btn-sm">
                                                    {t('adminApplications.view')}
                                                </Link>
                                            </div>
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </TableShell>
                    <div className="mt-4">
                        <Pagination
                            page={applications.data.pagination.current_page}
                            lastPage={applications.data.pagination.last_page}
                            total={applications.data.pagination.total}
                            onChange={setPage}
                            labels={{
                                previous: t('common.previous'),
                                next: t('common.next'),
                                showing: t('common.showing'),
                                results: t('common.results'),
                                page: t('common.page'),
                                of: t('common.of'),
                            }}
                        />
                    </div>
                </>
            ) : (
                <EmptyState title={t('adminApplications.empty')} icon={<ClipboardList size={22} />} />
            )}
        </TenantPage>
    );
}
