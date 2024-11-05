<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTimestampsToTicketitTables extends Migration
{
    public function up()
    {
        Schema::table('ticketit_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('ticketit_categories', 'created_at')) {
                $table->timestamps();
            }
        });

        Schema::table('ticketit_priorities', function (Blueprint $table) {
            if (!Schema::hasColumn('ticketit_priorities', 'created_at')) {
                $table->timestamps();
            }
        });

        Schema::table('ticketit_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('ticketit_statuses', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        Schema::table('ticketit_categories', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('ticketit_priorities', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
}