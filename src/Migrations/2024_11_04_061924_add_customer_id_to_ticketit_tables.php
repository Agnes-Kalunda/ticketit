<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerIdToTicketitTables extends Migration
{
    public function up()
    {
        // Add customer_id to ticketit table
        Schema::table('ticketit', function (Blueprint $table) {
            if (!Schema::hasColumn('ticketit', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('customers')
                      ->onDelete('cascade');
            }
        });

        // Add customer_id to comments table
        Schema::table('ticketit_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('ticketit_comments', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('customers')
                      ->onDelete('cascade');
            }
        });

        // Add customer_id to audits table
        Schema::table('ticketit_audits', function (Blueprint $table) {
            if (!Schema::hasColumn('ticketit_audits', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('customers')
                      ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('ticketit_audits', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('ticketit_comments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
}