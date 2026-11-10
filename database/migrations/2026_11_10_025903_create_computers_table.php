
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('computers', function (Blueprint $table) {
            $table->id();

            $table->string('computer_name');
            $table->string('office');

            $table->string('ram')->nullable();
            $table->string('cpu')->nullable();
            $table->string('ssd')->nullable();
            $table->string('hd')->nullable();

            $table->string('brand')->nullable();
            $table->string('model')->nullable();

            $table->string('serial_number')->nullable()->unique();
            $table->string('mac_address')->nullable()->unique();

            $table->string('status')->default('Working');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('office');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
