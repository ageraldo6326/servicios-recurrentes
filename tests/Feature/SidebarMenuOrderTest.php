<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SidebarNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_personal_sidebar_order(): void
    {
        $user = User::factory()->create();
        $order = ['notebooks', 'follow-up', 'commitments', 'agenda', 'collections', 'invoices', 'gestions'];

        $this->actingAs($user)
            ->putJson(route('settings.sidebar-menu-order.update'), [
                'section' => 'daily',
                'order' => $order,
            ])
            ->assertNoContent();

        $this->assertSame($order, $user->fresh()->sidebar_menu_order['daily']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Cuadernos', 'Seguimiento de hoy']);
    }

    public function test_user_cannot_save_unknown_sidebar_options(): void
    {
        $user = User::factory()->create();
        $order = SidebarNavigation::itemKeys('daily');
        $order[] = 'unknown-option';

        $this->actingAs($user)
            ->putJson(route('settings.sidebar-menu-order.update'), [
                'section' => 'daily',
                'order' => $order,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');
    }
}
