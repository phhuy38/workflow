<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import {
    AlertCircle,
    Clock,
    CheckCircle2,
    ChevronRight,
    Activity,
    Search,
    SkipForward,
} from 'lucide-vue-next';

interface Instance {
    id: number;
    name: string;
    template_name: string;
    status: string;
    progress: number;
    launched_at: string;
    creator_name: string;
    current_step: string;
    traffic_light_status: 'critical' | 'warning' | 'normal';
    time_elapsed: string;
    estimated_remaining_hours: number;
}

interface Step {
    id: number;
    name: string;
    description: string | null;
    order: number;
    status: string;
    assigned_to: number;
    assignee_name: string;
    deadline_at: string;
    completed_at: string;
    finisher_name: string;
    completion_notes: string | null;
    messages: any[];
}

interface Props {
    instance: Instance;
    steps: Step[];
    activities?: any[];
    can: {
        cancel: boolean;
        override: boolean;
        view_full_log: boolean;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Instances', href: '/process-instances' },
            { title: 'Chi tiết quy trình', href: '#' },
        ],
    },
});

const page = usePage();
const authUser = page.props.auth?.user as { id: number } | null;

const isHeaderCollapsed = ref(false);

const handleScroll = () => {
    isHeaderCollapsed.value = window.scrollY > 80;
};

const reloadData = useDebounceFn(() => {
    router.reload({ only: ['instance', 'steps', 'activities'] });
}, 300);

onMounted(() => {
    window.addEventListener('scroll', handleScroll);

    if (window.Echo) {
        window.Echo.private(`instance.${props.instance.id}`)
            .listen('ProcessInstanceUpdated', reloadData)
            .listen('StepExecutionUpdated', reloadData);

        // Reconnect handling (missed events recovery)
        window.Echo.connector.pusher.connection.bind('connected', reloadData);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);

    if (window.Echo) {
        window.Echo.leave(`instance.${props.instance.id}`);
        window.Echo.connector.pusher.connection.unbind('connected', reloadData);
    }
});

const showNotesForm = ref<number | null>(null);
const notesInput = ref<{ [key: number]: string }>({});
const completeForm = useForm({ completion_notes: '' });

const showOverrideForm = ref<number | null>(null);
const overrideInput = ref<{ [key: number]: string }>({});
const overrideForm = useForm({ reason: '' });

const showPingForm = ref<number | null>(null);
const pingInput = ref<{ [key: number]: string }>({});
const pingForm = useForm({ body: '' });

const showReplyForm = ref<number | null>(null);
const replyInput = ref<{ [key: number]: string }>({});
const replyForm = useForm({ body: '' });

function submitPing(stepId: number) {
    pingForm.body = pingInput.value[stepId] || '';
    pingForm.post(`/step-executions/${stepId}/messages`, {
        preserveScroll: true,
        onSuccess: () => {
            showPingForm.value = null;
            pingInput.value[stepId] = '';
        },
    });
}

function submitReply(stepId: number) {
    replyForm.body = replyInput.value[stepId] || '';
    replyForm.post(`/step-executions/${stepId}/messages`, {
        preserveScroll: true,
        onSuccess: () => {
            showReplyForm.value = null;
            replyInput.value[stepId] = '';
        },
    });
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'running':
        case 'in_progress':
            return 'default';
        case 'completed':
            return 'success';
        case 'cancelled':
        case 'escalated':
            return 'destructive';
        case 'paused':
        case 'blocked':
            return 'warning';
        default:
            return 'secondary';
    }
}

function isOverdue(deadlineString: string) {
    if (!deadlineString) return false;
    const deadline = new Date(deadlineString).getTime();
    const now = new Date().getTime();
    return now > deadline;
}

function getTimelineColor(step: Step) {
    if (
        ['pending', 'in_progress'].includes(step.status) &&
        isOverdue(step.deadline_at)
    ) {
        return 'bg-red-500';
    }

    switch (step.status) {
        case 'completed':
            return 'bg-green-500';
        case 'in_progress':
            return 'bg-yellow-500';
        case 'skipped':
            return 'bg-gray-400';
        case 'cancelled':
        case 'escalated':
            return 'bg-red-500';
        case 'pending':
        default:
            return 'bg-gray-200 dark:bg-gray-700';
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

function acknowledge(stepId: number) {
    useForm({}).post(`/step-executions/${stepId}/acknowledge`, {
        preserveScroll: true,
    });
}

function submitComplete(stepId: number) {
    completeForm.completion_notes = notesInput.value[stepId] || '';
    completeForm.post(`/step-executions/${stepId}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            showNotesForm.value = null;
            notesInput.value[stepId] = '';
            completeForm.reset();
        },
    });
}

function submitOverride(stepId: number) {
    overrideForm.reason = overrideInput.value[stepId] || '';
    overrideForm.post(`/step-executions/${stepId}/escalate`, {
        preserveScroll: true,
        onSuccess: () => {
            showOverrideForm.value = null;
            overrideInput.value[stepId] = '';
            overrideForm.reset();
        },
    });
}

function getFlash(key: string): string | null {
    const flash = page.props.flash as Record<string, unknown> | undefined;
    if (!flash || typeof flash[key] !== 'string') return null;
    return flash[key];
}
</script>

<template>
    <Head :title="`Chi tiết: ${instance.name}`" />

    <div class="flex h-full w-full flex-col">
        <!-- Sticky Header (UX-DR10) -->
        <div
            class="sticky top-0 z-40 border-b bg-background/95 p-6 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div class="mx-auto flex max-w-7xl flex-col gap-4">
                <div
                    class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
                >
                    <div>
                        <h1 class="text-2xl font-semibold">
                            {{ instance.name }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ instance.template_name }} &bull; Khởi tạo bởi
                            {{ instance.creator_name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge
                            :variant="
                                instance.traffic_light_status === 'critical'
                                    ? 'destructive'
                                    : instance.traffic_light_status ===
                                        'warning'
                                      ? 'default'
                                      : 'outline'
                            "
                            :class="{
                                'bg-yellow-500 text-white hover:bg-yellow-600':
                                    instance.traffic_light_status === 'warning',
                            }"
                            class="px-3 py-1 text-sm"
                        >
                            <AlertCircle
                                v-if="
                                    instance.traffic_light_status === 'critical'
                                "
                                class="mr-1.5 h-4 w-4"
                            />
                            <Clock
                                v-else-if="
                                    instance.traffic_light_status === 'warning'
                                "
                                class="mr-1.5 h-4 w-4"
                            />
                            <CheckCircle2
                                v-else
                                class="mr-1.5 h-4 w-4 text-green-600"
                            />
                            {{
                                instance.traffic_light_status === 'critical'
                                    ? 'Quá hạn'
                                    : instance.traffic_light_status ===
                                        'warning'
                                      ? 'Cần chú ý'
                                      : 'Bình thường'
                            }}
                        </Badge>
                        <Badge
                            :variant="getStatusVariant(instance.status)"
                            class="px-3 py-1 text-sm"
                        >
                            {{ instance.status }}
                        </Badge>
                    </div>
                </div>

                <div
                    class="flex flex-col items-center gap-6 text-sm md:flex-row"
                >
                    <div class="w-full flex-1">
                        <div class="mb-1 flex justify-between">
                            <span class="font-medium">Tiến độ quy trình</span>
                            <span class="font-bold"
                                >{{ instance.progress }}%</span
                            >
                        </div>
                        <div
                            class="h-2.5 w-full overflow-hidden rounded-full bg-secondary"
                        >
                            <div
                                class="h-full transition-all duration-500"
                                :class="{
                                    'bg-red-500':
                                        instance.traffic_light_status ===
                                        'critical',
                                    'bg-yellow-500':
                                        instance.traffic_light_status ===
                                        'warning',
                                    'bg-green-500':
                                        instance.traffic_light_status ===
                                        'normal',
                                }"
                                :style="{ width: instance.progress + '%' }"
                            ></div>
                        </div>
                    </div>
                    <div class="flex gap-8 pt-2 whitespace-nowrap md:pt-0">
                        <div class="flex flex-col">
                            <span class="text-xs text-muted-foreground"
                                >Thời gian đã chạy</span
                            >
                            <span class="font-semibold">{{
                                instance.time_elapsed
                            }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-muted-foreground"
                                >Ước tính còn lại</span
                            >
                            <span class="font-semibold">{{
                                instance.estimated_remaining_hours > 0
                                    ? instance.estimated_remaining_hours +
                                      ' giờ'
                                    : 'Đã hoàn thành'
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Process Context Flow Ribbon (Story 5.2) -->
                <div
                    class="scrollbar-thin mt-4 flex gap-2 overflow-x-auto border-t pt-4 pb-2"
                >
                    <div
                        v-for="(step, index) in steps"
                        :key="'ribbon-' + step.id"
                        class="flex items-center whitespace-nowrap"
                    >
                        <div
                            class="flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                            :class="{
                                'border-green-200 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400':
                                    step.status === 'completed',
                                'border-yellow-200 bg-yellow-100 text-yellow-800 ring-2 ring-yellow-400/50 dark:border-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400':
                                    step.status === 'in_progress',
                                'border-red-200 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400':
                                    ['pending', 'in_progress'].includes(
                                        step.status,
                                    ) && isOverdue(step.deadline_at),
                                'border-border bg-muted text-muted-foreground':
                                    step.status === 'pending' ||
                                    step.status === 'skipped',
                                'border-red-200 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400':
                                    step.status === 'cancelled' ||
                                    step.status === 'escalated',
                            }"
                        >
                            <span>B.{{ step.order }}</span>
                            <span
                                class="max-w-[120px] truncate"
                                :title="step.name"
                                >{{ step.name }}</span
                            >
                            <CheckCircle2
                                v-if="step.status === 'completed'"
                                class="h-3 w-3"
                            />
                            <Clock
                                v-else-if="step.status === 'pending'"
                                class="h-3 w-3"
                            />
                            <Activity
                                v-else-if="step.status === 'in_progress'"
                                class="h-3 w-3"
                            />
                            <AlertCircle
                                v-else-if="
                                    ['cancelled', 'escalated'].includes(
                                        step.status,
                                    )
                                "
                                class="h-3 w-3"
                            />
                            <SkipForward
                                v-else-if="step.status === 'skipped'"
                                class="h-3 w-3"
                            />
                        </div>
                        <ChevronRight
                            v-if="index < steps.length - 1"
                            class="mx-1 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div
            class="mx-auto flex w-full max-w-7xl flex-col gap-8 p-4 pb-24 md:p-6 md:pb-6"
        >
            <!-- Flash success message -->
            <div
                v-if="getFlash('success')"
                class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700 shadow-sm"
            >
                {{ getFlash('success') }}
            </div>

            <!-- Visual Timeline -->
            <div>
                <h2 class="mb-6 text-xl font-semibold">
                    Tiến trình thực hiện (Timeline)
                </h2>
                <div class="relative">
                    <div
                        class="absolute top-4 bottom-0 left-[15px] w-0.5 -translate-x-1/2 bg-border md:left-1/2"
                    ></div>

                    <div
                        v-for="(step, index) in steps"
                        :key="step.id"
                        class="group relative mb-8 flex flex-col gap-6 md:flex-row"
                    >
                        <!-- Mobile connector line -->
                        <div
                            class="absolute top-8 bottom-[-2rem] left-[15px] w-0.5 bg-border md:hidden"
                            v-if="index !== steps.length - 1"
                        ></div>

                        <!-- Left side (Time / Status for desktop) -->
                        <div
                            class="flex items-start pl-12 md:w-1/2 md:justify-end md:pt-1 md:pr-12 md:pl-0"
                        >
                            <div class="flex flex-col items-start md:items-end">
                                <span class="text-sm font-medium">{{
                                    formatDate(step.deadline_at)
                                }}</span>
                                <span
                                    class="text-left text-xs text-muted-foreground md:text-right"
                                    >Hạn chót</span
                                >
                            </div>
                        </div>

                        <!-- Center Node -->
                        <div
                            class="absolute left-0 z-10 flex h-8 w-8 -translate-x-1/2 items-center justify-center rounded-full border-4 border-background md:left-1/2"
                            :class="[
                                getTimelineColor(step.status),
                                step.status === 'in_progress'
                                    ? 'ring-4 ring-yellow-500/20'
                                    : '',
                            ]"
                        >
                            <span class="text-[10px] font-bold text-white">{{
                                step.order
                            }}</span>
                        </div>

                        <!-- Right side (Card) -->
                        <div
                            class="flex items-start justify-start pl-12 md:w-1/2 md:pr-0 md:pl-12"
                        >
                            <Card
                                class="w-full shadow-sm"
                                :class="{
                                    'ring-2 ring-primary':
                                        step.status === 'in_progress',
                                }"
                            >
                                <CardHeader class="p-4 pb-2">
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <CardTitle class="text-base">{{
                                            step.name
                                        }}</CardTitle>
                                        <Badge
                                            :variant="
                                                getStatusVariant(step.status)
                                            "
                                            >{{ step.status }}</Badge
                                        >
                                    </div>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        Phụ trách:
                                        <strong>{{
                                            step.assignee_name || 'N/A'
                                        }}</strong>
                                    </p>
                                    <p
                                        v-if="step.description"
                                        class="mt-2 text-sm text-foreground"
                                    >
                                        {{ step.description }}
                                    </p>
                                </CardHeader>

                                <CardContent class="p-4 pt-2">
                                    <!-- Interaction for Executor -->
                                    <div
                                        v-if="
                                            authUser &&
                                            step.assigned_to === authUser.id &&
                                            !step.completed_at &&
                                            step.status !== 'skipped' &&
                                            step.status !== 'cancelled'
                                        "
                                    >
                                        <div
                                            class="fixed right-0 bottom-0 left-0 z-50 border-t bg-background p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] md:static md:z-auto md:border-none md:bg-transparent md:p-0 md:shadow-none"
                                        >
                                            <div
                                                v-if="step.status === 'pending'"
                                                class="w-full"
                                            >
                                                <Button
                                                    size="sm"
                                                    class="w-full md:w-auto"
                                                    @click="
                                                        acknowledge(step.id)
                                                    "
                                                    >Xác nhận nhận việc</Button
                                                >
                                            </div>
                                            <div
                                                v-else-if="
                                                    step.status ===
                                                    'in_progress'
                                                "
                                                class="mt-2 flex w-full flex-col gap-2 md:mt-0"
                                            >
                                                <div
                                                    v-if="
                                                        showNotesForm !==
                                                        step.id
                                                    "
                                                >
                                                    <Button
                                                        size="sm"
                                                        variant="default"
                                                        class="w-full bg-green-600 text-white hover:bg-green-700 md:w-auto"
                                                        @click="
                                                            showNotesForm =
                                                                step.id
                                                        "
                                                        >Hoàn thành bước</Button
                                                    >
                                                </div>
                                                <div
                                                    v-else
                                                    class="flex flex-col gap-3 rounded-md border bg-muted/30 p-3"
                                                >
                                                    <Label
                                                        class="text-sm font-medium"
                                                        >Ghi chú (tùy
                                                        chọn)</Label
                                                    >
                                                    <Textarea
                                                        v-model="
                                                            notesInput[step.id]
                                                        "
                                                        rows="2"
                                                        placeholder="Nhập kết quả công việc..."
                                                    />
                                                    <p
                                                        v-if="
                                                            completeForm.errors
                                                                .completion_notes
                                                        "
                                                        class="text-xs text-destructive"
                                                    >
                                                        {{
                                                            completeForm.errors
                                                                .completion_notes
                                                        }}
                                                    </p>
                                                    <div class="flex gap-2">
                                                        <Button
                                                            size="sm"
                                                            class="flex-1 md:flex-none"
                                                            variant="ghost"
                                                            @click="
                                                                showNotesForm =
                                                                    null
                                                            "
                                                            >Hủy</Button
                                                        >
                                                        <Button
                                                            size="sm"
                                                            class="flex-1 md:flex-none"
                                                            :disabled="
                                                                completeForm.processing
                                                            "
                                                            @click="
                                                                submitComplete(
                                                                    step.id,
                                                                )
                                                            "
                                                            >Gửi xác
                                                            nhận</Button
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Interaction for Manager or Beneficiary (Ping / Override) -->
                                    <div
                                        v-if="
                                            (can.ping || can.override) &&
                                            ['pending', 'in_progress'].includes(
                                                step.status,
                                            )
                                        "
                                        class="mt-4 flex gap-2"
                                    >
                                        <div class="flex w-full flex-col gap-2">
                                            <div class="flex gap-2">
                                                <Button
                                                    v-if="
                                                        can.ping &&
                                                        showPingForm !== step.id
                                                    "
                                                    size="sm"
                                                    variant="outline"
                                                    class="h-8 text-xs"
                                                    @click="
                                                        showPingForm = step.id
                                                    "
                                                >
                                                    {{
                                                        authUser?.id ===
                                                        instance.created_for
                                                            ? 'Liên hệ người phụ trách'
                                                            : 'Nhắc việc'
                                                    }}
                                                </Button>
                                                <Button
                                                    v-if="
                                                        can.override &&
                                                        showOverrideForm !==
                                                            step.id
                                                    "
                                                    size="sm"
                                                    variant="outline"
                                                    class="h-8 border-warning text-xs text-warning"
                                                    @click="
                                                        showOverrideForm =
                                                            step.id
                                                    "
                                                    >Cưỡng chế</Button
                                                >
                                            </div>

                                            <div
                                                v-if="showPingForm === step.id"
                                                class="mt-2 flex flex-col gap-3 rounded-md border border-primary/20 bg-primary/5 p-3"
                                            >
                                                <Label
                                                    class="text-sm font-medium"
                                                    >Nhắn tin cho người thực
                                                    hiện:</Label
                                                >
                                                <Textarea
                                                    v-model="pingInput[step.id]"
                                                    rows="2"
                                                    placeholder="Nhập nội dung tin nhắn..."
                                                />
                                                <p
                                                    v-if="
                                                        pingInput[
                                                            step.id
                                                        ]?.trim() === ''
                                                    "
                                                    class="text-xs text-muted-foreground italic"
                                                >
                                                    Vui lòng nhập nội dung trước
                                                    khi gửi.
                                                </p>
                                                <div class="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        @click="
                                                            showPingForm = null
                                                        "
                                                        >Hủy</Button
                                                    >
                                                    <Button
                                                        size="sm"
                                                        :disabled="
                                                            !pingInput[
                                                                step.id
                                                            ]?.trim() ||
                                                            pingForm.processing
                                                        "
                                                        @click="
                                                            submitPing(step.id)
                                                        "
                                                        >Gửi tin nhắn</Button
                                                    >
                                                </div>
                                            </div>

                                            <div
                                                v-if="
                                                    can.override &&
                                                    showOverrideForm === step.id
                                                "
                                                class="mt-2 flex flex-col gap-3 rounded-md border border-warning/50 bg-warning/5 p-3"
                                            >
                                                <Label
                                                    class="text-sm font-medium text-warning"
                                                    >Lý do override:</Label
                                                >
                                                <Textarea
                                                    v-model="
                                                        overrideInput[step.id]
                                                    "
                                                    rows="2"
                                                />
                                                <p
                                                    v-if="
                                                        overrideForm.errors
                                                            .reason
                                                    "
                                                    class="text-xs text-destructive"
                                                >
                                                    {{
                                                        overrideForm.errors
                                                            .reason
                                                    }}
                                                </p>
                                                <div class="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        @click="
                                                            showOverrideForm =
                                                                null
                                                        "
                                                        >Hủy</Button
                                                    >
                                                    <Button
                                                        size="sm"
                                                        variant="warning"
                                                        :disabled="
                                                            overrideForm.processing
                                                        "
                                                        @click="
                                                            submitOverride(
                                                                step.id,
                                                            )
                                                        "
                                                        >Xác nhận</Button
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Thread -->
                                    <div
                                        v-if="
                                            step.messages &&
                                            step.messages.length > 0
                                        "
                                        class="mt-4 space-y-3 border-t pt-3"
                                    >
                                        <h4 class="text-sm font-semibold">
                                            Trao đổi ({{
                                                step.messages.length
                                            }})
                                        </h4>
                                        <div
                                            v-for="msg in step.messages"
                                            :key="msg.id"
                                            class="flex flex-col gap-1 rounded-md p-3 text-sm"
                                            :class="
                                                msg.is_manager
                                                    ? 'ml-4 bg-primary/10'
                                                    : 'mr-4 bg-muted/50'
                                            "
                                        >
                                            <div class="flex justify-between">
                                                <span
                                                    class="text-xs font-medium"
                                                    >{{ msg.sender_name }}
                                                    <span
                                                        v-if="msg.is_manager"
                                                        class="text-primary"
                                                        >(Manager)</span
                                                    ></span
                                                >
                                                <span
                                                    class="text-[10px] text-muted-foreground"
                                                    >{{
                                                        formatDate(
                                                            msg.created_at,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                            <p class="whitespace-pre-wrap">
                                                {{ msg.body }}
                                            </p>
                                        </div>

                                        <!-- Executor Reply Form -->
                                        <div
                                            v-if="
                                                authUser?.id ===
                                                    step.assigned_to &&
                                                ![
                                                    'completed',
                                                    'skipped',
                                                    'cancelled',
                                                ].includes(step.status)
                                            "
                                            class="mt-2"
                                        >
                                            <div
                                                v-if="showReplyForm !== step.id"
                                            >
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    class="h-8 text-xs text-muted-foreground"
                                                    @click="
                                                        showReplyForm = step.id
                                                    "
                                                    >Phản hồi</Button
                                                >
                                            </div>
                                            <div
                                                v-else
                                                class="mt-2 flex flex-col gap-3 rounded-md border border-border bg-background p-3"
                                            >
                                                <Textarea
                                                    v-model="
                                                        replyInput[step.id]
                                                    "
                                                    rows="2"
                                                    placeholder="Nhập phản hồi..."
                                                />
                                                <div class="flex gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        @click="
                                                            showReplyForm = null
                                                        "
                                                        >Hủy</Button
                                                    >
                                                    <Button
                                                        size="sm"
                                                        :disabled="
                                                            !replyInput[
                                                                step.id
                                                            ]?.trim() ||
                                                            replyForm.processing
                                                        "
                                                        @click="
                                                            submitReply(step.id)
                                                        "
                                                        >Gửi</Button
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Completed Info -->
                                    <div
                                        v-if="
                                            step.completed_at ||
                                            step.status === 'skipped' ||
                                            step.status === 'cancelled'
                                        "
                                        class="mt-3 rounded-md border bg-muted/30 p-3 text-sm"
                                    >
                                        <div
                                            class="mb-2 flex items-start justify-between"
                                        >
                                            <span class="text-muted-foreground"
                                                >Hoàn tất lúc:</span
                                            >
                                            <span class="font-medium">{{
                                                formatDate(step.completed_at)
                                            }}</span>
                                        </div>
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <span class="text-muted-foreground"
                                                >Bởi:</span
                                            >
                                            <span class="font-medium">{{
                                                step.finisher_name
                                            }}</span>
                                        </div>
                                        <div
                                            v-if="step.completion_notes"
                                            class="mt-3 border-t pt-3"
                                        >
                                            <span
                                                class="mb-1 block text-muted-foreground"
                                                >Ghi chú:</span
                                            >
                                            <p
                                                class="whitespace-pre-wrap text-foreground italic"
                                            >
                                                {{ step.completion_notes }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div
                v-if="can.view_full_log"
                class="mt-8 flex flex-col gap-4 border-t pt-8"
            >
                <h2 class="text-xl font-semibold">
                    Lịch sử hoạt động (Audit Trail)
                </h2>
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <div
                        v-if="activities && activities.length > 0"
                        class="relative flex flex-col gap-6"
                    >
                        <div
                            class="absolute top-2 bottom-2 left-[11px] w-[2px] bg-border"
                        ></div>

                        <div
                            v-for="activity in activities"
                            :key="activity.id"
                            class="relative z-10 flex gap-4"
                        >
                            <div
                                class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-4 border-card bg-primary"
                            ></div>
                            <div class="flex flex-col gap-1 pb-4">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-medium">{{
                                        activity.description
                                    }}</span>
                                    <span
                                        class="text-xs text-muted-foreground"
                                        >{{ activity.created_at }}</span
                                    >
                                </div>
                                <div class="text-sm">
                                    <span class="text-muted-foreground"
                                        >Người thực hiện:
                                    </span>
                                    <span class="font-medium">{{
                                        activity.causer_name
                                    }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-muted-foreground"
                                        >Đối tượng:
                                    </span>
                                    <span
                                        >{{ activity.subject_type }}
                                        {{
                                            activity.subject_name
                                                ? `- ${activity.subject_name}`
                                                : ''
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="
                                        activity.properties &&
                                        Object.keys(activity.properties)
                                            .length > 0
                                    "
                                    class="mt-2 overflow-x-auto rounded-md border bg-muted/50 p-3 font-mono text-xs"
                                >
                                    <div
                                        v-for="(
                                            value, key
                                        ) in activity.properties"
                                        :key="key"
                                        class="mb-1"
                                    >
                                        <span class="font-semibold text-primary"
                                            >{{ key }}:
                                        </span>
                                        <span
                                            v-if="
                                                typeof value === 'object' &&
                                                value !== null
                                            "
                                            class="whitespace-pre-wrap"
                                            >{{
                                                JSON.stringify(value, null, 2)
                                            }}</span
                                        >
                                        <span v-else>{{ value }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-muted-foreground">
                        Không có dữ liệu lịch sử hoạt động.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
