<?php

declare(strict_types=1);

namespace BombenProdukt\Invoices\Data;

use Carbon\CarbonImmutable;
use BombenProdukt\Invoices\Contracts\Discount;
use BombenProdukt\Invoices\Data\Concerns\SumsMoney;
use BombenProdukt\Invoices\Money\Money;

final class Invoice extends AbstractData
{
    use SumsMoney;

    /**
     * @param array<int, InvoiceItem> $items
     * @param array<int, Discount>    $discounts
     */
    public function __construct(
        public readonly string $identifier,
        public readonly CarbonImmutable $date,
        public readonly Customer $customer,
        public readonly Vendor $vendor,
        public readonly array $items,
        public readonly ?array $discounts,
    ) {
        //
    }

    public function discount(): Money
    {
        $totalDiscount = $this->sumMoney(fn (InvoiceItem $item): Money => $item->discount());

        if (empty($this->discounts)) {
            return $totalDiscount;
        }

        $total = $this->sumMoney(fn (InvoiceItem $item): Money => $item->total());

        foreach ($this->discounts as $discount) {
            $totalDiscount = $discount->calculate($total);
        }

        return $totalDiscount;
    }

    public function subtotal(): Money
    {
        return $this->sumMoney(fn (InvoiceItem $item): Money => $item->subtotal());
    }

    public function tax(): Money
    {
        return $this->sumMoney(fn (InvoiceItem $item): Money => $item->tax());
    }

    public function total(): Money
    {
        return $this
            ->subtotal()
            ->add($this->tax())
            ->subtract($this->discount());
    }
}
