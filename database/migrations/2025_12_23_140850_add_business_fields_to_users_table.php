<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_code')->after('id'); // DECORA001 / DHARMA001
            $table->string('business_name')->nullable()->after('business_code'); // only for admin

            $table->enum('role', [
                'admin',
                'sales',
                'manager',
                'dispatcher',
                'accounts'
            ])->after('business_name');


            $table->string('mobile_number')->unique()->after('name'); // login username

            $table->string('gst_number')->nullable()->after('password');
            $table->text('address')->nullable()->after('gst_number');

            $table->boolean('is_active')->default(1)->after('address'); // 1 = active, 0 = blocked
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'business_code',
                'business_name',
                'role',
                'mobile_number',
                'gst_number',
                'address',
                'is_active'
            ]);
        });
    }
};
