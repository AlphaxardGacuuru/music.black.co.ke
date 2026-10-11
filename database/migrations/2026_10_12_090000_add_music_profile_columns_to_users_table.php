<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Public handle, e.g. "@blackmusic". Profile URLs and the music
            // API address users by it.
            $table->string('username')->nullable()->unique()->after('name');
            // "normal" (fan) or "musician".
            $table->string('account_type')->default('normal')->after('gender');
            $table->string('backdrop')->default('img/headphones.jpg')->after('avatar');
            $table->string('bio')->nullable()->after('backdrop');
            $table->string('dob')->nullable()->after('bio');
            $table->string('location')->nullable()->after('dob');
            // The amount a musician's earnings must reach before they are paid out.
            $table->string('withdrawal')->default('1000')->after('location');
            $table->integer('decos')->default(0)->after('withdrawal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn([
                'username',
                'account_type',
                'backdrop',
                'bio',
                'dob',
                'location',
                'withdrawal',
                'decos',
            ]);
        });
    }
};
