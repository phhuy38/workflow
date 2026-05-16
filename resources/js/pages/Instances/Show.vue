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
import { AlertCircle, Clock, CheckCircle2, ChevronRight, Activity, Search, SkipForward } from 'lucide-vue-next';

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
        }
    });
}

function submitReply(stepId: number) {
    replyForm.body = replyInput.value[stepId] || '';
    replyForm.post(`/step-executions/${stepId}/messages`, {
        preserveScroll: true,
        onSuccess: () => {
            showReplyForm.value = null;
            replyInput.value[stepId] = '';
        }
    });
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'running': case 'in_progress': return 'default';
        case 'completed': return 'success';
        case 'cancelled': case 'escalated': return 'destructive';
        case 'paused': case 'blocked': return 'warning';
        default: return 'secondary';
    }
}

function isOverdue(deadlineString: string) {
    if (!deadlineString) return false;
    const deadline = new Date(deadlineString).getTime();
    const now = new Date().getTime();
    return now > deadline;
}

function getTimelineColor(step: Step) {
    if (['pending', 'in_progress'].includes(step.status) && isOverdue(step.deadline_at)) {
        return 'bg-red-500';
    }
    
    switch (step.status) {
        case 'completed': return 'bg-green-500';
        case 'in_progress': return 'bg-yellow-500';
        case 'skipped': return 'bg-gray-400';
        case 'cancelled': case 'escalated': return 'bg-red-500';
        case 'pending': default: return 'bg-gray-200 dark:bg-gray-700';
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
        }
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
        }
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

    <div class="flex flex-col h-full w-full">
        <!-- Sticky Header (UX-DR10) -->
        <div class="sticky top-0 z-40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b p-6 shadow-sm">
            <div class="flex flex-col gap-4 max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold">{{ instance.name }}</h1>
                        <p class="text-sm text-muted-foreground">{{ instance.template_name }} &bull; Khởi tạo bởi {{ instance.creator_name }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge :variant="
                            instance.traffic_light_status === 'critical' ? 'destructive' : 
                            (instance.traffic_light_status === 'warning' ? 'default' : 'outline')
                        " 
                        :class="{'bg-yellow-500 hover:bg-yellow-600 text-white': instance.traffic_light_status === 'warning'}"
                        class="text-sm py-1 px-3">
                            <AlertCircle v-if="instance.traffic_light_status === 'critical'" class="w-4 h-4 mr-1.5" />
                            <Clock v-else-if="instance.traffic_light_status === 'warning'" class="w-4 h-4 mr-1.5" />
                            <CheckCircle2 v-else class="w-4 h-4 mr-1.5 text-green-600" />
                            {{ 
                                instance.traffic_light_status === 'critical' ? 'Quá hạn' : 
                                (instance.traffic_light_status === 'warning' ? 'Cần chú ý' : 'Bình thường') 
                            }}
                        </Badge>
                        <Badge :variant="getStatusVariant(instance.status)" class="text-sm py-1 px-3">
                            {{ instance.status }}
                        </Badge>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-6 text-sm">
                    <div class="flex-1 w-full">
                        <div class="flex justify-between mb-1">
                            <span class="font-medium">Tiến độ quy trình</span>
                            <span class="font-bold">{{ instance.progress }}%</span>
                        </div>
                        <div class="w-full h-2.5 bg-secondary rounded-full overflow-hidden">
                            <div class="h-full transition-all duration-500" 
                                 :class="{
                                     'bg-red-500': instance.traffic_light_status === 'critical',
                                     'bg-yellow-500': instance.traffic_light_status === 'warning',
                                     'bg-green-500': instance.traffic_light_status === 'normal'
                                 }"
                                 :style="{ width: instance.progress + '%' }"></div>
                        </div>
                    </div>
                    <div class="flex gap-8 whitespace-nowrap pt-2 md:pt-0">
                        <div class="flex flex-col">
                            <span class="text-muted-foreground text-xs">Thời gian đã chạy</span>
                            <span class="font-semibold">{{ instance.time_elapsed }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-muted-foreground text-xs">Ước tính còn lại</span>
                            <span class="font-semibold">{{ instance.estimated_remaining_hours > 0 ? instance.estimated_remaining_hours + ' giờ' : 'Đã hoàn thành' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Process Context Flow Ribbon (Story 5.2) -->
                <div class="mt-4 pt-4 border-t flex gap-2 overflow-x-auto pb-2 scrollbar-thin">
                    <div v-for="(step, index) in steps" :key="'ribbon-' + step.id" class="flex items-center whitespace-nowrap">
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border"
                             :class="{
                                 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800': step.status === 'completed',
                                 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800 ring-2 ring-yellow-400/50': step.status === 'in_progress',
                                 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800': ['pending', 'in_progress'].includes(step.status) && isOverdue(step.deadline_at),
                                 'bg-muted text-muted-foreground border-border': step.status === 'pending' || step.status === 'skipped',
                                 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800': step.status === 'cancelled' || step.status === 'escalated'
                             }">
                            <span>B.{{ step.order }}</span>
                            <span class="truncate max-w-[120px]" :title="step.name">{{ step.name }}</span>
                            <CheckCircle2 v-if="step.status === 'completed'" class="w-3 h-3" />
                            <Clock v-else-if="step.status === 'pending'" class="w-3 h-3" />
                            <Activity v-else-if="step.status === 'in_progress'" class="w-3 h-3" />
                            <AlertCircle v-else-if="['cancelled', 'escalated'].includes(step.status)" class="w-3 h-3" />
                            <SkipForward v-else-if="step.status === 'skipped'" class="w-3 h-3" />
                        </div>
                        <ChevronRight v-if="index < steps.length - 1" class="w-4 h-4 text-muted-foreground mx-1 shrink-0" />
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto w-full p-4 md:p-6 flex flex-col gap-8 pb-24 md:pb-6">
            <!-- Flash success message -->
            <div
                v-if="getFlash('success')"
                class="rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200 shadow-sm"
            >
                {{ getFlash('success') }}
            </div>

            <!-- Visual Timeline -->
            <div>
                <h2 class="text-xl font-semibold mb-6">Tiến trình thực hiện (Timeline)</h2>
                <div class="relative">
                    <div class="absolute top-4 bottom-0 left-[15px] md:left-1/2 w-0.5 bg-border -translate-x-1/2"></div>
                    
                    <div v-for="(step, index) in steps" :key="step.id" class="relative flex flex-col md:flex-row gap-6 mb-8 group">
                        
                        <!-- Mobile connector line -->
                        <div class="absolute left-[15px] top-8 bottom-[-2rem] w-0.5 bg-border md:hidden" v-if="index !== steps.length - 1"></div>

                        <!-- Left side (Time / Status for desktop) -->
                        <div class="md:w-1/2 flex md:justify-end items-start md:pr-12 md:pt-1 pl-12 md:pl-0">
                            <div class="flex flex-col items-start md:items-end">
                                <span class="text-sm font-medium">{{ formatDate(step.deadline_at) }}</span>
                                <span class="text-xs text-muted-foreground text-left md:text-right">Hạn chót</span>
                            </div>
                        </div>

                        <!-- Center Node -->
                        <div class="absolute left-0 md:left-1/2 w-8 h-8 rounded-full border-4 border-background flex items-center justify-center -translate-x-1/2 z-10"
                             :class="[getTimelineColor(step.status), step.status === 'in_progress' ? 'ring-4 ring-yellow-500/20' : '']">
                            <span class="text-white text-[10px] font-bold">{{ step.order }}</span>
                        </div>

                        <!-- Right side (Card) -->
                        <div class="md:w-1/2 flex justify-start items-start md:pl-12 pl-12 md:pr-0">
                            <Card class="w-full shadow-sm" :class="{'ring-2 ring-primary': step.status === 'in_progress'}">
                                <CardHeader class="p-4 pb-2">
                                    <div class="flex justify-between items-start gap-4">
                                        <CardTitle class="text-base">{{ step.name }}</CardTitle>
                                        <Badge :variant="getStatusVariant(step.status)">{{ step.status }}</Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground mt-1">Phụ trách: <strong>{{ step.assignee_name || 'N/A' }}</strong></p>
                                    <p v-if="step.description" class="text-sm text-foreground mt-2">{{ step.description }}</p>
                                </CardHeader>
                                
                                <CardContent class="p-4 pt-2">
                                    <!-- Interaction for Executor -->
                                    <div v-if="authUser && step.assigned_to === authUser.id && !step.completed_at && step.status !== 'skipped' && step.status !== 'cancelled'">
                                        <div class="fixed bottom-0 left-0 right-0 p-4 bg-background border-t shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50 md:static md:bg-transparent md:border-none md:shadow-none md:p-0 md:z-auto">
                                            <div v-if="step.status === 'pending'" class="w-full">
                                                <Button size="sm" class="w-full md:w-auto" @click="acknowledge(step.id)">Xác nhận nhận việc</Button>
                                            </div>
                                            <div v-else-if="step.status === 'in_progress'" class="flex flex-col gap-2 mt-2 md:mt-0 w-full">
                                                <div v-if="showNotesForm !== step.id">
                                                    <Button size="sm" variant="default" class="w-full md:w-auto bg-green-600 hover:bg-green-700 text-white" @click="showNotesForm = step.id">Hoàn thành bước</Button>
                                                </div>
                                                <div v-else class="flex flex-col gap-3 p-3 border rounded-md bg-muted/30">
                                                    <Label class="text-sm font-medium">Ghi chú (tùy chọn)</Label>
                                                    <Textarea v-model="notesInput[step.id]" rows="2" placeholder="Nhập kết quả công việc..." />
                                                    <p v-if="completeForm.errors.completion_notes" class="text-xs text-destructive">{{ completeForm.errors.completion_notes }}</p>
                                                    <div class="flex gap-2">
                                                        <Button size="sm" class="flex-1 md:flex-none" variant="ghost" @click="showNotesForm = null">Hủy</Button>
                                                        <Button size="sm" class="flex-1 md:flex-none" :disabled="completeForm.processing" @click="submitComplete(step.id)">Gửi xác nhận</Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Interaction for Manager or Beneficiary (Ping / Override) -->
                                    <div v-if="(can.ping || can.override) && ['pending', 'in_progress'].includes(step.status)" class="mt-4 flex gap-2">
                                        <div class="flex flex-col gap-2 w-full">
                                            <div class="flex gap-2">
                                                <Button v-if="can.ping && showPingForm !== step.id" size="sm" variant="outline" class="h-8 text-xs" @click="showPingForm = step.id">
                                                    {{ authUser?.id === instance.created_for ? 'Liên hệ người phụ trách' : 'Nhắc việc' }}
                                                </Button>
                                                <Button v-if="can.override && showOverrideForm !== step.id" size="sm" variant="outline" class="text-warning border-warning h-8 text-xs" @click="showOverrideForm = step.id">Cưỡng chế</Button>
                                            </div>

                                            <div v-if="showPingForm === step.id" class="flex flex-col gap-3 p-3 mt-2 border border-primary/20 rounded-md bg-primary/5">
                                                <Label class="text-sm font-medium">Nhắn tin cho người thực hiện:</Label>
                                                <Textarea v-model="pingInput[step.id]" rows="2" placeholder="Nhập nội dung tin nhắn..." />
                                                <p v-if="pingInput[step.id]?.trim() === ''" class="text-xs text-muted-foreground italic">Vui lòng nhập nội dung trước khi gửi.</p>
                                                <div class="flex gap-2">
                                                    <Button size="sm" variant="ghost" @click="showPingForm = null">Hủy</Button>
                                                    <Button size="sm" :disabled="!pingInput[step.id]?.trim() || pingForm.processing" @click="submitPing(step.id)">Gửi tin nhắn</Button>
                                                </div>
                                            </div>
                                            
                                            <div v-if="can.override && showOverrideForm === step.id" class="flex flex-col gap-3 p-3 mt-2 border border-warning/50 rounded-md bg-warning/5">
                                                <Label class="text-sm text-warning font-medium">Lý do override:</Label>
                                                <Textarea v-model="overrideInput[step.id]" rows="2" />
                                                <p v-if="overrideForm.errors.reason" class="text-xs text-destructive">{{ overrideForm.errors.reason }}</p>
                                                <div class="flex gap-2">
                                                    <Button size="sm" variant="ghost" @click="showOverrideForm = null">Hủy</Button>
                                                    <Button size="sm" variant="warning" :disabled="overrideForm.processing" @click="submitOverride(step.id)">Xác nhận</Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Thread -->
                                    <div v-if="step.messages && step.messages.length > 0" class="mt-4 space-y-3 border-t pt-3">
                                        <h4 class="text-sm font-semibold">Trao đổi ({{ step.messages.length }})</h4>
                                        <div v-for="msg in step.messages" :key="msg.id" class="flex flex-col gap-1 text-sm p-3 rounded-md" :class="msg.is_manager ? 'bg-primary/10 ml-4' : 'bg-muted/50 mr-4'">
                                            <div class="flex justify-between">
                                                <span class="font-medium text-xs">{{ msg.sender_name }} <span v-if="msg.is_manager" class="text-primary">(Manager)</span></span>
                                                <span class="text-[10px] text-muted-foreground">{{ formatDate(msg.created_at) }}</span>
                                            </div>
                                            <p class="whitespace-pre-wrap">{{ msg.body }}</p>
                                        </div>
                                        
                                        <!-- Executor Reply Form -->
                                        <div v-if="authUser?.id === step.assigned_to && !['completed', 'skipped', 'cancelled'].includes(step.status)" class="mt-2">
                                            <div v-if="showReplyForm !== step.id">
                                                <Button size="sm" variant="ghost" class="h-8 text-xs text-muted-foreground" @click="showReplyForm = step.id">Phản hồi</Button>
                                            </div>
                                            <div v-else class="flex flex-col gap-3 p-3 mt-2 border border-border rounded-md bg-background">
                                                <Textarea v-model="replyInput[step.id]" rows="2" placeholder="Nhập phản hồi..." />
                                                <div class="flex gap-2">
                                                    <Button size="sm" variant="ghost" @click="showReplyForm = null">Hủy</Button>
                                                    <Button size="sm" :disabled="!replyInput[step.id]?.trim() || replyForm.processing" @click="submitReply(step.id)">Gửi</Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Completed Info -->
                                    <div v-if="step.completed_at || step.status === 'skipped' || step.status === 'cancelled'" class="mt-3 p-3 bg-muted/30 rounded-md text-sm border">
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-muted-foreground">Hoàn tất lúc:</span>
                                            <span class="font-medium">{{ formatDate(step.completed_at) }}</span>
                                        </div>
                                        <div class="flex justify-between items-start">
                                            <span class="text-muted-foreground">Bởi:</span>
                                            <span class="font-medium">{{ step.finisher_name }}</span>
                                        </div>
                                        <div v-if="step.completion_notes" class="mt-3 pt-3 border-t">
                                            <span class="text-muted-foreground block mb-1">Ghi chú:</span>
                                            <p class="italic text-foreground whitespace-pre-wrap">{{ step.completion_notes }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div v-if="can.view_full_log" class="flex flex-col gap-4 mt-8 pt-8 border-t">
                <h2 class="text-xl font-semibold">Lịch sử hoạt động (Audit Trail)</h2>
                <div class="rounded-xl border p-6 bg-card shadow-sm">
                    <div v-if="activities && activities.length > 0" class="flex flex-col gap-6 relative">
                        <div class="absolute left-[11px] top-2 bottom-2 w-[2px] bg-border"></div>
                        
                        <div v-for="activity in activities" :key="activity.id" class="flex gap-4 relative z-10">
                            <div class="mt-1 h-6 w-6 rounded-full bg-primary flex items-center justify-center border-4 border-card shrink-0"></div>
                            <div class="flex flex-col gap-1 pb-4">
                                <div class="flex items-baseline gap-2">
                                    <span class="font-medium">{{ activity.description }}</span>
                                    <span class="text-xs text-muted-foreground">{{ activity.created_at }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-muted-foreground">Người thực hiện: </span>
                                    <span class="font-medium">{{ activity.causer_name }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="text-muted-foreground">Đối tượng: </span>
                                    <span>{{ activity.subject_type }} {{ activity.subject_name ? `- ${activity.subject_name}` : '' }}</span>
                                </div>
                                <div v-if="activity.properties && Object.keys(activity.properties).length > 0" class="mt-2 p-3 bg-muted/50 rounded-md text-xs font-mono overflow-x-auto border">
                                    <div v-for="(value, key) in activity.properties" :key="key" class="mb-1">
                                        <span class="font-semibold text-primary">{{ key }}: </span>
                                        <span v-if="typeof value === 'object' && value !== null" class="whitespace-pre-wrap">{{ JSON.stringify(value, null, 2) }}</span>
                                        <span v-else>{{ value }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground">
                        Không có dữ liệu lịch sử hoạt động.
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
