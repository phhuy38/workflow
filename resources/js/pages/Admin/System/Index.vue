<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
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
import {
    index as systemIndex,
    update as systemUpdate,
    testEmail as systemTestEmail,
} from '@/routes/admin/system';

interface SystemSettings {
    smtp_host: string;
    smtp_port: string;
    smtp_username: string;
    smtp_password: string;
    smtp_from_address: string;
    smtp_from_name: string;
    smtp_encryption: string;
    session_lifetime: number;
}

interface TestResult {
    success: boolean;
    message: string;
}

const props = defineProps<{
    settings: SystemSettings;
    testResult: TestResult | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bảng điều khiển', href: '/dashboard' },
            { title: 'Cài đặt hệ thống', href: systemIndex().url },
        ],
    },
});

// Sentinel to indicate password should not be changed
const PASSWORD_PLACEHOLDER = '__PRESERVE_EXISTING_PASSWORD__';

const form = useForm({
    smtp_host: props.settings.smtp_host,
    smtp_port: props.settings.smtp_port,
    smtp_username: props.settings.smtp_username,
    smtp_password: '',
    smtp_from_address: props.settings.smtp_from_address,
    smtp_from_name: props.settings.smtp_from_name,
    smtp_encryption: props.settings.smtp_encryption,
    session_lifetime: props.settings.session_lifetime,
});

const isTesting = ref(false);

function submit() {
    // If password field is empty, send placeholder to indicate "don't change"
    // This allows distinguishing between empty password (preserve) vs. new password
    if (!form.smtp_password) {
        form.smtp_password = PASSWORD_PLACEHOLDER;
    }

    form.put(systemUpdate().url);
}

function sendTestEmail() {
    isTesting.value = true;
    router.post(
        systemTestEmail().url,
        {},
        {
            only: ['testResult', 'settings'],
            preserveState: true,
            onFinish: () => {
                isTesting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Cài đặt hệ thống" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Cài đặt hệ thống</h1>
        </div>

        <form @submit.prevent="submit" class="flex max-w-2xl flex-col gap-8">
            <!-- SMTP Configuration -->
            <div class="flex flex-col gap-4 rounded-md border p-6">
                <h2 class="text-lg font-medium">Cấu hình SMTP</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_host">Máy chủ SMTP</Label>
                        <Input
                            id="smtp_host"
                            v-model="form.smtp_host"
                            type="text"
                            placeholder="smtp.example.com"
                            :class="{
                                'border-destructive': form.errors.smtp_host,
                            }"
                        />
                        <p
                            v-if="form.errors.smtp_host"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.smtp_host }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_port">Cổng</Label>
                        <Input
                            id="smtp_port"
                            v-model="form.smtp_port"
                            type="number"
                            placeholder="587"
                            :class="{
                                'border-destructive': form.errors.smtp_port,
                            }"
                        />
                        <p
                            v-if="form.errors.smtp_port"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.smtp_port }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="smtp_encryption">Mã hóa</Label>
                    <Select v-model="form.smtp_encryption">
                        <SelectTrigger
                            id="smtp_encryption"
                            :class="{
                                'border-destructive':
                                    form.errors.smtp_encryption,
                            }"
                        >
                            <SelectValue placeholder="Chọn loại mã hóa" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tls">TLS</SelectItem>
                            <SelectItem value="ssl">SSL</SelectItem>
                            <SelectItem value="none">Không</SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.smtp_encryption"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.smtp_encryption }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="smtp_username">Tên người dùng</Label>
                    <Input
                        id="smtp_username"
                        v-model="form.smtp_username"
                        type="text"
                        placeholder="user@example.com"
                        :class="{
                            'border-destructive': form.errors.smtp_username,
                        }"
                    />
                    <p
                        v-if="form.errors.smtp_username"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.smtp_username }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="smtp_password">Mật khẩu</Label>
                    <Input
                        id="smtp_password"
                        v-model="form.smtp_password"
                        type="password"
                        :placeholder="
                            settings.smtp_password
                                ? 'Để trống nếu muốn giữ nguyên mật khẩu cũ'
                                : 'Nhập mật khẩu mới'
                        "
                        :class="{
                            'border-destructive': form.errors.smtp_password,
                        }"
                    />
                    <p class="text-xs text-muted-foreground">
                        Để trống nếu muốn giữ mật khẩu hiện tại. Chỉ nhập mật
                        khẩu mới nếu bạn muốn thay đổi.
                    </p>
                    <p
                        v-if="form.errors.smtp_password"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.smtp_password }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_from_address">Email người gửi</Label>
                        <Input
                            id="smtp_from_address"
                            v-model="form.smtp_from_address"
                            type="email"
                            placeholder="no-reply@example.com"
                            :class="{
                                'border-destructive':
                                    form.errors.smtp_from_address,
                            }"
                        />
                        <p
                            v-if="form.errors.smtp_from_address"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.smtp_from_address }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_from_name">Tên người gửi</Label>
                        <Input
                            id="smtp_from_name"
                            v-model="form.smtp_from_name"
                            type="text"
                            placeholder="My App"
                            :class="{
                                'border-destructive':
                                    form.errors.smtp_from_name,
                            }"
                        />
                        <p
                            v-if="form.errors.smtp_from_name"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.smtp_from_name }}
                        </p>
                    </div>
                </div>

                <div class="pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="isTesting"
                        @click="sendTestEmail"
                    >
                        <Loader2
                            v-if="isTesting"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        Gửi email thử nghiệm
                    </Button>

                    <div v-if="testResult" class="mt-2 text-sm">
                        <span v-if="testResult.success" class="text-green-600">
                            ✅ {{ testResult.message }}
                        </span>
                        <span v-else class="text-destructive">
                            ❌ {{ testResult.message }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Session Settings -->
            <div class="flex flex-col gap-4 rounded-md border p-6">
                <h2 class="text-lg font-medium">Cài đặt phiên hoạt động</h2>

                <div class="flex max-w-xs flex-col gap-1.5">
                    <Label for="session_lifetime"
                        >Thời gian hết hạn phiên (phút)</Label
                    >
                    <Input
                        id="session_lifetime"
                        v-model="form.session_lifetime"
                        type="number"
                        min="5"
                        max="1440"
                        placeholder="120"
                        :class="{
                            'border-destructive': form.errors.session_lifetime,
                        }"
                    />
                    <p class="text-xs text-muted-foreground">
                        Tối thiểu 5 phút, tối đa 1440 phút (24 giờ)
                    </p>
                    <p
                        v-if="form.errors.session_lifetime"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.session_lifetime }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    <Loader2
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Lưu cài đặt
                </Button>
            </div>
        </form>
    </div>
</template>
