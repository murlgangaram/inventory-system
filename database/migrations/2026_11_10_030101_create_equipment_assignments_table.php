
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();

            // Computer or printer
            $table->string('equipment_type');
            $table->unsignedBigInteger('equipment_id');

            // Assigned employee
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('assigned_at');
            $table->date('returned_at')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['equipment_type', 'equipment_id']);
            $table->index('employee_id');
        });

        // Prevent an equipment item from having
        // multiple active assignments.
        DB::statement("
            CREATE UNIQUE INDEX equipment_assignments_one_active
            ON equipment_assignments (equipment_type, equipment_id)
            WHERE returned_at IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS equipment_assignments_one_active'
        );

        Schema::dropIfExists('equipment_assignments');
    }
};
