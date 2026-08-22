<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSidebarMenuOrderRequest;
use App\Support\SidebarNavigation;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final class SidebarMenuOrderController extends Controller
{
    public function update(UpdateSidebarMenuOrderRequest $request): Response
    {
        $data = $request->validated();

        if (! SidebarNavigation::hasValidOrder($data['section'], $data['order'])) {
            throw ValidationException::withMessages([
                'order' => 'El orden del menú contiene opciones no permitidas.',
            ]);
        }

        $user = $request->user();
        $menuOrder = $user->sidebar_menu_order ?? [];
        $menuOrder[$data['section']] = array_values($data['order']);

        $user->update(['sidebar_menu_order' => $menuOrder]);

        return response()->noContent();
    }
}
