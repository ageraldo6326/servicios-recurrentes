<?php

namespace App\Http\Requests;

use App\Support\SidebarNavigation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSidebarMenuOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'section' => ['required', 'string', Rule::in(SidebarNavigation::sectionKeys())],
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'string', 'distinct'],
        ];
    }
}
