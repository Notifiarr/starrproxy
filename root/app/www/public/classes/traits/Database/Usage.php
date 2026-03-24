<?php

/*
----------------------------------
 ------  Created: 110524   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Usage
{
    public function getUsageTable()
    {
        $usage = [];

        $q = "SELECT *
              FROM " . USAGE_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $usage[] = $row;
        }

        return $usage;
    }

    public function deleteStarrAppUsage($starrId)
    {
        $q = "DELETE FROM " . USAGE_TABLE . "
              WHERE id = " . intval($starrId);
        $this->query($q);
    }

    public function getStarrAppUsage($appId)
    {
        $q   = "SELECT *
              FROM " . USAGE_TABLE . "
              WHERE app_id = " . intval($appId);
        $r   = $this->query($q);
        $row = $this->fetchAssoc($r);

        return $row ?: [];
    }
}
