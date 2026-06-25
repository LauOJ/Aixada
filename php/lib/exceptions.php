<?php

/**
 * Custom exception classes for Aixada.
 * All exceptions extend PHP's base Exception class.
 *
 * @package Aixada
 * @subpackage Exceptions
 */

/** Thrown when a database query fails for reasons other than foreign key violations. */
class DataException extends Exception {}

/** Thrown when a foreign key constraint is violated (MySQL errors 1451 and 1452). */
class ForeignKeyException extends Exception {}

/** Thrown when a product does not have enough stock to fulfil an order. */
class InsufficientStockException extends Exception
{
    /**
     * @param int $product_id The product with insufficient stock.
     * @param int $quantity The quantity requested.
     * @param int $after The stock difference after the attempted operation.
     */
    public function __construct($product_id, $quantity, $after)
    {
        parent::__construct('Insufficient stock of product ' . $product_id . '. Wanted ' . $quantity . ', but only ' . ($quantity + $after) . ' available');
    }
}

/** Thrown when trying to order a product on a date that is not open for ordering. */
class DateException extends Exception
{
    /**
     * @param string $date The date that is not activated for ordering.
     */
    public function __construct($date)
    {
        parent::__construct('The date ' . $date . ' is not activated for ordering.');
    }
}

/** Thrown when an unexpected internal error occurs (e.g. wrong arguments, missing config). */
class InternalException extends Exception {}

/** Thrown when a user is not authenticated or does not have permission for an action. */
class AuthException extends Exception {}

/** Thrown when a login attempt fails. */
class SignonException extends Exception {}

/** Thrown when an XML response from the server cannot be parsed. */
class XMLParseException extends Exception
{
    /**
     * @param string $expected The expected XML token.
     * @param string $found The token that was actually found.
     * @param string $xml The full XML string being parsed.
     */
    public function __construct($expected, $found, $xml)
    {
        parent::__construct('XML parse error. Expected ' . $expected . ', found ' . $found . ' in ' . $xml);
    }
}

?>
