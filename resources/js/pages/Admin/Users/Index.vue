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
import {
    index as usersIndex,
    create as usersCreate,
    edit as usersEdit,
} from '@/routes/admin/users';
import {
    deactivate as deactivateUser,
    reactivate as reactivateUser,
} from '@/routes/admin/users';

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
            { title: 'Bảng điều khiển', href: '/dashboard' },
            { title: 'Quản lý người dùng', href: usersIndex().url },
        ],
    },
});

function handleDeactivate(user: UserData) {
    if (!confirm(`Vô hiệu hóa người dùng "${user.full_name}"?`)) {
        return;
    }

    router.post(deactivateUser({ user: user.id }).url);
}

function handleReactivate(user: UserData) {
    router.post(reactivateUser({ user: user.id }).url);
}

function roleColor(
    role: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    const map: Record<
        string,
        'default' | 'secondary' | 'destructive' | 'outline'
    > = {
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
    <Head title="Quản lý người dùng" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Quản lý người dùng</h1>
            <Button as="a" :href="usersCreate().url">Thêm người dùng</Button>
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Họ và tên</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Vai trò</TableHead>
                        <TableHead>Trạng thái</TableHead>
                        <TableHead>Đăng nhập lần cuối</TableHead>
                        <TableHead class="text-right">Hành động</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell class="font-medium">{{
                            user.full_name
                        }}</TableCell>
                        <TableCell>{{ user.email }}</TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role"
                                    :variant="roleColor(role)"
                                >
                                    {{
                                        role === 'admin'
                                            ? 'Quản trị viên'
                                            : role === 'manager'
                                              ? 'Quản lý'
                                              : role === 'process_designer'
                                                ? 'Thiết kế quy trình'
                                                : role === 'executor'
                                                  ? 'Người thực thi'
                                                  : role === 'beneficiary'
                                                    ? 'Người thụ hưởng'
                                                    : role
                                    }}
                                </Badge>
                                <span
                                    v-if="user.roles.length === 0"
                                    class="text-sm text-muted-foreground"
                                    >—</span
                                >
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    user.is_active ? 'default' : 'destructive'
                                "
                            >
                                {{
                                    user.is_active
                                        ? 'Hoạt động'
                                        : 'Đã vô hiệu hóa'
                                }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{
                                user.last_login_at
                                    ? new Date(
                                          user.last_login_at,
                                      ).toLocaleDateString()
                                    : '—'
                            }}
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    as="a"
                                    :href="usersEdit({ user: user.id }).url"
                                >
                                    Sửa
                                </Button>
                                <Button
                                    v-if="user.is_active"
                                    variant="destructive"
                                    size="sm"
                                    @click="handleDeactivate(user)"
                                >
                                    Vô hiệu hóa
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    @click="handleReactivate(user)"
                                >
                                    Kích hoạt
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell
                            colspan="6"
                            class="py-8 text-center text-muted-foreground"
                        >
                            Không tìm thấy người dùng nào.
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
                Trang trước
            </Button>
            <span class="flex items-center px-2 text-sm text-muted-foreground">
                Trang {{ users.meta.current_page }} / {{ users.meta.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="!users.links.next"
                as="a"
                :href="users.links.next ?? '#'"
            >
                Trang sau
            </Button>
        </div>
    </div>
</template>
