<?php

namespace Ticket\Ticketit\Helpers;

use Illuminate\Support\Facades\Log;

class TicketitLogger
{
    /**
     * Log an info message
     */
    public static function info($message, array $context = [])
    {
        Log::info('[Ticketit] ' . $message, $context);
    }

    /**
     * Log an error message
     */
    public static function error($message, array $context = [])
    {
        Log::error('[Ticketit] ' . $message, $context);
    }

    /**
     * Log a warning message
     */
    public static function warning($message, array $context = [])
    {
        Log::warning('[Ticketit] ' . $message, $context);
    }

    /**
     * Log a debug message
     */
    public static function debug($message, array $context = [])
    {
        Log::debug('[Ticketit] ' . $message, $context);
    }
}