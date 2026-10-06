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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // One review per stay. A review removed by an admin keeps its row,
            // so the stay cannot be reviewed again.
            $table->foreignId('reservation_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->timestamp('edited_at')->nullable();

            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamp('reported_at')->nullable()->index();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_reason')->nullable();

            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deletion_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['hotel_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
