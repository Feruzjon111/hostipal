<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unique(['region_id', 'name']);
        });
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('tin', 9)->unique();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('doctor')->index();
            $table->foreignId('hospital_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('phone', 30)->nullable();
            $table->string('specialization')->nullable();
        });
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('hospital_id')->constrained()->restrictOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100);
            $table->string('passport', 9);
            $table->string('pin', 14);
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('address', 500);
            $table->foreignId('disease_id')->constrained()->restrictOnDelete();
            $table->dateTime('registered_at')->index();
            $table->string('status')->default('waiting')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['doctor_id', 'passport']);
            $table->unique(['doctor_id', 'pin']);
            $table->index(['hospital_id', 'registered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hospital_id');
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone', 'specialization']);
        });
        Schema::dropIfExists('hospitals');
        Schema::dropIfExists('diseases');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regions');
    }
};
