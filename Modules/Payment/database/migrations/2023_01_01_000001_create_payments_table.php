<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->string('gateway');
            $table->string('gateway_payment_id')->nullable();
            $table->json('gateway_data')->nullable();
            $table->string('status')->default('pending');
            $table->morphs('payable');
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->json('metadata')->nullable();
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
        Schema::dropIfExists('payments');
    }
}
