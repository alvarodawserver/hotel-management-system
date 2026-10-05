<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * The webhook does not come from the payment provider (or is malformed).
 */
class InvalidWebhookSignature extends RuntimeException {}
