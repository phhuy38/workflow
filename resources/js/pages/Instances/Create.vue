<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
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
import { Textarea } from '@/components/ui/textarea';
import type { ProcessTemplate } from '@/types';

interface Props {
    templates: ProcessTemplate[];
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Instances', href: '/process-instances' },
            { title: 'Khởi động quy trình', href: '/process-instances/create' },
        ],
    },
});

const form = useForm({
    template_id: '',
    name: '',
    context_data: '',
});

function submit() {
    form.post('/process-instances', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Khởi động quy trình" />

    <div class="mx-auto flex max-w-2xl flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Khởi động quy trình mới</h1>
            <p class="mt-1 text-muted-foreground">
                Chọn một template và đặt tên cho lần chạy này.
            </p>
        </div>

        <div class="rounded-md border p-6">
            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="flex flex-col gap-1.5">
                    <Label for="template"
                        >Chọn template
                        <span class="text-destructive">*</span></Label
                    >
                    <Select v-model="form.template_id">
                        <SelectTrigger
                            id="template"
                            :class="{
                                'border-destructive': form.errors.template_id,
                            }"
                        >
                            <SelectValue
                                placeholder="Chọn một quy trình đã công bố..."
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="template in templates"
                                :key="template.id"
                                :value="template.id.toString()"
                            >
                                {{ template.name }} ({{ template.step_count }}
                                bước)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.template_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.template_id }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="instance-name"
                        >Tên lần chạy quy trình
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="instance-name"
                        v-model="form.name"
                        placeholder="Ví dụ: Onboarding nhân viên mới - Nguyễn Văn A"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Tên này giúp bạn phân biệt các lần chạy khác nhau của
                        cùng một template.
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="context">Ghi chú / Thông tin bổ sung</Label>
                    <Textarea
                        id="context"
                        v-model="form.context_data"
                        placeholder="Nhập thông tin context nếu cần..."
                        rows="4"
                    />
                    <p
                        v-if="form.errors.context_data"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.context_data }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-4 border-t pt-4">
                    <Button
                        variant="outline"
                        type="button"
                        as="a"
                        href="/process-instances"
                        >Hủy</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Đang khởi động...</span>
                        <span v-else>Khởi động quy trình</span>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
