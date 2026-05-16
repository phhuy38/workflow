<?php

namespace App\Services;

use App\Models\ProcessInstance;
use App\Models\StepExecution;
use Illuminate\Support\Carbon;

class InstanceStatusCalculator
{
    /**
     * Tính toán trạng thái Traffic Light cho một ProcessInstance.
     * Trả về: 'critical' (đỏ), 'warning' (vàng), hoặc 'normal' (xanh).
     */
    public static function calculate(ProcessInstance $instance): string
    {
        if ($instance->status->getValue() !== 'running') {
            return 'normal';
        }

        /** @var StepExecution|null $activeStep */
        $activeStep = $instance->stepExecutions
            ->filter(fn (StepExecution $step) => in_array($step->status->getValue(), ['pending', 'in_progress']))
            ->first();

        if (! $activeStep) {
            return 'normal';
        }

        if (is_null($activeStep->deadline_at) || is_null($activeStep->created_at)) {
            return 'normal';
        }

        $now = now();
        $deadline = Carbon::parse($activeStep->deadline_at);
        $createdAt = Carbon::parse($activeStep->created_at);

        // 1. Critical (Red): Quá hạn
        if ($now->greaterThan($deadline)) {
            return 'critical';
        }

        // 2. Warning (Yellow): Chưa acknowledge sau 1 giờ
        if ($activeStep->status->getValue() === 'pending') {
            if ($createdAt->diffInMinutes($now) >= 60) {
                return 'warning';
            }
        }

        // 3. Warning (Yellow): Thời gian còn lại <= 30%
        $totalDurationMinutes = $createdAt->diffInMinutes($deadline);
        if ($totalDurationMinutes > 0) {
            $remainingMinutes = $now->diffInMinutes($deadline, false);
            $remainingPercentage = ($remainingMinutes / $totalDurationMinutes) * 100;

            if ($remainingPercentage <= 30) {
                return 'warning';
            }
        }

        // 4. Normal (Green)
        return 'normal';
    }
}
