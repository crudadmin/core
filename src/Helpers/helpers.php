<?php

use Illuminate\Database\Query\Expression;

if (! function_exists('trim_end')) {
    function trim_end($string, $trim)
    {
        while (substr($string, -strlen($trim)) == $trim) {
            $string = substr($string, 0, -strlen($trim));
        }

        return $string;
    }
}

function dbRaw($raw, $modelOrConnection = null)
{
    if ( $raw instanceof Expression ){
        $expression = $raw;
    } else {
        $expression = \DB::raw($raw);
    }

    $connection = $modelOrConnection;

    return $expression->getValue($connection->getQueryGrammar());
}
