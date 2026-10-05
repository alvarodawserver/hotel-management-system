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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('adults');
            $table->unsignedTinyInteger('children')->default(0);
            $table->string('guest_name');
            $table->string('guest_phone', 30);
            $table->text('special_requests')->nullable();
            $table->string('status')->index();

            // Price snapshot taken when booking (cents); later price or offer
            // changes never alter an existing reservation.
            $table->json('price_breakdown');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount');
            $table->unsignedInteger('total_price');

            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason')->nullable();

            $table->unsignedInteger('refund_amount')->default(0);
            $table->string('refund_status')->nullable();
            $table->string('stripe_refund_id')->nullable()->index();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->index(['room_id', 'status', 'check_in', 'check_out']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
