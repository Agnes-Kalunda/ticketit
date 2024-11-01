<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketitTables extends Migration
{
    public function up()
    {
        // Statuses table
        Schema::create('ticketit_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        // Priorities table
        Schema::create('ticketit_priorities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        // Categories table
        Schema::create('ticketit_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        // Categories-Agents table
        Schema::create('ticketit_categories_users', function (Blueprint $table) {
            $table->integer('category_id')->unsigned();
            $table->unsignedBigInteger('user_id')->nullable();
        });

        // Main tickets table
        Schema::create('ticketit', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subject');
            $table->longText('content');
            $table->integer('status_id')->unsigned();
            $table->integer('priority_id')->unsigned();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->integer('category_id')->unsigned();
            $table->timestamps();
        });

        // Comments table
        Schema::create('ticketit_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->text('content');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('ticket_id')->unsigned();
            $table->timestamps();
        });

        // Audits table
        Schema::create('ticketit_audits', function (Blueprint $table) {
            $table->increments('id');
            $table->text('operation');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('ticket_id')->unsigned();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('ticketit_audits');
        Schema::drop('ticketit_comments');
        Schema::drop('ticketit');
        Schema::drop('ticketit_categories_users');
        Schema::drop('ticketit_categories');
        Schema::drop('ticketit_priorities');
        Schema::drop('ticketit_statuses');
    }
}