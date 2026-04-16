import { usePage } from '@inertiajs/vue3';
import type { AuthPermissions } from '@/types/auth';

/**
 * Composable for checking user permissions in Vue components (ADR-025, ADR-019).
 *
 * IMPORTANT: These checks are UI-only (hide/show elements).
 * Server-side Policy is the authoritative security gate.
 */
export function usePermission() {
    const page = usePage();

    /**
     * Check if the authenticated user has the given permission.
     * Returns false if the user is not authenticated or permission is unknown.
     */
    const can = (permission: keyof AuthPermissions): boolean => {
        return page.props.auth?.can?.[permission] ?? false;
    };

    return { can };
}
