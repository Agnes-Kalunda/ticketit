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
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->string('name');
            $table->string('color');
        });

        Schema::create('ticketit_priorities', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->string('name');
            $table->string('color');
        });

        Schema::create('ticketit_categories', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->string('name');
            $table->string('color');
        });

        Schema::create('ticketit_categories_users', function (Blueprint $table) {
            $table->bigInteger('category_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            
            $table->foreign('category_id')->references('id')->on('ticketit_categories');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('ticketit', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->string('subject');
            $table->longText('content');
            $table->bigInteger('status_id')->unsigned();
            $table->bigInteger('priority_id')->unsigned();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('agent_id')->unsigned()->nullable();
            $table->bigInteger('category_id')->unsigned();
            $table->timestamps();

            $table->foreign('status_id')->references('id')->on('ticketit_statuses');
            $table->foreign('priority_id')->references('id')->on('ticketit_priorities');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('agent_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('ticketit_categories');
        });

        Schema::create('ticketit_comments', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->text('content');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('ticket_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('ticket_id')->references('id')->on('ticketit');
        });

        Schema::create('ticketit_audits', function (Blueprint $table) {
            $table->bigInteger('id')->unsigned()->autoIncrement();
            $table->text('operation');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->bigInteger('ticket_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('ticket_id')->references('id')->on('ticketit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ticketit_audits');
        Schema::dropIfExists('ticketit_comments');
        Schema::dropIfExists('ticketit');
        Schema::dropIfExists('ticketit_categories_users');
        Schema::dropIfExists('ticketit_categories');
        Schema::dropIfExists('ticketit_priorities');
        Schema::dropIfExists('ticketit_statuses');
    }
}