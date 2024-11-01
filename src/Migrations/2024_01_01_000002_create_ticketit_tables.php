<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketitTables extends Migration
{
    public function up()
    {
        // Categories table
        Schema::create('ticketit_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('color')->default('#000000');
            $table->timestamps();
        });

        // Priorities table
        Schema::create('ticketit_priorities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('color')->default('#000000');
            $table->timestamps();
        });

        // Statuses table
        Schema::create('ticketit_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('color')->default('#000000');
            $table->timestamps();
        });

        // Main tickets table
        Schema::create('ticketit', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('subject');
            $table->longText('content');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('priority_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('status_id')->references('id')->on('ticketit_statuses')
                  ->onDelete('restrict');
            $table->foreign('priority_id')->references('id')->on('ticketit_priorities')
                  ->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('ticketit_categories')
                  ->onDelete('restrict');
            $table->foreign('agent_id')->references('id')->on('users')
                  ->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')
                  ->onDelete('cascade');
        });

        // Comments table
        Schema::create('ticketit_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('content');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('ticketit')
                  ->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')
                  ->onDelete('cascade');
        });

        // Categories-Agents table
        Schema::create('ticketit_categories_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('ticketit_categories')
                  ->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticketit_categories_users');
        Schema::dropIfExists('ticketit_comments');
        Schema::dropIfExists('ticketit');
        Schema::dropIfExists('ticketit_statuses');
        Schema::dropIfExists('ticketit_priorities');
        Schema::dropIfExists('ticketit_categories');
    }
}