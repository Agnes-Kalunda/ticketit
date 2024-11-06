<?php

namespace Ticket\Ticketit\Seeds;

use Illuminate\Database\Seeder;
use Ticket\Ticketit\Models\Setting;

class TicketitSettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            'assigned_notification' => ['default' => 'yes', 'value' => 'yes'],
            'comment_notification' => ['default' => 'yes', 'value' => 'yes'],
            'status_notification' => ['default' => 'yes', 'value' => 'yes'],
            'close_ticket_perm' => ['default' => '["admin" => "yes", "agent" => "yes", "owner" => "yes"]', 'value' => '["admin" => "yes", "agent" => "yes", "owner" => "yes"]'],
            'reopen_ticket_perm' => ['default' => '["admin" => "yes", "agent" => "yes", "owner" => "yes"]', 'value' => '["admin" => "yes", "agent" => "yes", "owner" => "yes"]'],
            'delete_modal_type' => ['default' => 'builtin', 'value' => 'builtin'],
            'agent_restrict' => ['default' => 'no', 'value' => 'no'],
            'main_route' => ['default' => 'tickets', 'value' => 'tickets'],
            'main_route_path' => ['default' => 'tickets', 'value' => 'tickets'],
            'admin_route' => ['default' => 'tickets-admin', 'value' => 'tickets-admin'],
            'admin_route_path' => ['default' => 'tickets-admin', 'value' => 'tickets-admin']
        ];

        foreach ($defaults as $slug => $setting) {
            Setting::firstOrCreate(
                ['slug' => $slug],
                [
                    'value' => $setting['value'],
                    'default' => $setting['default']
                ]
            );
        }
    }
}