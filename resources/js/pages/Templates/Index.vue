<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { index as templatesIndex, store as templatesStore, show as templatesShow } from '@/routes/process-templates';
import type { ProcessTemplate } from '@/types';

defineProps<{
    templates: ProcessTemplate[];
    can: { create: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Templates', href: templatesIndex().url },
        ],
    },
});

const page = usePage();

const form = useForm({
    name: '',
    description: '',
});

function submitCreate() {
    form.post(templatesStore().url, {
        preserveScroll: true,
    });
}

function formatDate(isoString: string): string {
    return new Date(isoString).toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Templates" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Process Templates</h1>
        </div>

        <!-- Flash success message -->
        <div
            v-if="(page.props.flash as Record<string, string>)?.success"
            class="rounded-md bg-green-50 p-4 text-sm text-green-700 border border-green-200"
        >
            {{ (page.props.flash as Record<string, string>).success }}
        </div>

        <!-- Create form -->
        <div v-if="can.create" class="rounded-md border p-6">
            <h2 class="mb-4 text-lg font-medium">Tạo template mới</h2>
            <form @submit.prevent="submitCreate" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label for="template-name">Tên template <span class="text-destructive">*</span></Label>
                    <Input
                        id="template-name"
                        v-model="form.name"
                        placeholder="Nhập tên template..."
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-sm">{{ form.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label for="template-description">Mô tả</Label>
                    <Textarea
                        id="template-description"
                        v-model="form.description"
                        placeholder="Mô tả ngắn về quy trình này..."
                        rows="3"
                    />
                    <p v-if="form.errors.description" class="text-destructive text-sm">{{ form.errors.description }}</p>
                </div>

                <div>
                    <Button type="submit" :disabled="form.processing">
                        <span v-if="form.processing">Đang tạo...</span>
                        <span v-else>Tạo template</span>
                    </Button>
                </div>
            </form>
        </div>

        <!-- Templates table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tên template</TableHead>
                        <TableHead>Trạng thái</TableHead>
                        <TableHead>Số bước</TableHead>
                        <TableHead>Ngày tạo</TableHead>
                        <TableHead class="text-right">Thao tác</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="template in templates"
                        :key="template.id"
                    >
                        <TableCell class="font-medium">{{ template.name }}</TableCell>
                        <TableCell>
                            <Badge :variant="template.is_published ? 'default' : 'secondary'">
                                {{ template.is_published ? 'Published' : 'Draft' }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ template.step_count }}</TableCell>
                        <TableCell class="text-muted-foreground text-sm">
                            {{ formatDate(template.created_at) }}
                        </TableCell>
                        <TableCell class="text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                as="a"
                                :href="templatesShow({ process_template: template.id }).url"
                            >
                                Xem
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="templates.length === 0">
                        <TableCell colspan="5" class="text-muted-foreground py-8 text-center">
                            Chưa có template nào. Tạo template đầu tiên bên trên.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>
