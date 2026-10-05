<?php

namespace App\Services\Payments;

use RuntimeException;

/**
 * The payment provider rejected a request or could not be reached.
 */
class PaymentGatewayException extends RuntimeException {}
