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
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->text('options')->nullable();
            $table->integer('order');
            $table->timestamps();
        });

        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained('checklist_templates');
            $table->string('status');
            $table->string('title');
            $table->text('description');
            $table->string('type');
            $table->string('visibility');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('checklist_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('checklists');
            $table->foreignId('user_id')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type');
            $table->text('options')->nullable();
            $table->integer('order');
            $table->string('response');
            $table->text('notes');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamps();
        });
        

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
        Schema::dropIfExists('checklist_template_items');
        Schema::dropIfExists('checklists');
        Schema::dropIfExists('checklist_responses');
    }
};
