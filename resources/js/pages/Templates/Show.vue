<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import StepBuilder from '@/components/template-builder/StepBuilder.vue';
import { Badge } from '@/components/ui/badge';
import { index as templatesIndex, show as templatesShow } from '@/routes/process-templates';
import type { ProcessTemplate } from '@/types';
import type { StepDefinition } from '@/types/step';

interface Can {
    update: boolean;
    delete: boolean;
    publish: boolean;
}

interface Props {
    template: ProcessTemplate;
    steps: StepDefinition[];
    can: Can;
}

const props = defineProps<Props>();

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

function getFlash(key: string): string | null {
    const flash = page.props.flash as Record<string, unknown> | undefined;

    if (!flash || typeof flash[key] !== 'string') {
return null;
}

    return flash[key];
}

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
            v-if="getFlash('success')"
            class="rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700"
        >
            {{ getFlash('success') }}
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

        <!-- Two-column layout: sidebar info + main editor -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Sidebar: template metadata -->
            <aside class="lg:col-span-1">
                <div class="rounded-md border p-4">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Thông tin template
                    </h2>
                    <dl class="flex flex-col gap-2 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Số bước</dt>
                            <dd class="font-medium">{{ steps.length }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Phiên bản</dt>
                            <dd class="font-medium">v{{ template.version }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Ngày tạo</dt>
                            <dd class="font-medium">{{ formatDate(template.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Trạng thái</dt>
                            <dd>
                                <Badge :variant="template.is_published ? 'default' : 'secondary'">
                                    {{ template.is_published ? 'Published' : 'Draft' }}
                                </Badge>
                            </dd>
                        </div>
                    </dl>
                </div>
            </aside>

            <!-- Main: visual step builder -->
            <main class="lg:col-span-2">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Các bước quy trình</h2>
                    </div>

                    <StepBuilder
                        :template-id="template.id"
                        :initial-steps="steps"
                        :can-edit="can.update"
                    />
                </div>
            </main>
        </div>
    </div>
</template>
