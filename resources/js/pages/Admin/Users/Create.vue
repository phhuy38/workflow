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
            { title: 'Bảng điều khiển', href: '/dashboard' },
            { title: 'Quản lý người dùng', href: usersIndex().url },
            { title: 'Thêm người dùng', href: '#' },
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
    <Head title="Thêm người dùng" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Thêm người dùng</h1>
        </div>

        <div class="max-w-lg rounded-md border p-6">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label for="full_name">Họ và tên</Label>
                    <Input
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                        placeholder="Họ và tên"
                        :class="{ 'border-destructive': form.errors.full_name }"
                    />
                    <p
                        v-if="form.errors.full_name"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.full_name }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="email">Địa chỉ email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="email@example.com"
                        :class="{ 'border-destructive': form.errors.email }"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="password">Mật khẩu</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        placeholder="Tối thiểu 8 ký tự"
                        :class="{ 'border-destructive': form.errors.password }"
                    />
                    <p
                        v-if="form.errors.password"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="role">Vai trò</Label>
                    <Select v-model="form.role">
                        <SelectTrigger
                            id="role"
                            :class="{ 'border-destructive': form.errors.role }"
                        >
                            <SelectValue placeholder="Chọn vai trò" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="manager">Quản lý</SelectItem>
                            <SelectItem value="process_designer"
                                >Thiết kế quy trình</SelectItem
                            >
                            <SelectItem value="executor"
                                >Người thực thi</SelectItem
                            >
                            <SelectItem value="beneficiary"
                                >Người thụ hưởng</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.role" class="text-sm text-destructive">
                        {{ form.errors.role }}
                    </p>
                </div>

                <div class="flex gap-2 pt-2">
                    <Button type="submit" :disabled="form.processing">
                        Tạo người dùng
                    </Button>
                    <Button variant="outline" as="a" :href="usersIndex().url">
                        Hủy
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
