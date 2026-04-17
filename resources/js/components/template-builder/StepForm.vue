<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { store as stepStore, update as stepUpdate } from '@/routes/step-definitions';
import type { StepDefinition } from '@/types/step';

const props = defineProps<{
    templateId: number;
    step?: StepDefinition | null;
    submitLabel?: string;
}>();

const emit = defineEmits<{
    cancel: [];
    success: [];
}>();


const isEditing = !!props.step;

const form = useForm({
    template_id: props.templateId,
    name: props.step?.name ?? '',
    description: props.step?.description ?? '',
    assignee_type: props.step?.assignee_type ?? null,
    assignee_id: props.step?.assignee_id ?? null,
    duration_hours: props.step?.duration_hours ?? 24,
    is_required: props.step?.is_required ?? true,
});

function submit() {
    if (isEditing && props.step) {
        form.put(stepUpdate({ step_definition: props.step.id }).url, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(stepStore().url, {
            preserveScroll: true,
            onSuccess: () => emit('success'),
        });
    }
}
</script>

<template>
    <form class="flex flex-col gap-4" @submit.prevent="submit">
        <div class="flex flex-col gap-1.5">
            <Label for="step-name">
                Tên bước <span class="text-destructive">*</span>
            </Label>
            <Input
                id="step-name"
                v-model="form.name"
                placeholder="Nhập tên bước..."
                :class="{ 'border-destructive': form.errors.name }"
                data-test="step-name-input"
            />
            <p v-if="form.errors.name" class="text-destructive text-sm">{{ form.errors.name }}</p>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="step-description">Mô tả</Label>
            <Textarea
                id="step-description"
                v-model="form.description"
                placeholder="Mô tả bước này..."
                rows="2"
            />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
                <Label for="assignee-type">Loại người phụ trách</Label>
                <Select v-model="form.assignee_type">
                    <SelectTrigger id="assignee-type">
                        <SelectValue placeholder="Chọn loại..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="user">Người dùng cụ thể</SelectItem>
                        <SelectItem value="role">Theo vai trò</SelectItem>
                        <SelectItem value="department">Theo phòng ban</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="duration">
                    Thời hạn (giờ) <span class="text-destructive">*</span>
                </Label>
                <Input
                    id="duration"
                    v-model.number="form.duration_hours"
                    type="number"
                    min="1"
                    placeholder="24"
                    :class="{ 'border-destructive': form.errors.duration_hours }"
                    data-test="duration-input"
                />
                <p v-if="form.errors.duration_hours" class="text-destructive text-sm">
                    {{ form.errors.duration_hours }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox id="is-required" v-model:checked="form.is_required" />
            <Label for="is-required" class="cursor-pointer">Bắt buộc hoàn thành</Label>
        </div>

        <div class="flex gap-2">
            <Button
                type="submit"
                :disabled="form.processing || !form.name.trim() || form.duration_hours < 1"
                data-test="submit-step-button"
            >
                <span v-if="form.processing">Đang lưu...</span>
                <span v-else>{{ submitLabel ?? (isEditing ? 'Lưu thay đổi' : 'Thêm bước') }}</span>
            </Button>
            <Button
                type="button"
                variant="outline"
                data-test="cancel-step-button"
                @click="emit('cancel')"
            >
                Hủy
            </Button>
        </div>
    </form>
</template>
