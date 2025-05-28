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
        // Verificar se a tabela existe e eliminá-la
        if (Schema::hasTable('attendance_records')) {
            Schema::drop('attendance_records');
        }

        // Criar a tabela novamente com todas as colunas necessárias
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('entry_time')->nullable();
            $table->dateTime('exit_time')->nullable();
            $table->string('status')->default('registered');
            $table->timestamps();
            
            // Adicionar chave estrangeira
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};