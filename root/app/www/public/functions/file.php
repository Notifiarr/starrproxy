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
        $contents = json_encode($contents);
    }

    file_put_contents($file, $contents);
}
