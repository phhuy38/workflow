<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as usersIndex, store as usersStore } from '@/routes/admin/users';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'User Management', href: usersIndex().url },
            { title: 'Add User', href: '#' },
        ],
    },
});

const form = useForm({
    full_name: '',
    email: '',
    password: '',
    role: '',
});

function submit() {
    form.post(usersStore().url);
}
</script>

<template>
    <Head title="Add User" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Add User</h1>
        </div>

        <div class="max-w-lg rounded-md border p-6">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label for="full_name">Full Name</Label>
                    <Input
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                        placeholder="Full name"
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
                        placeholder="email@example.com"
                        :class="{ 'border-destructive': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="text-destructive text-sm">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Min 8 characters"
                        :class="{ 'border-destructive': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="text-destructive text-sm">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="role">Role</Label>
                    <Select v-model="form.role">
                        <SelectTrigger
                            id="role"
                            :class="{ 'border-destructive': form.errors.role }"
                        >
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="manager">Manager</SelectItem>
                            <SelectItem value="process_designer">Process Designer</SelectItem>
                            <SelectItem value="executor">Executor</SelectItem>
                            <SelectItem value="beneficiary">Beneficiary</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.role" class="text-destructive text-sm">
                        {{ form.errors.role }}
                    </p>
                </div>

                <div class="flex gap-2 pt-2">
                    <Button type="submit" :disabled="form.processing">
                        Create User
                    </Button>
                    <Button variant="outline" as="a" :href="usersIndex().url">
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
