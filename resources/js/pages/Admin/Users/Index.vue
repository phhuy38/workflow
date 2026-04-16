<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index as usersIndex, create as usersCreate, edit as usersEdit } from '@/routes/admin/users';
import { deactivate as deactivateUser, reactivate as reactivateUser } from '@/routes/admin/users';

interface UserData {
    id: number;
    full_name: string;
    email: string;
    is_active: boolean;
    last_login_at: string | null;
    created_at: string;
    roles: string[];
}

interface PaginatedUsers {
    data: UserData[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
    };
    links: {
        prev: string | null;
        next: string | null;
    };
}

defineProps<{
    users: PaginatedUsers;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'User Management', href: usersIndex().url },
        ],
    },
});

function handleDeactivate(user: UserData) {
    if (!confirm(`Deactivate user "${user.full_name}"?`)) return;
    router.post(deactivateUser({ user: user.id }).url);
}

function handleReactivate(user: UserData) {
    router.post(reactivateUser({ user: user.id }).url);
}

function roleColor(role: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    const map: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        admin: 'destructive',
        manager: 'default',
        process_designer: 'secondary',
        executor: 'outline',
        beneficiary: 'outline',
    };
    return map[role] ?? 'outline';
}
</script>

<template>
    <Head title="User Management" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">User Management</h1>
            <Button as="a" :href="usersCreate().url">Add User</Button>
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Roles</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Last Login</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell class="font-medium">{{ user.full_name }}</TableCell>
                        <TableCell>{{ user.email }}</TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role"
                                    :variant="roleColor(role)"
                                >
                                    {{ role }}
                                </Badge>
                                <span v-if="user.roles.length === 0" class="text-muted-foreground text-sm">—</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="user.is_active ? 'default' : 'destructive'">
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground text-sm">
                            {{ user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : '—' }}
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="sm" as="a" :href="usersEdit({ user: user.id }).url">
                                    Edit
                                </Button>
                                <Button
                                    v-if="user.is_active"
                                    variant="destructive"
                                    size="sm"
                                    @click="handleDeactivate(user)"
                                >
                                    Deactivate
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    @click="handleReactivate(user)"
                                >
                                    Reactivate
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell colspan="6" class="text-muted-foreground py-8 text-center">
                            No users found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="users.meta.last_page > 1" class="flex justify-end gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!users.links.prev"
                as="a"
                :href="users.links.prev ?? '#'"
            >
                Previous
            </Button>
            <span class="text-muted-foreground flex items-center px-2 text-sm">
                Page {{ users.meta.current_page }} of {{ users.meta.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="!users.links.next"
                as="a"
                :href="users.links.next ?? '#'"
            >
                Next
            </Button>
        </div>
    </div>
</template>
