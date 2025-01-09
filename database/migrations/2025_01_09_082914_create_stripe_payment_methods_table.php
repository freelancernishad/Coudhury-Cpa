<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripePaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stripe_customer_id'); // Link to the Stripe customer
            $table->string('stripe_payment_method_id')->unique(); // Stripe payment method ID
            $table->json('details')->nullable(); // Payment method details (card/bank)
            $table->boolean('is_default')->default(false); // Mark as default payment method
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('stripe_customer_id')->references('id')->on('stripe_customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stripe_payment_methods');
    }
}
