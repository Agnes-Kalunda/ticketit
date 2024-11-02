<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTicketitColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('users', 'ticketit_agent')) {
                $table->boolean('ticketit_agent')->default(false);
            }
            if (!Schema::hasColumn('users', 'ticketit_admin')) {
                $table->boolean('ticketit_admin')->default(false);
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before dropping
            $columns = [];
            
            if (Schema::hasColumn('users', 'ticketit_agent')) {
                $columns[] = 'ticketit_agent';
            }
            
            if (Schema::hasColumn('users', 'ticketit_admin')) {
                $columns[] = 'ticketit_admin';
            }
            
            // Only drop columns if they exist
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}