export interface StepDefinition {
    id: number;
    template_id: number;
    name: string;
    description: string | null;
    order: number;
    assignee_type: 'user' | 'role' | 'department' | null;
    assignee_id: number | null;
    duration_hours: number;
    is_required: boolean;
    created_at: string;
}

export interface StepFormData {
    template_id: number;
    name: string;
    description: string;
    assignee_type: 'user' | 'role' | 'department' | null;
    assignee_id: number | null;
    duration_hours: number;
    is_required: boolean;
}
