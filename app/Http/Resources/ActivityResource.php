<?php

namespace App\Http\Resources;

use App\Models\ProcessInstance;
use App\Models\StepExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->formatDescription(),
            'causer_name' => $this->causer ? $this->causer->full_name : 'Hệ thống',
            'properties' => $this->properties,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'subject_type' => $this->formatSubjectType(),
            'subject_name' => $this->getSubjectName(),
        ];
    }

    private function formatDescription(): string
    {
        $map = [
            'created' => 'Khởi tạo',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'acknowledged' => 'Xác nhận nhận việc',
            'completed' => 'Hoàn thành',
            'overridden' => 'Ghi đè (Override)',
            'cancelled' => 'Hủy bỏ',
        ];

        return $map[$this->description] ?? $this->description;
    }

    private function formatSubjectType(): string
    {
        if ($this->subject instanceof ProcessInstance) {
            return 'Quy trình';
        }
        if ($this->subject instanceof StepExecution) {
            return 'Bước thực thi';
        }

        return 'Khác';
    }

    private function getSubjectName(): ?string
    {
        if ($this->subject) {
            return $this->subject->name;
        }

        return null;
    }
}
