<script setup lang="ts">
import { watch, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useUiStore } from '@/stores/ui';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Search, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { useDebounceFn } from '@vueuse/core';

interface FilterOptions {
    templates: { id: number; name: string }[];
    executors: { id: number; name: string }[];
}

const props = defineProps<{
    options: FilterOptions;
}>();

const { dashboardFilters } = useUiStore();

const searchInput = ref(dashboardFilters.value.search);

const applyFilters = () => {
    router.get(
        '/dashboard',
        {
            search: dashboardFilters.value.search,
            template_id: dashboardFilters.value.template_id,
            status: dashboardFilters.value.status,
            executor_id: dashboardFilters.value.executor_id,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['instances', 'filters'],
        }
    );
};

const debouncedApply = useDebounceFn(() => {
    dashboardFilters.value.search = searchInput.value;
    applyFilters();
}, 400);

const clearFilters = () => {
    dashboardFilters.value = {
        search: '',
        template_id: '',
        status: '',
        executor_id: '',
    };
    searchInput.value = '';
    applyFilters();
};

const hasActiveFilters = () => {
    return dashboardFilters.value.search || dashboardFilters.value.template_id || dashboardFilters.value.status || dashboardFilters.value.executor_id;
};

// Sync internal search input if external changes (e.g. clear)
watch(() => dashboardFilters.value.search, (val) => {
    if (val !== searchInput.value) {
        searchInput.value = val;
    }
});

</script>

<template>
    <div class="flex flex-col sm:flex-row gap-3 mb-6 items-center">
        <div class="relative w-full sm:max-w-xs">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input 
                v-model="searchInput" 
                @input="debouncedApply"
                placeholder="Tìm tên quy trình..." 
                class="pl-8" 
            />
        </div>

        <Select v-model="dashboardFilters.template_id" @update:modelValue="applyFilters">
            <SelectTrigger class="w-full sm:w-[180px]">
                <SelectValue placeholder="Tất cả quy trình" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="">Tất cả quy trình</SelectItem>
                <SelectItem v-for="template in options.templates" :key="template.id" :value="template.id.toString()">
                    {{ template.name }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Select v-model="dashboardFilters.status" @update:modelValue="applyFilters">
            <SelectTrigger class="w-full sm:w-[160px]">
                <SelectValue placeholder="Đang chạy" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="App\States\ProcessInstance\Running">Đang chạy</SelectItem>
                <SelectItem value="App\States\ProcessInstance\Completed">Đã hoàn thành</SelectItem>
                <SelectItem value="App\States\ProcessInstance\Cancelled">Đã hủy</SelectItem>
            </SelectContent>
        </Select>

        <Select v-model="dashboardFilters.executor_id" @update:modelValue="applyFilters">
            <SelectTrigger class="w-full sm:w-[160px]">
                <SelectValue placeholder="Tất cả người làm" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="">Tất cả người làm</SelectItem>
                <SelectItem v-for="user in options.executors" :key="user.id" :value="user.id.toString()">
                    {{ user.name }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Button 
            v-if="hasActiveFilters()" 
            variant="ghost" 
            size="sm" 
            @click="clearFilters" 
            class="h-9 px-2 text-muted-foreground hover:text-foreground"
            title="Xóa bộ lọc"
        >
            <X class="h-4 w-4" />
        </Button>
    </div>
</template>
