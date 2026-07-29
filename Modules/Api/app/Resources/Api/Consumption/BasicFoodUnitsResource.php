<?php

namespace Modules\Api\app\Resources\Api\Consumption;

use Illuminate\Http\Resources\Json\JsonResource;

class BasicFoodUnitsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_primary' => $this->pivot->is_primary == 1,
            'quantity_per_unit' => $this->pivot->quantity_per_unit,
            'max_allowed' => $this->pivot->max_allowed,
        ];
    }
}
