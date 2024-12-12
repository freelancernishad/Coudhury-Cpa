<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdAndUserTypeToTokenBlacklistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('token_blacklists', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('token');
            $table->string('user_type')->default('user')->after('user_id');
            $table->string('date')->after('user_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('token_blacklists', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'user_type','date']); // Drop the columns if rolling back
        });
    }
}
