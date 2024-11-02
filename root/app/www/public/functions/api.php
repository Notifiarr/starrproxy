<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function generateApikey($length = 32)
{
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

function apiResponse($code, $response, $responseHeaders = [])
{
    session_unset();
    session_destroy();

    http_response_code($code);

    $response = is_array($response) ? json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : json_decode(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), true);

    if ($responseHeaders) {
        foreach ($responseHeaders as $rhKey => $rhVal) {
            header($rhKey . ': ' . $rhVal[0]);
        }
    } else {
        header('Content-Length: ' . strlen($response));
        header('Access-Control-Allow-Origin: *');

        if ($response) {
            header('Content-Type: application/json');
        }
    }

    if ($response) {
        echo $response;
    }

    die();
}
