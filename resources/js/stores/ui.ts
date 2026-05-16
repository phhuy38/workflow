import { useStorage } from '@vueuse/core';

export const useUiStore = () => {
    const dashboardFilters = useStorage('dashboard-filters', {
        search: '',
        template_id: '',
        status: '',
        executor_id: '',
    });

    return {
        dashboardFilters,
    };
};
