<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as usersIndex,
    update as usersUpdate,
    deactivate as deactivateUser,
    reactivate as reactivateUser,
    assignDesigner,
    revokeDesigner,
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

const props = defineProps<{
    user: UserData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'User Management', href: usersIndex().url },
            { title: 'Edit User', href: '#' },
        ],
    },
});

const form = useForm({
    full_name: props.user.full_name,
    email: props.user.email,
});

const isDesigner = computed(() => props.user.roles.includes('process_designer'));

function submit() {
    form.put(usersUpdate({ user: props.user.id }).url);
}

function handleDeactivate() {
    if (!confirm(`Deactivate user "${props.user.full_name}"?`)) return;
    router.post(deactivateUser({ user: props.user.id }).url);
}

function handleReactivate() {
    router.post(reactivateUser({ user: props.user.id }).url);
}

function handleAssignDesigner() {
    router.post(assignDesigner({ user: props.user.id }).url, {}, { preserveState: false });
}

function handleRevokeDesigner() {
    if (!confirm(`Remove Process Designer role from "${props.user.full_name}"?`)) return;
    router.post(revokeDesigner({ user: props.user.id }).url, {}, { preserveState: false });
}
</script>

<template>
    <Head title="Edit User" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Edit User</h1>
        </div>

        <div class="grid max-w-2xl gap-6">
            <!-- Basic info form -->
            <div class="rounded-md border p-6">
                <h2 class="mb-4 text-lg font-medium">Basic Information</h2>
                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label for="full_name">Full Name</Label>
                        <Input
                            id="full_name"
                            v-model="form.full_name"
                            type="text"
                            :class="{ 'border-destructive': form.errors.full_name }"
                        />
                        <p v-if="form.errors.full_name" class="text-destructive text-sm">
                            {{ form.errors.full_name }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :class="{ 'border-destructive': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-destructive text-sm">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <Button type="submit" :disabled="form.processing">
                            Save Changes
                        </Button>
                        <Button variant="outline" as="a" :href="usersIndex().url">
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Roles section -->
            <div class="rounded-md border p-6">
                <h2 class="mb-4 text-lg font-medium">Roles</h2>
                <div class="flex flex-wrap gap-2 mb-4">
                    <Badge
                        v-for="role in user.roles"
                        :key="role"
                        variant="secondary"
                    >
                        {{ role }}
                    </Badge>
                    <span v-if="user.roles.length === 0" class="text-muted-foreground text-sm">No roles assigned</span>
                </div>

                <div class="flex items-center justify-between rounded border p-3">
                    <div>
                        <p class="font-medium text-sm">Process Designer</p>
                        <p class="text-muted-foreground text-xs">Can create and manage process templates</p>
                    </div>
                    <Button
                        v-if="isDesigner"
                        variant="outline"
                        size="sm"
                        @click="handleRevokeDesigner"
                    >
                        Revoke
                    </Button>
                    <Button
                        v-else
                        variant="secondary"
                        size="sm"
                        @click="handleAssignDesigner"
                    >
                        Assign
                    </Button>
                </div>
            </div>

            <!-- Account status section -->
            <div class="rounded-md border p-6">
                <h2 class="mb-4 text-lg font-medium">Account Status</h2>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Badge :variant="user.is_active ? 'default' : 'destructive'">
                            {{ user.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                        <span v-if="user.last_login_at" class="text-muted-foreground text-sm">
                            Last login: {{ new Date(user.last_login_at).toLocaleDateString() }}
                        </span>
                    </div>
                    <Button
                        v-if="user.is_active"
                        variant="destructive"
                        size="sm"
                        @click="handleDeactivate"
                    >
                        Deactivate
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        @click="handleReactivate"
                    >
                        Reactivate
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
