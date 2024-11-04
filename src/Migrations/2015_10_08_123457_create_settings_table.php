```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticketit_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('lang')->unique()->nullable();
            $table->string('slug')->unique()->index();
            $table->text('value');  
            $table->text('default'); 
            $table->timestamps();
        });

        // Insert default settings
        $defaults = [
            [
                'lang' => null,
                'slug' => 'main_route',
                'value' => 'tickets',
                'default' => 'tickets',
            ],
            [
                'lang' => null,
                'slug' => 'main_route_path',
                'value' => 'tickets',
                'default' => 'tickets',
            ],
            [
                'lang' => null,
                'slug' => 'admin_route',
                'value' => 'tickets-admin',
                'default' => 'tickets-admin',
            ],
            [
                'lang' => null,
                'slug' => 'admin_route_path',
                'value' => 'tickets-admin',
                'default' => 'tickets-admin',
            ],
            [
                'lang' => null,
                'slug' => 'paginate_items',
                'value' => '10',
                'default' => '10',
            ],
            [
                'lang' => null,
                'slug' => 'default_status_id',
                'value' => '1',
                'default' => '1',
            ],
            [
                'lang' => null,
                'slug' => 'default_close_status_id',
                'value' => '3',
                'default' => '3',
            ],
            [
                'lang' => null,
                'slug' => 'default_reopen_status_id',
                'value' => '1',
                'default' => '1',
            ],
            [
                'lang' => null,
                'slug' => 'status_notification',
                'value' => '1',
                'default' => '1',
            ],
            [
                'lang' => null,
                'slug' => 'comment_notification',
                'value' => '1',
                'default' => '1',
            ],
            [
                'lang' => null,
                'slug' => 'close_ticket_perm',
                'value' => json_encode(['owner' => 'yes', 'agent' => 'yes', 'admin' => 'yes']),
                'default' => json_encode(['owner' => 'yes', 'agent' => 'yes', 'admin' => 'yes']),
            ],
            [
                'lang' => null,
                'slug' => 'reopen_ticket_perm',
                'value' => json_encode(['owner' => 'yes', 'agent' => 'yes', 'admin' => 'yes']),
                'default' => json_encode(['owner' => 'yes', 'agent' => 'yes', 'admin' => 'yes']),
            ],
        ];

        DB::table('ticketit_settings')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('ticketit_settings');
    }
}
