<?php

/*
----------------------------------
 ------  Created: 111224   ------
 ------  Austin Best	   ------
----------------------------------
*/

function gitBranch()
{
    if (!defined('DOCKERFILE_BRANCH')) {
        return 'Source';
    }

    return DOCKERFILE_BRANCH;
}

function gitHash()
{
    if (!defined('DOCKERFILE_COMMIT')) {
        return 'Unknown';
    }

    return DOCKERFILE_COMMIT;
}

function gitMessage()
{
    if (!defined('DOCKERFILE_COMMIT_MSG')) {
        return 'Unknown';
    }

    return DOCKERFILE_COMMIT_MSG;
}

function gitVersion($full = false)
{
    if (!defined('DOCKERFILE_COMMITS')) {
        return ($full ? 'v' : '') . '0.0.0' . ($full ? ' - ' . gitBranch() : '');
    }

    if ($full) {
        return 'v' . APP_X . '.' . APP_Y . '.' . DOCKERFILE_COMMITS . ' - ' . gitBranch();
    }

    return APP_X . '.' . APP_Y . '.' . DOCKERFILE_COMMITS;
}
