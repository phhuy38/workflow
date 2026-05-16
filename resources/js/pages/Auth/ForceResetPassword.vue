<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.force-reset'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-muted/40 p-4">
        <Head title="Cập nhật mật khẩu" />

        <Card class="w-full max-w-md">
            <CardHeader>
                <CardTitle>Chào mừng đến với hệ thống</CardTitle>
                <CardDescription>
                    Vì lý do bảo mật, bạn cần thay đổi mật khẩu mặc định trước
                    khi bắt đầu sử dụng.
                </CardDescription>
            </CardHeader>

            <form @submit.prevent="submit">
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="password">Mật khẩu mới</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            :class="{
                                'border-destructive': form.errors.password,
                            }"
                        />
                        <p
                            v-if="form.errors.password"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation"
                            >Xác nhận mật khẩu mới</Label
                        >
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            :class="{
                                'border-destructive':
                                    form.errors.password_confirmation,
                            }"
                        />
                        <p
                            v-if="form.errors.password_confirmation"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>
                </CardContent>

                <CardFooter>
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="form.processing"
                    >
                        Cập nhật mật khẩu
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
