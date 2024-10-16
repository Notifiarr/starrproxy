<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function array_flatten_keep_keys($array)
{
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));

    foreach ($it as $key => $value) {
        $return[$key] = $value;
    }

    return $return;
}

function makeArray($array)
{
    return is_array($array) ? $array : [];
}
