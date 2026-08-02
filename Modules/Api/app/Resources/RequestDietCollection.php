<?php

namespace Modules\Api\app\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Api\Http\Controllers\Profile\DashboardController;
use Modules\Diet\Entities\DietRequest;

class RequestDietCollection extends ResourceCollection
{
    public $collects = RequestDietResourse::class;

    protected $user;

    public function __construct($resource, $user)
    {
        parent::__construct($resource);
        $this->user = $user;
    }

    public function toArray($request): array
    {
        return [
            'show_diet_banner' => app(DashboardController::class)->showDietBanner($this->user),
            'request_diets' => $this->collection,
            'diet_history' => RequestDietHistoryResource::collection(
                DietRequest::without('dietRequestDetails')
                    ->with('dietPlan')
                    ->where('user_id', $this->user->id)
                    ->orderByDesc('id')
                    ->get()
            ),
            'paginate' => [
                'current_page' => $this->currentPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'last_page' => $this->lastPage()
            ],
        ];
    }
}
