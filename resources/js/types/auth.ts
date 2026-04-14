export type User = {
    id: number;
    full_name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AuthPermissions = {
    manage_templates: boolean;
    publish_templates: boolean;
    launch_instances: boolean;
    view_all_instances: boolean;
    manage_instances: boolean;
    complete_assigned_steps: boolean;
    view_own_instances: boolean;
    manage_users: boolean;
    manage_system: boolean;
};

export type Auth = {
    user: User;
    can: AuthPermissions;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
