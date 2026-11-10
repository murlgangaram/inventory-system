
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();

            $table->string('printer_name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();

            $table->string('serial_number')->nullable()->unique();

            $table->string('office');
            $table->string('status')->default('Working');
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('office');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printers');
    }
};
