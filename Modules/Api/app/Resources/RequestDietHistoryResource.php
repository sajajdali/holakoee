<?php

namespace Modules\Api\app\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Diet\Enum\DietRequestStatusEnum;

class RequestDietHistoryResource extends JsonResource
{
    private function calculatePercentage($total, $part)
    {
        if ($total == 0) {
            return 0;
        }

        return ($part / $total) * 100;
    }

    private function statusLabel(): string
    {
        if ($this->status instanceof DietRequestStatusEnum) {
            return $this->status->getJustName();
        }

        return DietRequestStatusEnum::tryFrom((int) $this->status)?->getJustName() ?? '';
    }

    private function statusBadge(): array
    {
        $status = $this->status instanceof DietRequestStatusEnum
            ? $this->status
            : DietRequestStatusEnum::tryFrom((int) $this->status);

        if ($this->end_date && Carbon::parse($this->end_date)->isPast()) {
            return [
                'title' => 'تمام شده',
                'class' => 'bg-secondary',
                'color' => '#6c757d',
            ];
        }

        if ((int) $this->active === 0) {
            return [
                'title' => 'غیر فعال',
                'class' => 'bg-secondary',
                'color' => '#6c757d',
            ];
        }

        return match ($status) {
            DietRequestStatusEnum::ACTIVE => [
                'title' => 'فعال',
                'class' => 'bg-success',
                'color' => '#198754',
            ],
            DietRequestStatusEnum::PENDING => [
                'title' => 'در انتظار',
                'class' => 'bg-warning',
                'color' => '#ffc107',
            ],
            DietRequestStatusEnum::REQUESTED => [
                'title' => 'درخواست شده',
                'class' => 'bg-info',
                'color' => '#0dcaf0',
            ],
            DietRequestStatusEnum::REJECTED => [
                'title' => 'رد شده',
                'class' => 'bg-danger',
                'color' => '#dc3545',
            ],
            DietRequestStatusEnum::REJECT_BY_SYSTEM => [
                'title' => 'غیر فعال شده توسط سیستم',
                'class' => 'bg-secondary',
                'color' => '#6c757d',
            ],
            DietRequestStatusEnum::REJECT_BY_SYSTEM_HAVE_ERROR => [
                'title' => 'عدم تجویز - اخطار در هنگام تجویز',
                'class' => 'bg-secondary',
                'color' => '#6c757d',
            ],
            DietRequestStatusEnum::END => [
                'title' => 'تمام شده',
                'class' => 'bg-secondary',
                'color' => '#6c757d',
            ],
            default => [
                'title' => $this->statusLabel(),
                'class' => 'bg-secondary',
                'color' => '#6c757d',
            ],
        };
    }

    public function toArray($request): array
    {
        $startDate = $this->start_date ? Carbon::parse($this->start_date) : null;
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : null;
        $total = $startDate && $endDate ? $endDate->diffInDays($startDate) : 0;
        $currentDay = $startDate && $endDate && $endDate->isFuture()
            ? $startDate->diffInDays(Carbon::now()) + 1
            : 0;
        $statusBadge = $this->statusBadge();

        return [
            'id' => $this->id,
            'top_chart' => [
                'total_day' => $total,
                'current_day' => $currentDay,
                'percent' => round($this->calculatePercentage($total, $currentDay)),
            ],
            'diet_plan_name' => $this->dietPlan?->name,
            'start_weight' => $this->user_information['WEIGHT'] ?? '',
            'status' => $endDate ? $endDate->isFuture() : false,
            'status_code' => $this->status instanceof DietRequestStatusEnum ? $this->status->value : (int) $this->status,
            'active' => (bool) $this->active,
            'status_title' => $statusBadge['title'],
            'status_badge' => $statusBadge,
            'start_date' => $this->start_date ? verta($this->start_date)->format('Y/m/d') : '',
            'end_date' => $this->end_date ? verta($this->end_date)->format('Y/m/d') : '',
            'calories' => $this->calories,
        ];
    }
}
