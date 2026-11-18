export type Actor = 'admin' | 'theater' | 'group' | 'user';

export type TenantType = 'theater' | 'group';

export interface ApiErrorPayload {
    success?: boolean;
    message?: string;
    errors?: Record<string, string[]>;
}

export interface ApiEnvelope<T> {
    success: boolean;
    message: string;
    data: T;
    errors?: Record<string, string[]>;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PaginatedData<T> {
    data: T[];
    pagination: Pagination;
}

export interface TenantInfo {
    id: number;
    ulid: string;
    type: TenantType;
    name: string;
    subdomain: string;
    status: string;
}

export interface RoleSummary {
    id: number;
    name: string;
    template_key: string | null;
    is_system?: boolean;
}

export interface TenantMember {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_owner: boolean;
    is_active: boolean;
    token?: string | null;
    token_type?: string | null;
    expires_in?: number | null;
    tenant: TenantInfo | null;
    roles: RoleSummary[];
    permissions: string[];
    last_login_at: string | null;
}

export interface TeamMember {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    is_owner: boolean;
    is_active: boolean;
    roles: RoleSummary[];
    last_login_at: string | null;
    created_at: string;
}

export interface TenantRole {
    id: number;
    name: string;
    template_key: string | null;
    is_system: boolean;
    is_active: boolean;
    permissions: string[];
    permission_ids: number[];
}

export interface PermissionOption {
    id: number;
    name: string;
    module: string | null;
    action: string | null;
    title: string;
}

export interface Invitation {
    id: number;
    email: string;
    name: string | null;
    role_id: number | null;
    expires_at: string;
    accepted_at: string | null;
    token?: string | null;
}

export interface GroupDocument {
    id: number;
    type: string;
    name: string | null;
    status: string;
    version: number;
    rejection_reason: string | null;
    admin_note: string | null;
    reviewed_at: string | null;
    resubmitted_at: string | null;
    can_resubmit: boolean;
}

export interface GroupApplicationHistory {
    id: number;
    document_id: number | null;
    from_status: string | null;
    to_status: string;
    action: string;
    note: string | null;
    actor: string | null;
    actor_type: 'admin' | 'member' | 'system';
    created_at: string;
}

export interface GroupApplication {
    id: number;
    reference: string;
    status: string;
    previous_status: string | null;
    submitted_at: string | null;
    approved_at: string | null;
    last_action_at: string | null;
    rejection_reason: string | null;
    review_feedback: string | null;
    owner_phone_verified: boolean;
    required_actions: string[];
    documents: GroupDocument[];
    documents_count: number;
    documents_accepted: number;
    history: GroupApplicationHistory[];
    support: {
        email: string;
        phone: string;
    };
}

export interface AdminGroupApplication {
    id: number;
    reference: string;
    status: string;
    previous_status: string | null;
    submitted_at: string | null;
    last_action_at: string | null;
    approved_at: string | null;
    suspended_at: string | null;
    suspension_reason: string | null;
    group_name: string | null;
    subdomain: string | null;
    tenant_status: string | null;
    owner: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        phone_verified_at: string | null;
    } | null;
    documents_count: number;
    documents_accepted: number;
    documents_pending: number;
}

export interface AdminGroupDocument {
    id: number;
    type: string;
    name: string | null;
    status: string;
    version: number;
    rejection_reason: string | null;
    admin_note: string | null;
    reviewed_by: string | null;
    reviewed_at: string | null;
    resubmitted_at: string | null;
    created_at: string;
}

export interface AdminGroupApplicationDetail {
    id: number;
    reference: string;
    status: string;
    previous_status: string | null;
    submitted_at: string | null;
    last_action_at: string | null;
    approved_at: string | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
    review_feedback: string | null;
    admin_notes: string | null;
    suspended_at: string | null;
    suspension_reason: string | null;
    group: {
        name: string | null;
        subdomain: string | null;
        type: string | null;
        status: string | null;
        description: string | null;
        contact_person_name: string | null;
        email: string | null;
        phone: string | null;
    };
    owner: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        phone_verified_at: string | null;
    } | null;
    documents: AdminGroupDocument[];
    documents_count: number;
    documents_accepted: number;
    documents_pending: number;
    history: GroupApplicationHistory[];
    approval_blockers: string[];
    can_approve: boolean;
}

export interface AppNotification {
    id: number;
    type: string;
    title: string;
    body: string;
    data: {
        application_id?: number;
        document_id?: number;
        status?: string;
    } | null;
    read_at: string | null;
    created_at: string;
}

export interface NotificationPage extends PaginatedData<AppNotification> {
    unread_count: number;
}

export interface TheaterProfileData {
    id: number;
    tenant_id: number;
    name: string;
    description: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    country_id: number | null;
    city_id: number | null;
    country: { id: number; name_en: string; name_ar: string } | null;
    city: { id: number; name_en: string; name_ar: string } | null;
    timezone: string;
    logo_url: string | null;
    cover_url: string | null;
    is_active: boolean;
}

export interface Hall {
    id: number;
    name: string;
    description: string | null;
    capacity: number;
    is_active: boolean;
    schedules_count?: number;
    created_at: string;
}

export interface HallSchedule {
    id: number;
    hall_id: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    slot_duration_minutes: number;
    price: string;
    currency: string;
    is_active: boolean;
}

export interface HallSlot {
    id: number;
    hall_id: number;
    starts_at: string;
    ends_at: string;
    starts_at_local: string;
    ends_at_local: string;
    timezone: string;
    status: string;
    price: string;
    currency: string;
    show_id: number | null;
    block_id: number | null;
}

export interface HallBlock {
    id: number;
    hall_id: number;
    starts_at: string;
    ends_at: string;
    reason: string | null;
}

export interface EventCategory {
    id: number;
    slug: string;
    name: string | null;
}

export interface EventRecord {
    id: number;
    slug: string;
    status: string;
    category: EventCategory | null;
    poster_url: string | null;
    title_en: string | null;
    title_ar: string | null;
    description_en: string | null;
    description_ar: string | null;
    published_at: string | null;
    cancelled_at: string | null;
    created_at: string;
    shows?: ShowRecord[];
    shows_count?: number;
}

export interface ShowRecord {
    id: number;
    event_id: number;
    hall_id: number;
    starts_at: string;
    ends_at: string;
    status: string;
    event?: {
        id: number;
        title_en: string | null;
        title_ar: string | null;
    };
    hall?: {
        id: number;
        name: string;
        description: string | null;
        capacity: number;
    };
    tiers?: TicketTierRecord[];
}

export interface TicketTierRecord {
    id: number;
    show_id: number;
    name: string | null;
    name_en: string | null;
    name_ar: string | null;
    description_en: string | null;
    description_ar: string | null;
    price: string;
    currency: string;
    total_quantity: number;
    sold_quantity: number;
    held_quantity: number;
    available_quantity: number;
    sale_starts_at: string | null;
    sale_ends_at: string | null;
    per_booking_limit: number | null;
    is_active: boolean;
}

export interface PublicTheater {
    id: number;
    name: string;
    subdomain: string;
    description: string | null;
    address: string | null;
    city: { id: number; name_en: string; name_ar: string } | null;
    country: { id: number; name_en: string; name_ar: string } | null;
    logo_url: string | null;
    cover_url: string | null;
    timezone: string | null;
    halls_count?: number;
    halls?: Array<{
        id: number;
        name: string;
        description: string | null;
        capacity: number;
    }>;
}

export interface PublicEvent {
    id: number;
    slug: string;
    title: string | null;
    description: string | null;
    poster_url: string | null;
    category: EventCategory | null;
    theater: {
        id: number | null;
        name: string | null;
        logo_url: string | null;
        address: string | null;
    };
    published_at: string | null;
    shows: ShowRecord[];
}

export interface UserProfile {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    country_code: string | null;
    avatar: string | null;
    postal_code: string | null;
    main_address: string | null;
    is_active: boolean;
    created_at: string | null;
    token?: string | null;
    token_type?: string | null;
    expires_in?: number | null;
    last_login_at: string | null;
}

export interface AdminProfile {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    job_title: string | null;
    contract_type: string | null;
    is_active: boolean;
    roles: Array<{ id: number; name: string }>;
    permissions?: string[];
    created_at: string | null;
    last_login_at: string | null;
    token?: string | null;
    token_type?: string | null;
    expires_in?: number | null;
}

export interface TenantDashboard {
    tenant: TenantInfo;
    member: TeamMember;
    counts: Record<string, number>;
}

export interface UserDashboard {
    user: UserProfile;
    bookings_count: number;
    upcoming_bookings: unknown[];
}

export interface AdminCounts {
    users_count: number;
    theaters_count: number;
    theater_groups_count: number;
    pending_group_applications_count: number;
    reservations_count: number;
    bookings_count: number;
}

export interface Country {
    id: number;
    name_ar: string;
    name_en: string;
    code: string | null;
    phone_code: string | null;
    phone_length: number | null;
}

export interface City {
    id: number;
    name_ar: string;
    name_en: string;
    setting_id: number;
}
