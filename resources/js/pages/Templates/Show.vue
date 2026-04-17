<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { index as templatesIndex, show as templatesShow } from '@/routes/process-templates';
import type { ProcessTemplate } from '@/types';

interface Can {
    update: boolean;
    delete: boolean;
    publish: boolean;
}

const props = defineProps<{
    template: ProcessTemplate;
    can: Can;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Templates', href: templatesIndex().url },
            { title: props.template.name, href: templatesShow({ process_template: props.template.id }).url },
        ],
    },
});

const page = usePage();

function formatDate(isoString: string): string {
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
    <Head :title="template.name" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Flash success message -->
        <div
            v-if="(page.props.flash as Record<string, string>)?.success"
            class="rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200"
        >
            {{ (page.props.flash as Record<string, string>).success }}
        </div>

        <!-- Template header -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold">{{ template.name }}</h1>
                <p v-if="template.description" class="text-muted-foreground text-sm">
                    {{ template.description }}
                </p>
            </div>
            <Badge :variant="template.is_published ? 'default' : 'secondary'" class="shrink-0">
                {{ template.is_published ? 'Published' : 'Draft' }}
            </Badge>
        </div>

        <!-- Template info -->
        <div class="grid grid-cols-2 gap-4 rounded-md border p-4 sm:grid-cols-3">
            <div>
                <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Số bước</p>
                <p class="mt-1 font-medium">{{ template.step_count }}</p>
            </div>
            <div>
                <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Phiên bản</p>
                <p class="mt-1 font-medium">v{{ template.version }}</p>
            </div>
            <div>
                <p class="text-muted-foreground text-xs font-medium uppercase tracking-wide">Ngày tạo</p>
                <p class="mt-1 font-medium">{{ formatDate(template.created_at) }}</p>
            </div>
        </div>

        <!-- Steps section — placeholder for Story 2.2 -->
        <div class="rounded-md border p-6">
            <h2 class="mb-3 text-lg font-medium">Các bước quy trình</h2>
            <p class="text-muted-foreground text-sm">
                Thêm bước quy trình trong phần này (sẽ có đầy đủ chức năng sau).
            </p>
        </div>
    </div>
</template>
