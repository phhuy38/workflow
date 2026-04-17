<script setup lang="ts">
import { ChevronDown, ChevronUp, Pencil, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { StepDefinition } from '@/types/step';

defineProps<{
    step: StepDefinition;
    isFirst: boolean;
    isLast: boolean;
    canEdit: boolean;
    isNew?: boolean;
}>();

const emit = defineEmits<{
    edit: [step: StepDefinition];
    delete: [step: StepDefinition];
    moveUp: [step: StepDefinition];
    moveDown: [step: StepDefinition];
}>();

function assigneeLabel(step: StepDefinition): string {
    if (!step.assignee_type) {
return 'Chưa gán';
}

    const typeMap: Record<string, string> = {
        user: 'Người dùng',
        role: 'Vai trò',
        department: 'Phòng ban',
    };

    return typeMap[step.assignee_type] ?? step.assignee_type;
}
</script>

<template>
    <Card
        class="relative transition-shadow"
        :class="{ 'ring-2 ring-primary': isNew }"
        :data-test="`step-card-${step.id}`"
    >
        <CardHeader class="pb-2">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold">
                        {{ step.order }}
                    </span>
                    <div>
                        <p class="font-semibold leading-tight">{{ step.name }}</p>
                        <p v-if="step.description" class="text-muted-foreground mt-0.5 text-xs">
                            {{ step.description }}
                        </p>
                    </div>
                </div>
                <Badge v-if="isNew" variant="default" class="shrink-0 text-xs">MỚI</Badge>
            </div>
        </CardHeader>

        <CardContent class="pt-0">
            <div class="text-muted-foreground flex flex-wrap gap-3 text-xs">
                <span>
                    <span class="font-medium">Người phụ trách:</span> {{ assigneeLabel(step) }}
                </span>
                <span>
                    <span class="font-medium">Thời hạn:</span> {{ step.duration_hours }}h
                </span>
                <Badge v-if="step.is_required" variant="outline" class="text-xs">Bắt buộc</Badge>
            </div>

            <div v-if="canEdit" class="mt-3 flex gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7"
                    :disabled="isFirst"
                    data-test="move-up-button"
                    title="Di chuyển lên"
                    @click="emit('moveUp', step)"
                >
                    <ChevronUp class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7"
                    :disabled="isLast"
                    data-test="move-down-button"
                    title="Di chuyển xuống"
                    @click="emit('moveDown', step)"
                >
                    <ChevronDown class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7"
                    data-test="edit-button"
                    title="Chỉnh sửa"
                    @click="emit('edit', step)"
                >
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 text-destructive hover:text-destructive"
                    data-test="delete-button"
                    title="Xóa bước"
                    @click="emit('delete', step)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
