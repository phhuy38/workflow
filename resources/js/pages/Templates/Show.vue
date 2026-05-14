<script setup lang="ts">
import { useForm, usePage, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import StepBuilder from '@/components/template-builder/StepBuilder.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { index as templatesIndex, show as templatesShow, update as templatesUpdate, destroy as templatesDestroy } from '@/routes/process-templates';
import type { ProcessTemplate } from '@/types';
import type { StepDefinition } from '@/types/step';
import { ref } from 'vue';

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

const isEditingMetadata = ref(false);
const metadataForm = useForm({
    name: props.template.name,
    description: props.template.description || '',
});

function submitMetadataUpdate() {
    metadataForm.patch(templatesUpdate({ process_template: props.template.id }).url, {
        onSuccess: () => {
            isEditingMetadata.value = false;
        },
    });
}

function cancelMetadataEdit() {
    metadataForm.reset();
    isEditingMetadata.value = false;
}

function deleteTemplate() {
    if (!window.confirm('Bạn có chắc chắn muốn xóa template này? Hành động này không thể hoàn tác.')) {
        return;
    }

    router.delete(templatesDestroy({ process_template: props.template.id }).url);
}

function publishTemplate() {
    if (!window.confirm('Bạn có chắc chắn muốn xuất bản template này? Manager sẽ có thể khởi chạy quy trình từ phiên bản này.')) {
        return;
    }
    router.post(route('process-templates.publish', props.template.id));
}

function unpublishTemplate() {
    if (!window.confirm('Bạn có chắc chắn muốn chuyển template này về bản nháp? Manager sẽ không thể khởi tạo quy trình mới từ template này.')) {
        return;
    }
    router.post(route('process-templates.unpublish', props.template.id));
}

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

        <!-- Flash error message -->
        <div
            v-if="getFlash('error')"
            class="rounded-md border border-destructive bg-destructive/10 p-4 text-sm text-destructive"
        >
            {{ getFlash('error') }}
        </div>

        <!-- Template header -->
        <div class="flex items-start justify-between gap-4">
            <div v-if="!isEditingMetadata" class="flex flex-col gap-1 w-full">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold">{{ template.name }}</h1>
                    <Button v-if="can.update" variant="ghost" size="sm" @click="isEditingMetadata = true">
                        Sửa
                    </Button>
                </div>
                <p v-if="template.description" class="text-muted-foreground text-sm">
                    {{ template.description }}
                </p>
            </div>

            <!-- Metadata Edit Form -->
            <div v-else class="flex flex-col gap-4 w-full rounded-md border p-4 bg-muted/30">
                <div class="flex flex-col gap-2">
                    <Input v-model="metadataForm.name" placeholder="Tên template..." />
                    <p v-if="metadataForm.errors.name" class="text-destructive text-sm">{{ metadataForm.errors.name }}</p>
                </div>
                <div class="flex flex-col gap-2">
                    <Textarea v-model="metadataForm.description" placeholder="Mô tả..." rows="2" />
                    <p v-if="metadataForm.errors.description" class="text-destructive text-sm">{{ metadataForm.errors.description }}</p>
                </div>
                <div class="flex gap-2">
                    <Button size="sm" @click="submitMetadataUpdate" :disabled="metadataForm.processing">
                        {{ metadataForm.processing ? 'Đang lưu...' : 'Lưu' }}
                    </Button>
                    <Button size="sm" variant="ghost" @click="cancelMetadataEdit">Hủy</Button>
                </div>
            </div>

            <Badge v-if="!isEditingMetadata" :variant="template.is_published ? 'default' : 'secondary'" class="shrink-0">
                {{ template.is_published ? 'Published' : 'Draft' }}
            </Badge>
        </div>

        <!-- Action bar for Publish/Unpublish -->
        <div v-if="can.publish" class="flex items-center gap-3 bg-muted/20 p-4 rounded-md border">
            <template v-if="!template.is_published">
                <div class="flex flex-col gap-1 grow">
                    <p class="text-sm font-medium">Template đang ở trạng thái nháp</p>
                    <p class="text-xs text-muted-foreground">Manager chưa thể sử dụng template này.</p>
                </div>
                <Button @click="publishTemplate">
                    Xuất bản (Publish)
                </Button>
            </template>
            <template v-else>
                <div class="flex flex-col gap-1 grow">
                    <p class="text-sm font-medium">Template đã được xuất bản</p>
                    <p class="text-xs text-muted-foreground">Manager có thể khởi tạo quy trình từ template này.</p>
                </div>
                <Button variant="outline" @click="unpublishTemplate">
                    Chuyển về bản nháp (Unpublish)
                </Button>
            </template>
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

                    <!-- Danger zone: Delete -->
                    <div v-if="can.delete" class="mt-6 pt-6 border-t">
                        <Button variant="destructive" size="sm" class="w-full" @click="deleteTemplate">
                            Xóa template
                        </Button>
                    </div>
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
