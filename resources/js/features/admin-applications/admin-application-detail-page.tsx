import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import {
    AlertTriangle,
    CheckCircle2,
    Clock3,
    Download,
    FileText,
    History,
    Mail,
    Phone,
    RotateCcw,
    ShieldCheck,
    UserRound,
    XCircle,
} from 'lucide-react';
import {
    acceptGroupDocument,
    addGroupApplicationNote,
    approveGroupApplication,
    downloadGroupDocument,
    getGroupApplication,
    reactivateGroupApplication,
    rejectGroupApplication,
    rejectGroupDocument,
    reopenGroupApplication,
    requestGroupDocumentResubmission,
    suspendGroupApplication,
} from '@/api/admin-applications';
import type { AdminGroupDocument } from '@/api/types';
import {
    Alert,
    Badge,
    Button,
    ConfirmDialog,
    EmptyState,
    Field,
    FormError,
    Modal,
    Skeleton,
    statusTone,
    TableShell,
    Td,
    Th,
    Textarea,
} from '@/components/ui';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDate, formatDateTime, humanize } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

type ReasonDialog =
    | { type: 'reject-document' | 'resubmission'; document: AdminGroupDocument }
    | { type: 'reject-application' | 'suspend' }
    | null;

type ConfirmAction = 'approve' | 'reopen' | 'reactivate' | null;

interface PreviewState {
    documentId: number;
    name: string;
    url: string;
    isImage: boolean;
    isPdf: boolean;
}

function triggerDownload(blob: Blob, name: string) {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = name;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}

export function AdminApplicationDetailPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const { id } = useParams();
    const applicationId = Number(id);
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const canUpdate = session?.permissions.includes('group_applications-update') ?? false;
    const [dialog, setDialog] = useState<ReasonDialog>(null);
    const [confirmAction, setConfirmAction] = useState<ConfirmAction>(null);
    const [reason, setReason] = useState('');
    const [feedback, setFeedback] = useState('');
    const [note, setNote] = useState('');
    const [busy, setBusy] = useState(false);
    const [preview, setPreview] = useState<PreviewState | null>(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [previewError, setPreviewError] = useState<string | null>(null);

    const application = useQuery({
        queryKey: ['admin', 'application', applicationId],
        queryFn: () => getGroupApplication(applicationId),
        enabled: Number.isFinite(applicationId),
    });

    useEffect(() => {
        return () => {
            if (preview) {
                URL.revokeObjectURL(preview.url);
            }
        };
    }, [preview]);

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['admin', 'application', applicationId] });
        void queryClient.invalidateQueries({ queryKey: ['admin', 'applications'] });
        void queryClient.invalidateQueries({ queryKey: ['admin', 'dashboard'] });
        void queryClient.invalidateQueries({ queryKey: ['notifications'] });
    };

    if (application.isLoading) {
        return <TenantPage><Skeleton className="h-96" /></TenantPage>;
    }

    if (application.isError || !application.data) {
        return (
            <TenantPage>
                <EmptyState
                    title={t('errors.somethingWentWrong')}
                    description={errorMessage(application.error)}
                    icon={<FileText size={22} />}
                    action={<Button variant="secondary" onClick={() => void application.refetch()}>{t('common.retry')}</Button>}
                />
            </TenantPage>
        );
    }

    const app = application.data;
    const canReviewDocuments = canUpdate && !['approved', 'rejected'].includes(app.status);
    const isSuspended = app.group.status === 'suspended';

    const openDialog = (next: ReasonDialog) => {
        setReason('');
        setFeedback('');
        setDialog(next);
    };

    const submitReasonDialog = async () => {
        if (!dialog) {
            return;
        }

        setBusy(true);
        try {
            if (dialog.type === 'reject-document') {
                if (!reason.trim()) {
                    throw new Error(t('adminApplications.reasonRequired'));
                }
                await rejectGroupDocument(applicationId, dialog.document.id, reason.trim());
                toast.success(t('adminApplications.documentRejected'));
            } else if (dialog.type === 'resubmission') {
                if (!feedback.trim()) {
                    throw new Error(t('adminApplications.feedbackRequired'));
                }
                await requestGroupDocumentResubmission(applicationId, dialog.document.id, feedback.trim());
                toast.success(t('adminApplications.resubmissionRequested'));
            } else if (dialog.type === 'reject-application') {
                if (!reason.trim()) {
                    throw new Error(t('adminApplications.reasonRequired'));
                }
                await rejectGroupApplication(applicationId, {
                    reason: reason.trim(),
                    review_feedback: feedback.trim() || undefined,
                });
                toast.success(t('adminApplications.applicationRejected'));
            } else if (dialog.type === 'suspend') {
                if (!reason.trim()) {
                    throw new Error(t('adminApplications.reasonRequired'));
                }
                await suspendGroupApplication(applicationId, reason.trim());
                toast.success(t('adminApplications.suspendedMessage'));
            }
            setDialog(null);
            refresh();
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    const acceptDocument = async (document: AdminGroupDocument) => {
        setBusy(true);
        try {
            await acceptGroupDocument(applicationId, document.id);
            toast.success(t('adminApplications.documentAccepted'));
            refresh();
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    const onConfirm = async () => {
        if (!confirmAction) {
            return;
        }

        setBusy(true);
        try {
            if (confirmAction === 'approve') {
                await approveGroupApplication(applicationId);
                toast.success(t('adminApplications.applicationApproved'));
            } else if (confirmAction === 'reopen') {
                await reopenGroupApplication(applicationId);
                toast.success(t('adminApplications.applicationReopened'));
            } else {
                await reactivateGroupApplication(applicationId);
                toast.success(t('adminApplications.reactivatedMessage'));
            }
            setConfirmAction(null);
            refresh();
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    const saveNote = async () => {
        if (!note.trim()) {
            toast.error(t('adminApplications.noteRequired'));
            return;
        }

        setBusy(true);
        try {
            await addGroupApplicationNote(applicationId, note.trim());
            setNote('');
            toast.success(t('adminApplications.noteAdded'));
            refresh();
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    const download = async (document: AdminGroupDocument) => {
        try {
            const blob = await downloadGroupDocument(applicationId, document.id);
            triggerDownload(blob, document.name ?? `${document.type}-${document.id}`);
        } catch (error) {
            toast.error(errorMessage(error));
        }
    };

    const openPreview = async (document: AdminGroupDocument) => {
        setPreviewLoading(true);
        setPreviewError(null);
        try {
            const blob = await downloadGroupDocument(applicationId, document.id, true);
            const name = document.name ?? document.type;
            const isImage = /\.(png|jpe?g|gif|webp)$/i.test(name);
            const isPdf = /\.pdf$/i.test(name);
            setPreview({ documentId: document.id, name, url: URL.createObjectURL(blob), isImage, isPdf });

            if (!isImage && !isPdf) {
                setPreviewError(t('adminApplications.previewUnavailable'));
            }
        } catch (error) {
            setPreviewError(errorMessage(error));
        } finally {
            setPreviewLoading(false);
        }
    };

    return (
        <TenantPage
            title={app.group.name ?? app.reference}
            subtitle={t('adminApplications.subtitle')}
            actions={
                <>
                    <Button variant="secondary" onClick={() => navigate('/admin/applications')}>{t('adminApplications.backToList')}</Button>
                    {app.status === 'rejected' && canUpdate ? (
                        <Button variant="secondary" onClick={() => setConfirmAction('reopen')}><RotateCcw size={15} /> {t('adminApplications.reopen')}</Button>
                    ) : null}
                    {canUpdate && !isSuspended && app.status === 'approved' ? (
                        <Button variant="secondary" onClick={() => openDialog({ type: 'suspend' })}>{t('adminApplications.suspend')}</Button>
                    ) : null}
                    {canUpdate && isSuspended ? (
                        <Button variant="secondary" onClick={() => setConfirmAction('reactivate')}>{t('adminApplications.reactivate')}</Button>
                    ) : null}
                </>
            }
        >
            <div className="mb-5 flex flex-wrap items-center gap-2">
                <Badge tone={statusTone(app.status)}>{t(`status.${app.status}`, { defaultValue: humanize(app.status) })}</Badge>
                <Badge tone={statusTone(app.group.status)}>{t(`status.${app.group.status}`, { defaultValue: humanize(app.group.status) })}</Badge>
                <span className="font-mono text-xs text-ink-500">{app.reference}</span>
                <span className="text-xs text-ink-400">·</span>
                <span className="text-xs text-ink-500">{t('adminApplications.submitted')}: {formatDate(app.submitted_at)}</span>
            </div>

            {app.status === 'approved' ? <Alert tone="success">{t('adminApplications.approvedMessage')}</Alert> : null}
            {app.status === 'rejected' ? <Alert tone="danger" title={app.rejection_reason ?? undefined}>{t('adminApplications.rejectedMessage')}</Alert> : null}
            {isSuspended ? <Alert tone="warning" title={app.suspension_reason ?? undefined}>{t('adminApplications.suspendedMessage')}</Alert> : null}

            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><ShieldCheck size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('adminApplications.groupInfo')}</h2>
                    </div>
                    <dl className="space-y-3 text-sm">
                        <InfoRow label={t('profile.tenantName')} value={app.group.name ?? '—'} />
                        <InfoRow label={t('profile.subdomain')} value={app.group.subdomain ? `${app.group.subdomain}.theaterlink` : '—'} />
                        <InfoRow label={t('profile.type')} value={app.group.type ? t(`roles.${app.group.type}`, { defaultValue: app.group.type }) : '—'} />
                        <InfoRow label={t('common.email')} value={app.group.email ?? '—'} />
                        <InfoRow label={t('common.phone')} value={app.group.phone ?? '—'} />
                    </dl>
                    {app.group.description ? <p className="mt-4 rounded-xl bg-ink-50 p-3 text-sm leading-6 text-ink-600">{app.group.description}</p> : null}
                </section>

                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><UserRound size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('adminApplications.ownerInfo')}</h2>
                    </div>
                    <dl className="space-y-3 text-sm">
                        <InfoRow label={t('common.name')} value={app.owner?.name ?? '—'} />
                        <InfoRow label={t('common.email')} value={app.owner?.email ?? '—'} />
                        <InfoRow label={t('common.phone')} value={app.owner?.phone ?? '—'} />
                    </dl>
                    <div className="mt-4">
                        <Badge tone={app.owner?.phone_verified_at ? 'success' : 'danger'}>
                            {app.owner?.phone_verified_at ? t('adminApplications.ownerVerified') : t('adminApplications.ownerUnverified')}
                        </Badge>
                    </div>
                </section>
            </div>

            <section className="card mt-4 overflow-hidden">
                <div className="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                    <div>
                        <h2 className="font-semibold text-ink-950">{t('adminApplications.documents')}</h2>
                        <p className="text-xs text-ink-500">{t('adminApplications.documentsProgress', { accepted: app.documents_accepted, total: app.documents_count })}</p>
                    </div>
                    <Badge tone="neutral">{app.documents_count}</Badge>
                </div>
                {app.documents.length === 0 ? (
                    <div className="p-5"><EmptyState title={t('application.noDocuments')} icon={<FileText size={20} />} /></div>
                ) : (
                    <TableShell className="rounded-none border-0">
                        <table className="min-w-full">
                            <thead className="bg-ink-50">
                                <tr>
                                    <Th>{t('common.name')}</Th>
                                    <Th>{t('common.status')}</Th>
                                    <Th>{t('adminApplications.documentFeedback')}</Th>
                                    <Th className="text-end">{t('common.actions')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {app.documents.map((document) => (
                                    <tr key={document.id}>
                                        <Td>
                                            <div className="flex items-center gap-2">
                                                <FileText size={16} className="text-ink-400" />
                                                <div>
                                                    <p className="font-medium text-ink-900">{document.name ?? humanize(document.type)}</p>
                                                    <p className="text-xs text-ink-400">{t('adminApplications.version', { version: document.version })}</p>
                                                </div>
                                            </div>
                                        </Td>
                                        <Td><Badge tone={statusTone(document.status)}>{t(`status.${document.status}`, { defaultValue: humanize(document.status) })}</Badge></Td>
                                        <Td className="max-w-xs text-xs text-ink-500">{document.rejection_reason ?? document.admin_note ?? '—'}</Td>
                                        <Td>
                                            <div className="flex flex-wrap justify-end gap-1">
                                                <Button variant="ghost" size="sm" onClick={() => void openPreview(document)}><FileText size={14} /> {t('common.preview')}</Button>
                                                <Button variant="ghost" size="sm" onClick={() => void download(document)}><Download size={14} /> {t('common.download')}</Button>
                                                {canReviewDocuments && document.status === 'pending' ? (
                                                    <>
                                                        <Button size="sm" variant="secondary" disabled={busy} onClick={() => void acceptDocument(document)}><CheckCircle2 size={14} /> {t('adminApplications.accept')}</Button>
                                                        <Button size="sm" variant="secondary" disabled={busy} onClick={() => openDialog({ type: 'reject-document', document })}><XCircle size={14} /> {t('adminApplications.rejectDocument')}</Button>
                                                    </>
                                                ) : null}
                                                {canReviewDocuments && ['pending', 'rejected'].includes(document.status) ? (
                                                    <Button size="sm" variant="secondary" disabled={busy} onClick={() => openDialog({ type: 'resubmission', document })}>
                                                        {t('adminApplications.requestResubmission')}
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </TableShell>
                )}
            </section>

            <div className="mt-4 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><CheckCircle2 size={18} /></span>
                        <div>
                            <h2 className="font-semibold text-ink-950">{t('adminApplications.reviewDecision')}</h2>
                            <p className="text-xs text-ink-500">{t('adminApplications.decisionHelp')}</p>
                        </div>
                    </div>

                    {app.approval_blockers.length > 0 ? (
                        <Alert tone="warning" title={t('adminApplications.blockers')}>
                            <ul className="list-inside list-disc space-y-1 text-xs">
                                {app.approval_blockers.map((blocker) => <li key={blocker}>{blocker}</li>)}
                            </ul>
                        </Alert>
                    ) : (
                        <Alert tone="success">{t('adminApplications.noBlockers')}</Alert>
                    )}

                    <div className="mt-4 flex flex-wrap gap-2">
                        {canUpdate ? (
                            <>
                                <Button disabled={!app.can_approve || busy} onClick={() => setConfirmAction('approve')}>
                                    <CheckCircle2 size={15} /> {t('adminApplications.approve')}
                                </Button>
                                {!['approved', 'rejected'].includes(app.status) ? (
                                    <Button variant="danger" disabled={busy} onClick={() => openDialog({ type: 'reject-application' })}>
                                        <XCircle size={15} /> {t('adminApplications.rejectApplication')}
                                    </Button>
                                ) : null}
                            </>
                        ) : (
                            <p className="text-sm text-ink-500">{t('errors.unauthorized')}</p>
                        )}
                    </div>

                    {app.review_feedback ? (
                        <div className="mt-4 rounded-xl bg-ink-50 p-3 text-sm text-ink-600">
                            <p className="font-semibold text-ink-800">{t('adminApplications.reviewFeedback')}</p>
                            <p className="mt-1">{app.review_feedback}</p>
                        </div>
                    ) : null}
                </section>

                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><History size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('adminApplications.internalNotes')}</h2>
                    </div>
                    {app.admin_notes ? <p className="whitespace-pre-line rounded-xl bg-ink-50 p-3 text-xs leading-6 text-ink-600">{app.admin_notes}</p> : <p className="text-sm text-ink-500">{t('adminApplications.noNotes')}</p>}
                    {canUpdate ? (
                        <div className="mt-4">
                            <Field label={t('adminApplications.addNote')}>
                                <Textarea value={note} onChange={(event) => setNote(event.target.value)} placeholder={t('adminApplications.notePlaceholder')} />
                            </Field>
                            <Button size="sm" className="mt-3" onClick={() => void saveNote()} loading={busy}>{t('adminApplications.saveNote')}</Button>
                        </div>
                    ) : null}
                </section>
            </div>

            <section className="card card-body mt-4">
                <div className="mb-4 flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Clock3 size={18} /></span>
                    <h2 className="font-semibold text-ink-950">{t('adminApplications.history')}</h2>
                </div>
                {app.history.length === 0 ? (
                    <p className="text-sm text-ink-500">{t('adminApplications.noHistory')}</p>
                ) : (
                    <ol className="relative space-y-4 border-s border-ink-200 ps-5">
                        {app.history.map((event) => (
                            <li key={event.id} className="relative">
                                <span className="absolute -start-[1.65rem] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-500" />
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-sm font-semibold text-ink-900">{humanize(event.action)}</p>
                                    <Badge tone={statusTone(event.to_status)}>{t(`status.${event.to_status}`, { defaultValue: humanize(event.to_status) })}</Badge>
                                </div>
                                {event.note ? <p className="mt-1 text-xs text-ink-500">{event.note}</p> : null}
                                <p className="mt-1 text-[11px] text-ink-400">
                                    {event.actor ?? t('roles.admin')} · {formatDateTime(event.created_at)}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}
            </section>

            <Modal
                open={dialog !== null}
                onClose={() => setDialog(null)}
                title={
                    dialog?.type === 'reject-document'
                        ? t('adminApplications.rejectDocument')
                        : dialog?.type === 'resubmission'
                            ? t('adminApplications.requestResubmission')
                            : dialog?.type === 'reject-application'
                                ? t('adminApplications.rejectApplication')
                                : t('adminApplications.suspend')
                }
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)} disabled={busy}>{t('common.cancel')}</Button>
                        <Button variant={dialog?.type === 'reject-application' || dialog?.type === 'suspend' || dialog?.type === 'reject-document' ? 'danger' : 'primary'} onClick={() => void submitReasonDialog()} loading={busy}>
                            {t('common.confirm')}
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    {dialog?.type === 'resubmission' ? (
                        <Field label={t('adminApplications.documentFeedback')}><Textarea value={feedback} onChange={(event) => setFeedback(event.target.value)} required /></Field>
                    ) : (
                        <>
                            <Field label={dialog?.type === 'suspend' ? t('adminApplications.suspendReason') : t('adminApplications.rejectReason')}>
                                <Textarea value={reason} onChange={(event) => setReason(event.target.value)} required />
                            </Field>
                            {dialog?.type === 'reject-application' ? (
                                <Field label={t('adminApplications.reviewFeedback')}><Textarea value={feedback} onChange={(event) => setFeedback(event.target.value)} /></Field>
                            ) : null}
                        </>
                    )}
                </div>
            </Modal>

            <ConfirmDialog
                open={confirmAction !== null}
                title={confirmAction === 'approve' ? t('adminApplications.approve') : confirmAction === 'reopen' ? t('adminApplications.reopen') : t('adminApplications.reactivate')}
                description={confirmAction === 'approve' ? t('adminApplications.confirmApprove') : confirmAction === 'reopen' ? t('adminApplications.reopenConfirm') : t('adminApplications.reactivate')}
                confirmLabel={t('common.confirm')}
                cancelLabel={t('common.cancel')}
                loading={busy}
                onClose={() => setConfirmAction(null)}
                onConfirm={() => void onConfirm()}
            />

            <Modal
                open={preview !== null || previewLoading || previewError !== null}
                onClose={() => { setPreview(null); setPreviewError(null); }}
                title={t('adminApplications.documentPreview')}
                size="lg"
                footer={
                    <>
                        <Button variant="secondary" onClick={() => { setPreview(null); setPreviewError(null); }}>{t('common.close')}</Button>
                        {preview ? (
                            <Button onClick={() => void download({ id: preview.documentId, name: preview.name } as AdminGroupDocument)}>{t('common.download')}</Button>
                        ) : null}
                    </>
                }
            >
                {previewLoading ? <Skeleton className="h-72" /> : previewError ? <FormError>{previewError}</FormError> : preview ? (
                    <div className="overflow-hidden rounded-xl border border-ink-200 bg-ink-50">
                        {preview.isImage ? <img src={preview.url} alt={preview.name} className="mx-auto max-h-[65vh] object-contain" /> : null}
                        {preview.isPdf ? <iframe src={preview.url} title={preview.name} className="h-[65vh] w-full" /> : null}
                        {!preview.isImage && !preview.isPdf ? <div className="p-8 text-center text-sm text-ink-500">{t('adminApplications.previewUnavailable')}</div> : null}
                    </div>
                ) : null}
            </Modal>
        </TenantPage>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-ink-100 pb-2 last:border-0 last:pb-0">
            <dt className="text-ink-500">{label}</dt>
            <dd className="text-end font-medium text-ink-900">{value}</dd>
        </div>
    );
}
