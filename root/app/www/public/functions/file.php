<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getFile($file)
{
    $file = json_decode(file_get_contents($file), true);

    return $file;
}

function setFile($file, $contents)
{
    if (is_array($contents)) {
        $contents = json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        $contents = json_encode(json_decode($contents, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    if (!empty(json_decode($contents, true))) {
        file_put_contents($file, $contents);
    }
}
