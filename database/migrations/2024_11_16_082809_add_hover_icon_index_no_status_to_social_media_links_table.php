<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHoverIconIndexNoStatusToSocialMediaLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_media_links', function (Blueprint $table) {
            $table->string('hover_icon')->nullable()->after('icon'); // For hover icon
            $table->integer('index_no')->nullable()->after('hover_icon'); // For custom sorting
            $table->boolean('status')->default(true)->after('index_no'); // For enabling/disabling links
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('social_media_links', function (Blueprint $table) {
            $table->dropColumn(['hover_icon', 'index_no', 'status']);
        });
    }
}
