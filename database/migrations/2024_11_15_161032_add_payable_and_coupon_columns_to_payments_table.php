<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayableAndCouponColumnsToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payable_type')->nullable()->after('status');
            $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');
            $table->unsignedBigInteger('coupon_id')->nullable()->after('payable_id');

            // Add foreign key constraint for coupon_id if needed
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['payable_type', 'payable_id', 'coupon_id']);
        });
    }
}
