<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketitTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticketit_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        Schema::create('ticketit_priorities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        Schema::create('ticketit_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color');
        });

        Schema::create('ticketit_categories_users', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('user_id')->nullable();
        });

        Schema::create('ticketit', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subject');
            $table->longText('content');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('priority_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
        });

        Schema::create('ticketit_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->text('content');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('ticket_id');
            $table->timestamps();
        });

        Schema::create('ticketit_audits', function (Blueprint $table) {
            $table->increments('id');
            $table->text('operation');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('ticket_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
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