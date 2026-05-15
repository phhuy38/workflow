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
import { ref } from 'vue';

interface Instance {
    id: number;
    name: string;
    template_name: string;
    status: string;
    progress: number;
    launched_at: string;
    creator_name: string;
}

interface Step {
    id: number;
    name: string;
    order: number;
    status: string;
    assigned_to: number;
    assignee_name: string;
    deadline_at: string;
    completed_at: string;
    finisher_name: string;
}

interface Props {
    instance: Instance;
    steps: Step[];
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

const showNotesForm = ref<number | null>(null);
const completeForm = useForm({
    completion_notes: '',
});

function getStatusVariant(status: string) {
    switch (status) {
        case 'running': case 'in_progress': return 'default';
        case 'completed': return 'success';
        case 'cancelled': case 'escalated': return 'destructive';
        case 'paused': case 'blocked': return 'warning';
        default: return 'secondary';
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
    completeForm.post(`/step-executions/${stepId}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            showNotesForm.value = null;
            completeForm.reset();
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

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">{{ instance.name }}</h1>
            <Badge :variant="getStatusVariant(instance.status)" class="text-sm">
                {{ instance.status }}
            </Badge>
        </div>

        <!-- Flash success message -->
        <div
            v-if="getFlash('success')"
            class="rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200"
        >
            {{ getFlash('success') }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Template gốc</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-lg font-bold">{{ instance.template_name }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Tiến độ</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-lg font-bold">{{ instance.progress }}%</div>
                    <div class="w-full h-2 bg-secondary rounded-full mt-2 overflow-hidden">
                        <div class="h-full bg-primary" :style="{ width: instance.progress + '%' }"></div>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Thông tin khởi động</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-sm">{{ instance.creator_name }}</div>
                    <div class="text-xs text-muted-foreground">{{ formatDate(instance.launched_at) }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-semibold">Danh sách các bước thực thi</h2>
            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12 text-center">#</TableHead>
                            <TableHead>Tên bước</TableHead>
                            <TableHead>Người phụ trách</TableHead>
                            <TableHead>Trạng thái</TableHead>
                            <TableHead>Thời hạn</TableHead>
                            <TableHead>Thao tác / Kết quả</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="step in steps" :key="step.id">
                            <TableCell class="text-center font-medium">{{ step.order }}</TableCell>
                            <TableCell>{{ step.name }}</TableCell>
                            <TableCell>{{ step.assignee_name || 'N/A' }}</TableCell>
                            <TableCell>
                                <Badge :variant="getStatusVariant(step.status)">
                                    {{ step.status }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm">
                                {{ formatDate(step.deadline_at) }}
                            </TableCell>
                            <TableCell class="text-sm min-w-[200px]">
                                <!-- Interaction for Executor -->
                                <div v-if="authUser && step.assigned_to === authUser.id && !step.completed_at && step.status !== 'skipped' && step.status !== 'cancelled'">
                                    <div v-if="step.status === 'pending'">
                                        <Button size="sm" @click="acknowledge(step.id)">Xác nhận nhận việc</Button>
                                    </div>
                                    <div v-else-if="step.status === 'in_progress'" class="flex flex-col gap-2">
                                        <div v-if="showNotesForm !== step.id">
                                            <Button size="sm" variant="success" @click="showNotesForm = step.id">Hoàn thành</Button>
                                        </div>
                                        <div v-else class="flex flex-col gap-2 p-2 border rounded bg-muted/20">
                                            <Label class="text-xs">Ghi chú hoàn thành (tùy chọn):</Label>
                                            <Textarea v-model="completeForm.completion_notes" rows="2" class="text-xs" />
                                            <div class="flex gap-2">
                                                <Button size="xs" variant="ghost" @click="showNotesForm = null">Hủy</Button>
                                                <Button size="xs" :disabled="completeForm.processing" @click="submitComplete(step.id)">Gửi hoàn thành</Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Interaction for Manager (Override) -->
                                <div v-if="can.override && ['pending', 'in_progress'].includes(step.status)" class="mt-2">
                                    <div v-if="showOverrideForm !== step.id">
                                        <Button size="xs" variant="outline" class="text-warning border-warning" @click="showOverrideForm = step.id">Override (Cưỡng chế)</Button>
                                    </div>
                                    <div v-else class="flex flex-col gap-2 p-2 border border-warning/50 rounded bg-warning/10">
                                        <Label class="text-xs text-warning">Lý do override:</Label>
                                        <Textarea v-model="overrideForm.reason" rows="2" class="text-xs" />
                                        <p v-if="overrideForm.errors.reason" class="text-destructive text-xs">{{ overrideForm.errors.reason }}</p>
                                        <div class="flex gap-2">
                                            <Button size="xs" variant="ghost" @click="showOverrideForm = null">Hủy</Button>
                                            <Button size="xs" variant="warning" :disabled="overrideForm.processing" @click="submitOverride(step.id)">Xác nhận Override</Button>
                                        </div>
                                    </div>
                                </div>

                                <div v-else-if="step.completed_at || step.status === 'skipped' || step.status === 'cancelled'" class="flex flex-col gap-1 mt-1">
                                    <div class="flex flex-col">
                                        <span>{{ formatDate(step.completed_at) }}</span>
                                        <span class="text-xs text-muted-foreground">Bởi: {{ step.finisher_name }}</span>
                                    </div>
                                    <div v-if="step.completion_notes" class="text-xs p-2 bg-muted rounded italic">
                                        {{ step.completion_notes }}
                                    </div>
                                </div>
                                <span v-else class="text-muted-foreground">---</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Activity Log Timeline -->
        <div v-if="can.view_full_log" class="flex flex-col gap-4 mt-4 border-t pt-6">
            <h2 class="text-xl font-semibold">Lịch sử hoạt động (Audit Trail)</h2>
            <div class="rounded-md border p-6 bg-card">
                <div v-if="activities && activities.length > 0" class="flex flex-col gap-6 relative">
                    <!-- Vertical line -->
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
                            <div v-if="activity.properties && Object.keys(activity.properties).length > 0" class="mt-1 p-2 bg-muted rounded text-xs">
                                <div v-for="(value, key) in activity.properties" :key="key">
                                    <span class="font-medium capitalize">{{ key }}: </span>
                                    <span v-if="typeof value === 'object' && value !== null" class="whitespace-pre-wrap">{{ JSON.stringify(value, null, 2) }}</span>
                                    <span v-else>{{ value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-4 text-muted-foreground">
                    Không có dữ liệu lịch sử hoạt động.
                </div>
            </div>
        </div>

    </div>
</template>
