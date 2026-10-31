<?php

namespace App\Enums;

enum ShippingCarrier: string
{
    case Usps = 'usps';
    case Ups = 'ups';
    case Fedex = 'fedex';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Usps => 'USPS',
            self::Ups => 'UPS',
            self::Fedex => 'FedEx',
            self::Other => 'Other',
        };
    }

    public function trackingUrl(?string $trackingNumber): ?string
    {
        if (! $trackingNumber) {
            return null;
        }

        return match ($this) {
            self::Usps => preg_match('/^\d{20,22}$/', $trackingNumber)
                ? 'https://tools.usps.com/go/TrackConfirmAction?tLabels='.rawurlencode($trackingNumber)
                : null,
            self::Ups => preg_match('/^1Z[0-9A-Z]{16}$/i', $trackingNumber)
                ? 'https://www.ups.com/track?tracknum='.rawurlencode($trackingNumber)
                : null,
            self::Fedex => preg_match('/^\d{12}$/', $trackingNumber)
                ? 'https://www.fedex.com/fedextrack/?trknbr='.rawurlencode($trackingNumber)
                : null,
            self::Other => null,
        };
    }
}
