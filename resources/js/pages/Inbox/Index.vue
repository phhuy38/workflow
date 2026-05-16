<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import {
    Clock,
    AlertCircle,
    CheckCircle2,
    Inbox,
    ChevronDown,
    ChevronUp,
    Check,
} from 'lucide-vue-next';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

interface InboxTask {
    id: number;
    instance_id: number;
    name: string;
    process_name: string;
    template_name: string;
    status: string;
    urgency_status: 'overdue' | 'due_soon' | 'in_progress' | 'pending';
    deadline_at: string;
    created_at: string;
    launched_by_name: string;
}

const props = defineProps<{
    tasks: InboxTask[];
}>();

watch(
    () => props.tasks,
    (newTasks) => {
        if (
            expandedNoteTaskId.value &&
            !newTasks.find((t) => t.id === expandedNoteTaskId.value)
        ) {
            expandedNoteTaskId.value = null;
        }
    },
    { deep: true },
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inbox', href: '/inbox' }],
    },
});

const page = usePage();
const authUser = page.props.auth?.user as { id: number } | null;

const isOffline = ref(false);

const reloadTasks = () => {
    router.reload({
        only: ['tasks'],
        preserveScroll: true,
        preserveState: true,
    });
};

const handleConnected = () => {
    isOffline.value = false;
    reloadTasks();
};

const handleDisconnected = () => {
    isOffline.value = true;
};

onMounted(() => {
    if (authUser && window.Echo) {
        window.Echo.private(`user.${authUser.id}`)
            .listen('StepExecutionUpdated', reloadTasks)
            .error((error: any) => {
                console.error('Echo subscription error:', error);
            });

        if (window.Echo.connector?.pusher?.connection) {
            window.Echo.connector.pusher.connection.bind(
                'connected',
                handleConnected,
            );
            window.Echo.connector.pusher.connection.bind(
                'disconnected',
                handleDisconnected,
            );
        }
    }
});

onUnmounted(() => {
    if (authUser && window.Echo) {
        window.Echo.leave(`user.${authUser.id}`);

        if (window.Echo.connector?.pusher?.connection) {
            window.Echo.connector.pusher.connection.unbind(
                'connected',
                handleConnected,
            );
            window.Echo.connector.pusher.connection.unbind(
                'disconnected',
                handleDisconnected,
            );
        }
    }
});

function getUrgencyVariant(status: string) {
    switch (status) {
        case 'overdue':
            return 'destructive';
        case 'due_soon':
            return 'default';
        case 'in_progress':
            return 'success';
        case 'pending':
        default:
            return 'secondary';
    }
}

function getUrgencyText(status: string) {
    switch (status) {
        case 'overdue':
            return 'Quá hạn';
        case 'due_soon':
            return 'Sắp tới hạn';
        case 'in_progress':
            return 'Đang thực hiện';
        case 'pending':
        default:
            return 'Chưa nhận việc';
    }
}

function formatDate(isoString: string): string {
    if (!isoString) return 'N/A';
    return new Date(isoString).toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Quick Complete Logic (Story 5.3)
const expandedNoteTaskId = ref<number | null>(null);
const notesInput = ref<{ [key: number]: string }>({});
const completeForm = useForm({ completion_notes: '' });
const completingTaskId = ref<number | null>(null);

function toggleNote(taskId: number) {
    if (expandedNoteTaskId.value === taskId) {
        expandedNoteTaskId.value = null;
    } else {
        expandedNoteTaskId.value = taskId;
    }
}

function quickComplete(taskId: number) {
    if (completeForm.processing) return;

    completingTaskId.value = taskId;
    completeForm.completion_notes = notesInput.value[taskId] || '';
    completeForm.post(`/step-executions/${taskId}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            expandedNoteTaskId.value = null;
            swipedTaskId.value = null;
            notesInput.value[taskId] = '';
            completingTaskId.value = null;
            completeForm.reset();
        },
        onError: () => {
            completingTaskId.value = null;
        },
    });
}

// Swipe to complete logic for mobile
const swipedTaskId = ref<number | null>(null);
let touchStartX = 0;
let touchStartY = 0;

function handleTouchStart(e: TouchEvent, taskId: number) {
    touchStartX = e.changedTouches[0].screenX;
    touchStartY = e.changedTouches[0].screenY;
}

function handleTouchMove(e: TouchEvent, taskId: number) {
    if (swipedTaskId.value && swipedTaskId.value !== taskId) return;

    const currentX = e.changedTouches[0].screenX;
    const currentY = e.changedTouches[0].screenY;
    const diffX = touchStartX - currentX;
    const diffY = Math.abs(touchStartY - currentY);

    // Ensure horizontal swipe
    if (diffY < 30 && diffX > 40) {
        swipedTaskId.value = taskId;
    } else if (diffX < -40) {
        if (swipedTaskId.value === taskId) {
            swipedTaskId.value = null;
        }
    }
}
</script>

<template>
    <Head title="Inbox - Công việc của tôi" />

    <div
        v-if="isOffline"
        class="sticky top-0 z-50 bg-destructive/10 p-2 text-center text-sm text-destructive"
    >
        Đang mất kết nối mạng. Đang cố gắng kết nối lại...
    </div>

    <div
        class="mx-auto flex w-full max-w-5xl flex-col gap-6 overflow-hidden p-4 md:p-8"
    >
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Inbox của tôi
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Danh sách các công việc bạn đang được giao xử lý.
                </p>
            </div>
            <div
                class="rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary"
            >
                {{ tasks.length }} công việc
            </div>
        </div>

        <div
            v-if="tasks.length === 0"
            class="flex h-full min-h-[400px] flex-col items-center justify-center gap-6 rounded-xl border-2 border-dashed p-8 text-center transition-all duration-500 fade-in zoom-in"
        >
            <div class="rounded-full bg-muted p-4">
                <Inbox class="h-12 w-12 text-muted-foreground" />
            </div>
            <div class="flex flex-col gap-2">
                <h2 class="text-xl font-semibold">
                    Bạn không có task nào đang chờ.
                </h2>
                <p class="max-w-md text-muted-foreground">
                    Tốt lắm! 🎉 Hãy tận hưởng thời gian nghỉ ngơi của mình.
                </p>
            </div>
        </div>

        <TransitionGroup
            name="list"
            tag="div"
            class="flex w-full flex-col gap-4"
        >
            <div
                v-for="task in tasks"
                :key="task.id"
                class="relative w-full"
                style="touch-action: pan-y"
                @touchstart="(e) => handleTouchStart(e, task.id)"
                @touchmove="(e) => handleTouchMove(e, task.id)"
            >
                <!-- Swipe Action Background -->
                <div
                    class="absolute inset-0 z-0 flex items-center justify-end rounded-xl bg-green-500 px-6"
                    :class="{
                        'pointer-events-none': completingTaskId === task.id,
                    }"
                >
                    <span
                        class="flex items-center gap-2 font-medium text-white"
                    >
                        <Check class="h-5 w-5" /> Hoàn thành
                    </span>
                </div>

                <!-- Main Task Card -->
                <Card
                    class="relative z-10 w-full overflow-hidden shadow-sm transition-all duration-300 hover:shadow-md"
                    :class="{
                        'border-red-500/50 bg-red-50/95 dark:bg-red-950/20':
                            task.urgency_status === 'overdue',
                        'border-yellow-500/50 bg-yellow-50/95 dark:bg-yellow-950/20':
                            task.urgency_status === 'due_soon',
                        'border-primary/50 bg-background':
                            task.status === 'in_progress',
                        'bg-background': task.status === 'pending',
                        '-translate-x-24': swipedTaskId === task.id,
                        'pointer-events-none opacity-50':
                            completingTaskId === task.id,
                    }"
                >
                    <!-- Urgency Left Border indicator for desktop -->
                    <div
                        class="absolute top-0 bottom-0 left-0 hidden w-1 md:block"
                        :class="{
                            'bg-red-500': task.urgency_status === 'overdue',
                            'bg-yellow-500': task.urgency_status === 'due_soon',
                            'bg-green-500':
                                task.urgency_status === 'in_progress',
                            'bg-gray-300 dark:bg-gray-600':
                                task.urgency_status === 'pending',
                        }"
                    ></div>

                    <div
                        class="flex flex-col justify-between gap-4 p-4 md:flex-row md:items-start"
                    >
                        <div class="min-w-0 flex-1">
                            <div
                                class="mb-2 flex items-start justify-between md:items-center"
                            >
                                <Badge
                                    :variant="
                                        getUrgencyVariant(task.urgency_status)
                                    "
                                    class="mb-2 shrink-0 text-xs md:mb-0"
                                    :class="{
                                        'bg-yellow-500 text-white hover:bg-yellow-600':
                                            task.urgency_status === 'due_soon',
                                        'bg-green-500 text-white hover:bg-green-600':
                                            task.urgency_status ===
                                            'in_progress',
                                    }"
                                >
                                    <AlertCircle
                                        v-if="task.urgency_status === 'overdue'"
                                        class="mr-1 h-3 w-3"
                                    />
                                    <Clock
                                        v-else-if="
                                            task.urgency_status === 'due_soon'
                                        "
                                        class="mr-1 h-3 w-3"
                                    />
                                    <CheckCircle2
                                        v-else-if="
                                            task.urgency_status ===
                                            'in_progress'
                                        "
                                        class="mr-1 h-3 w-3"
                                    />
                                    {{ getUrgencyText(task.urgency_status) }}
                                </Badge>
                                <span
                                    class="ml-4 text-xs whitespace-nowrap text-muted-foreground md:hidden"
                                    >Hạn:
                                    {{ formatDate(task.deadline_at) }}</span
                                >
                            </div>

                            <h3
                                class="truncate text-lg font-semibold"
                                :title="task.name"
                            >
                                <Link
                                    :href="`/process-instances/${task.instance_id}`"
                                    class="hover:underline"
                                    >{{ task.name }}</Link
                                >
                            </h3>

                            <div
                                class="mt-1 flex flex-col gap-1 text-sm text-muted-foreground sm:flex-row sm:items-center sm:gap-4"
                            >
                                <div class="flex items-center gap-1.5 truncate">
                                    <span
                                        class="max-w-[150px] truncate font-medium sm:max-w-none"
                                        :title="task.process_name"
                                        >{{ task.process_name }}</span
                                    >
                                </div>
                                <span class="hidden sm:inline">&bull;</span>
                                <div class="truncate">
                                    Từ: {{ task.launched_by_name }}
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-2 flex items-center justify-between gap-3 border-t border-border pt-3 md:mt-0 md:justify-end md:border-t-0 md:pt-0"
                        >
                            <div class="mr-4 hidden flex-col items-end md:flex">
                                <span
                                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                                    >Hạn chót</span
                                >
                                <span
                                    class="text-sm font-medium"
                                    :class="{
                                        'font-bold text-red-600 dark:text-red-400':
                                            task.urgency_status === 'overdue',
                                    }"
                                >
                                    {{ formatDate(task.deadline_at) }}
                                </span>
                            </div>

                            <!-- Action Buttons -->
                            <div
                                class="flex w-full items-center gap-2 md:w-auto"
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="flex-1 md:flex-none"
                                    @click="toggleNote(task.id)"
                                >
                                    Ghi chú
                                    <ChevronDown
                                        v-if="expandedNoteTaskId !== task.id"
                                        class="ml-1 h-4 w-4"
                                    />
                                    <ChevronUp v-else class="ml-1 h-4 w-4" />
                                </Button>
                                <Button
                                    size="sm"
                                    variant="default"
                                    class="flex-1 bg-green-600 text-white hover:bg-green-700 md:flex-none"
                                    @click="quickComplete(task.id)"
                                    :disabled="completingTaskId === task.id"
                                >
                                    <Check
                                        class="mr-1 hidden h-4 w-4 sm:inline-block"
                                    />
                                    Hoàn thành
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Progressive Disclosure Note Input -->
                    <div
                        v-if="expandedNoteTaskId === task.id"
                        class="border-t bg-muted/20 px-4 pt-3 pb-4 transition-all duration-300"
                    >
                        <label class="mb-1 block text-sm font-medium"
                            >Ghi chú hoàn thành (tùy chọn)</label
                        >
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Textarea
                                v-model="notesInput[task.id]"
                                placeholder="Nhập ghi chú cho công việc này..."
                                rows="2"
                                class="w-full text-sm"
                            />
                            <Button
                                size="default"
                                class="shrink-0 bg-green-600 text-white hover:bg-green-700 sm:self-end"
                                @click="quickComplete(task.id)"
                                :disabled="completingTaskId === task.id"
                            >
                                Gửi & Hoàn thành
                            </Button>
                        </div>
                    </div>
                </Card>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
/* Transition Group Animations for Inbox Zero metaphor */
.list-move,
.list-enter-active,
.list-leave-active {
    transition: all 0.5s cubic-bezier(0.55, 0, 0.1, 1);
}

.list-enter-from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(100px) scale(0.95);
}

.list-leave-active {
    position: absolute;
}
</style>
