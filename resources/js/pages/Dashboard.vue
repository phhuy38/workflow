<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import {
    Card,
    CardHeader,
    CardTitle,
    CardContent,
    CardFooter,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    AlertCircle,
    Clock,
    CheckCircle2,
    ChevronRight,
    Activity,
    Search,
} from 'lucide-vue-next';
import DashboardFilters from '@/components/dashboard/DashboardFilters.vue';

interface Instance {
    id: number;
    name: string;
    template_name: string;
    status: string;
    progress: number;
    current_step: string;
    launched_at: string;
    creator_name: string;
    traffic_light_status: 'critical' | 'warning' | 'normal';
}

defineProps<{
    instances?: Instance[];
    can_view_manager_dashboard: boolean;
    filterOptions?: {
        templates: { id: number; name: string }[];
        executors: { id: number; name: string }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();

const isOffline = ref(false);

const reloadDashboard = useDebounceFn(() => {
    router.reload({
        only: ['instances'],
        preserveScroll: true,
        preserveState: true,
    });
}, 1000);

const handleConnected = () => {
    isOffline.value = false;
    reloadDashboard();
};

const handleDisconnected = () => {
    isOffline.value = true;
};

onMounted(() => {
    if (props.can_view_manager_dashboard && window.Echo) {
        window.Echo.private('system.instances')
            .listen('ProcessInstanceUpdated', reloadDashboard)
            .listen('ProcessLaunched', reloadDashboard)
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
    if (window.Echo) {
        window.Echo.leave('system.instances');
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
</script>

<template>
    <Head title="Dashboard" />

    <div
        v-if="isOffline"
        class="bg-destructive/10 p-2 text-center text-sm text-destructive"
    >
        Đang mất kết nối mạng. Đang cố gắng kết nối lại...
    </div>

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-8"
    >
        <div
            v-if="!can_view_manager_dashboard"
            class="flex h-full flex-col items-center justify-center gap-4 text-center"
        >
            <Activity class="h-16 w-16 text-muted-foreground opacity-50" />
            <h2 class="text-xl font-medium">Dashboard</h2>
            <p class="max-w-md text-muted-foreground">
                Khu vực dành cho bạn đang được xây dựng. Vui lòng kiểm tra lại
                sau.
            </p>
        </div>

        <template v-else>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Trạng thái quy trình
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Theo dõi các tiến trình đang chạy theo mức độ khẩn cấp.
                    </p>
                </div>
                <Button asChild variant="outline">
                    <Link href="/process-instances/create">Khởi động mới</Link>
                </Button>
            </div>

            <DashboardFilters v-if="filterOptions" :options="filterOptions" />

            <!-- Empty state when no instances at all vs no instances matching filters -->
            <template v-if="instances && instances.length === 0">
                <div
                    v-if="
                        page.url.includes('search=') ||
                        page.url.includes('template_id=') ||
                        page.url.includes('executor_id=')
                    "
                    class="flex h-full min-h-[300px] flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed p-8 text-center"
                >
                    <div class="rounded-full bg-muted p-4">
                        <Search class="h-10 w-10 text-muted-foreground" />
                    </div>
                    <h3 class="text-xl font-medium">Không tìm thấy kết quả</h3>
                    <p class="text-muted-foreground">
                        Thử điều chỉnh bộ lọc để xem các quy trình khác.
                    </p>
                </div>
                <div
                    v-else
                    class="flex h-full min-h-[400px] flex-col items-center justify-center gap-6 rounded-xl border-2 border-dashed p-8 text-center"
                >
                    <div class="rounded-full bg-muted p-4">
                        <Activity class="h-12 w-12 text-muted-foreground" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <h2 class="text-2xl font-semibold">
                            Chưa có quy trình nào đang chạy
                        </h2>
                        <p class="max-w-md text-muted-foreground">
                            Bắt đầu bằng cách khởi động một quy trình mới từ các
                            template đã có sẵn.
                        </p>
                    </div>
                    <Button asChild size="lg">
                        <Link href="/process-instances/create"
                            >Khởi động quy trình đầu tiên</Link
                        >
                    </Button>
                </div>
            </template>

            <!-- CSS Grid layout for instances -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="instance in instances"
                    :key="instance.id"
                    :class="{
                        'border-red-500/50 bg-red-50 dark:bg-red-950/20':
                            instance.traffic_light_status === 'critical',
                        'border-yellow-500/50 bg-yellow-50 dark:bg-yellow-950/20':
                            instance.traffic_light_status === 'warning',
                    }"
                    class="flex flex-col transition-colors hover:shadow-md"
                >
                    <CardHeader
                        class="flex flex-row items-start justify-between space-y-0 pb-3"
                    >
                        <div class="space-y-1">
                            <CardTitle
                                class="line-clamp-1 text-base"
                                :title="instance.name"
                            >
                                {{ instance.name }}
                            </CardTitle>
                            <p
                                class="line-clamp-1 text-xs text-muted-foreground"
                            >
                                {{ instance.template_name }}
                            </p>
                        </div>
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
                            class="ml-2 shrink-0 whitespace-nowrap"
                        >
                            <AlertCircle
                                v-if="
                                    instance.traffic_light_status === 'critical'
                                "
                                class="mr-1 h-3 w-3"
                            />
                            <Clock
                                v-else-if="
                                    instance.traffic_light_status === 'warning'
                                "
                                class="mr-1 h-3 w-3"
                            />
                            <CheckCircle2
                                v-else
                                class="mr-1 h-3 w-3 text-green-600"
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
                    </CardHeader>
                    <CardContent class="flex-1 pb-3">
                        <div class="space-y-3">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-medium"
                                    >Bước hiện tại</span
                                >
                                <span class="truncate text-sm font-semibold">{{
                                    instance.current_step
                                }}</span>
                            </div>

                            <div class="space-y-1.5">
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span class="text-muted-foreground"
                                        >Tiến độ</span
                                    >
                                    <span class="font-medium"
                                        >{{ instance.progress }}%</span
                                    >
                                </div>
                                <div
                                    class="h-2 w-full overflow-hidden rounded-full bg-secondary"
                                >
                                    <div
                                        class="h-full rounded-full transition-all"
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
                                        :style="{
                                            width: `${instance.progress}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="mt-auto border-t bg-muted/20 pt-3">
                        <Button
                            asChild
                            variant="ghost"
                            class="h-8 w-full justify-between px-2 text-xs"
                        >
                            <Link :href="`/process-instances/${instance.id}`">
                                Xem chi tiết
                                <ChevronRight class="h-4 w-4" />
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </template>
    </div>
</template>
