export * from './auth';
export * from './navigation';
export * from './ui';

export interface ProcessTemplate {
    id: number;
    name: string;
    description: string | null;
    is_published: boolean;
    step_count: number;
    created_at: string;
    version: number;
}
