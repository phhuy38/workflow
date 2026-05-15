<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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

interface Instance {
    id: number;
    name: string;
    template_name: string;
    status: string;
    progress: number;
    current_step: string;
    launched_at: string;
    creator_name: string;
}

interface Props {
    instances: Instance[];
}

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Instances', href: '/process-instances' },
        ],
    },
});

function getStatusVariant(status: string) {
    switch (status) {
        case 'running': return 'default';
        case 'completed': return 'success';
        case 'cancelled': return 'destructive';
        case 'paused': return 'warning';
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
</script>

<template>
    <Head title="Danh sách quy trình đang chạy" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Quy trình đang chạy</h1>
            <Button as="a" href="/process-instances/create">Khởi động quy trình mới</Button>
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tên quy trình</TableHead>
                        <TableHead>Template gốc</TableHead>
                        <TableHead>Tiến độ</TableHead>
                        <TableHead>Bước hiện tại</TableHead>
                        <TableHead>Trạng thái</TableHead>
                        <TableHead>Ngày khởi động</TableHead>
                        <TableHead class="text-right">Thao tác</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="instance in instances" :key="instance.id">
                        <TableCell class="font-medium">
                            <div class="flex flex-col">
                                <span>{{ instance.name }}</span>
                                <span class="text-xs text-muted-foreground">Người khởi động: {{ instance.creator_name }}</span>
                            </div>
                        </TableCell>
                        <TableCell>{{ instance.template_name }}</TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2">
                                <div class="w-24 h-2 bg-secondary rounded-full overflow-hidden">
                                    <div class="h-full bg-primary" :style="{ width: instance.progress + '%' }"></div>
                                </div>
                                <span class="text-xs font-medium">{{ instance.progress }}%</span>
                            </div>
                        </TableCell>
                        <TableCell>{{ instance.current_step }}</TableCell>
                        <TableCell>
                            <Badge :variant="getStatusVariant(instance.status)">
                                {{ instance.status }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ formatDate(instance.launched_at) }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="outline" size="sm" as="a" :href="`/process-instances/${instance.id}`">
                                Chi tiết
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="instances.length === 0">
                        <TableCell colspan="7" class="text-muted-foreground py-8 text-center">
                            Chưa có quy trình nào đang chạy. Nhấn "Khởi động quy trình mới" để bắt đầu.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
