<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicePurchasedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_purchased', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Foreign key for the user
            $table->dateTime('date'); // Date of purchase
            $table->decimal('subtotal', 10, 2); // Subtotal amount
            $table->decimal('paid_amount', 10, 2); // Amount paid
            $table->decimal('due_amount', 10, 2); // Due amount
            $table->string('status'); // Status of the purchase
            $table->text('client_note')->nullable(); // Client notes
            $table->text('admin_note')->nullable(); // Admin notes
            $table->decimal('discount_amount', 10, 2)->nullable(); // Discount amount
            $table->json('service_details')->nullable(); // JSON column for service details
            $table->timestamps(); // Created at and updated at timestamps

            // Foreign key constraint for user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('service_purchased');
    }
}
