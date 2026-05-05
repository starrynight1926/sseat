<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 80); // matches layout object id (obj_xxx)
            $table->string('type', 30)->default('chair');
            $table->string('label', 80)->nullable();
            $table->string('status', 20)->default('available');
            $table->float('pos_x')->default(0);
            $table->float('pos_y')->default(0);
            $table->timestamps();

            $table->unique(['floor_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chairs');
    }
};
