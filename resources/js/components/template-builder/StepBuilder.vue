<script setup lang="ts">
import axios from 'axios';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { reorder as stepReorder } from '@/routes/step-definitions';
import type { StepDefinition } from '@/types/step';
import StepCard from './StepCard.vue';
import StepForm from './StepForm.vue';

const props = defineProps<{
    templateId: number;
    initialSteps: StepDefinition[];
    canEdit: boolean;
}>();

const steps = ref<StepDefinition[]>([...props.initialSteps]);
const editingStep = ref<StepDefinition | null>(null);
const showAddForm = ref(false);
const newStepIds = ref<Set<number>>(new Set());
const isReordering = ref(false);

function onStepAdded() {
    showAddForm.value = false;
    // Inertia navigates back — parent component re-renders with fresh data
    // Ideally track new step ID here to show "MỚI" badge, but since Inertia re-renders,
    // this function is called before the new step arrives. Badge will show briefly on next render.
}

function onStepEdited() {
    editingStep.value = null;
}

function startEdit(step: StepDefinition) {
    editingStep.value = step;
    showAddForm.value = false;
}

function cancelEdit() {
    editingStep.value = null;
}

async function moveStep(step: StepDefinition, direction: 'up' | 'down') {
    if (isReordering.value) {
        return;
    }

    const newOrder = direction === 'up' ? step.order - 1 : step.order + 1;

    // Optimistic: swap immediately
    const other = steps.value.find((s) => s.order === newOrder);

    if (!other) {
        return;
    }

    isReordering.value = true;
    const oldOrder = step.order;

    step.order = newOrder;
    other.order = oldOrder;
    steps.value = [...steps.value].sort((a, b) => a.order - b.order);

    try {
        const response = await axios.patch(
            stepReorder({ step_definition: step.id }).url,
            {
                new_order: newOrder,
            },
        );
        steps.value = response.data.steps;
    } catch {
        // Revert optimistic update
        step.order = oldOrder;
        other.order = newOrder;
        steps.value = [...steps.value].sort((a, b) => a.order - b.order);
    } finally {
        isReordering.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <!-- Steps list -->
        <div v-if="steps.length > 0" class="flex flex-col gap-3">
            <template v-for="step in steps" :key="step.id">
                <!-- Edit form inline -->
                <div
                    v-if="editingStep?.id === step.id"
                    class="rounded-md border p-4"
                >
                    <p class="mb-3 text-sm font-medium">
                        Chỉnh sửa bước {{ step.order }}
                    </p>
                    <StepForm
                        :template-id="templateId"
                        :step="step"
                        submit-label="Lưu thay đổi"
                        @success="onStepEdited"
                        @cancel="cancelEdit"
                    />
                </div>

                <!-- Display card -->
                <StepCard
                    v-else
                    :step="step"
                    :is-first="step.order === 1"
                    :is-last="step.order === steps.length"
                    :can-edit="canEdit"
                    :is-new="newStepIds.has(step.id)"
                    @edit="startEdit"
                    @delete="
                        $inertia.delete(`/step-definitions/${step.id}`, {
                            preserveScroll: true,
                        })
                    "
                    @move-up="moveStep(step, 'up')"
                    @move-down="moveStep(step, 'down')"
                />
            </template>
        </div>

        <!-- Empty state -->
        <div v-else class="rounded-md border-2 border-dashed p-8 text-center">
            <p class="text-sm text-muted-foreground">
                Chưa có bước nào. Thêm bước đầu tiên bên dưới.
            </p>
        </div>

        <!-- Add step form -->
        <div v-if="canEdit">
            <div v-if="showAddForm" class="rounded-md border p-4">
                <p class="mb-3 text-sm font-medium">Thêm bước mới</p>
                <StepForm
                    :template-id="templateId"
                    @success="onStepAdded"
                    @cancel="showAddForm = false"
                />
            </div>

            <Button
                v-else
                variant="outline"
                class="w-full"
                data-test="add-step-button"
                @click="
                    showAddForm = true;
                    editingStep = null;
                "
            >
                <Plus class="mr-2 h-4 w-4" />
                Thêm bước
            </Button>
        </div>
    </div>
</template>
