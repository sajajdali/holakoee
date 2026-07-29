<?php

namespace Modules\Api\app\Http\Requests\Api\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Api\Http\Controllers\Profile\ProfileController;

class UpdateUserDietInformationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'updates' => ['required', 'array', 'min:1'], // باید یک آرایه باشه
            'updates.*.action' => [
                'required',
                Rule::in(ProfileController::getDefinedConstants()) // هر action باید داخل لیست ثابت‌ها باشه
            ],
            'updates.*.value' => ['required'], // هر value باید وجود داشته باشه
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
