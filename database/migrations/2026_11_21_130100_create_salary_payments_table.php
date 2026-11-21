<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // the employee
            $table->string('employee_name');          // snapshot (survives staff deletion)
            $table->string('designation')->nullable(); // snapshot
            $table->string('month');                   // "YYYY-MM"
            $table->decimal('basic', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, paid
            $table->date('paid_on')->nullable();
            $table->string('method')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'month']);
        });

        // Permissions for the salary module (also added to the seeder for fresh installs)
        foreach (['salary.view', 'salary.manage'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            foreach (['Super Admin', 'School Admin', 'Principal', 'Accountant'] as $roleName) {
                if ($role = Role::where('name', $roleName)->first()) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
