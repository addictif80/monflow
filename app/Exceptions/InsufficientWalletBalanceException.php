<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown inside a locked wallet-debit transaction when the balance, checked
 * after acquiring the row lock, turns out to be insufficient — prevents a
 * race between concurrent requests from driving the balance negative.
 */
class InsufficientWalletBalanceException extends Exception
{
    public function __construct(public readonly float $balance)
    {
        parent::__construct("Insufficient wallet balance: {$balance}");
    }
}
