<?php

namespace App\Actions\Pricing;

use Illuminate\Contracts\Support\Arrayable;

/**
 * The price of a stay in one room, night by night. All amounts in euro cents.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class StayPriceBreakdown implements Arrayable
{
    /**
     * @param  list<array{date: string, base: int, discount_percent: int, price: int}>  $nights
     */
    public function __construct(
        public array $nights,
        public int $subtotal,
        public int $discount,
        public int $total,
    ) {}

    public function nightCount(): int
    {
        return count($this->nights);
    }

    /**
     * The largest discount applied to any night (0 when no offer applies).
     */
    public function bestDiscountPercent(): int
    {
        return $this->nights === [] ? 0 : max(array_column($this->nights, 'discount_percent'));
    }

    /**
     * @return array{nights: list<array{date: string, base: int, discount_percent: int, price: int}>, night_count: int, subtotal: int, discount: int, total: int, best_discount_percent: int}
     */
    public function toArray(): array
    {
        return [
            'nights' => $this->nights,
            'night_count' => $this->nightCount(),
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'best_discount_percent' => $this->bestDiscountPercent(),
        ];
    }
}
