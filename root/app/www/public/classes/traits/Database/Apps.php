<?php

/*
----------------------------------
 ------  Created: 110424   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Apps
{
    public function getAppsTable()
    {
        $apps = [];

        $appsTableCache = $this->cache->get(APPS_TABLE_CACHE_KEY);

        if ($appsTableCache) {
            $apps = json_decode($appsTableCache, true);
        }

        if (empty($apps)) {
            $q = "SELECT *
                  FROM " . APPS_TABLE . "
                  ORDER BY name ASC";
            $r = $this->query($q);
            while ($row = $this->fetchAssoc($r)) {
                $apps[] = $row;
            }

            $this->cache->set(APPS_TABLE_CACHE_KEY, json_encode($apps), APPS_TABLE_CACHE_TIME);
        }

        return $apps;
    }

    public function getAppFromId($appId, $appsTable)
    {
        $appsTable = $appsTable ?: $this->getAppsTable();

        foreach ($appsTable as $app) {
            if ($app['id'] == $appId) {
                return $app;
            }
        }

        return [];
    }

    public function addApp($fields = [])
    {
        $fieldList = $valueList = '';

        foreach ($fields as $field => $val) {
            $val = str_equals_any($field, ['endpoints']) ? $val : $this->prepare($val);

            $fieldList .= ($fieldList ? ', ' : '') . "`" . $field . "`";
            $valueList .= ($valueList ? ', ' : '') . "'" . $val . "'";
        }

        $q = "INSERT INTO " . APPS_TABLE . "
              (" . $fieldList . ")
              VALUES
              (" . $valueList . ")";
        $this->query($q);

        if ($this->error() != 'not an error') {
            return $this->error();
        }

        $this->cache->bust(APPS_TABLE_CACHE_KEY);

        return;
    }

    public function updateApp($appId, $fields = [])
    {
        $fieldList = '';

        foreach ($fields as $field => $val) {
            $val = str_equals_any($field, ['endpoints']) ? $val : $this->prepare($val);

            $fieldList .= ($fieldList ? ', ' : '') . "`" . $field . "` = '" . $val . "'";
            $fieldList .= ($fieldList ? ', ' : '') . "`" . $field . "` = '" . $val . "'";
        }

        $q = "UPDATE " . APPS_TABLE . "
              SET " . $fieldList . "
              WHERE id = " . intval($appId);
        $this->query($q);

        if ($this->error() != 'not an error') {
            return $this->error();
        }

        $this->cache->bust(APPS_TABLE_CACHE_KEY);

        return;
    }

    public function deleteApp($appId)
    {
        $q = "DELETE FROM " . APPS_TABLE . "
              WHERE id = " . intval($appId);
        $this->query($q);

        if ($this->error() != 'not an error') {
            return $this->error();
        }

        $this->cache->bust(APPS_TABLE_CACHE_KEY);

        return;
    }

    public function adjustAppUsage($appId, $code)
    {
        $field = str_equals_any($code, [401, 405]) ? 'rejected' : 'allowed';

        $q = "UPDATE " . USAGE_TABLE . "
              SET " . $field . " = " . $field . " + 1
              WHERE app_id = " . $appId;
        $this->query($q);

        if ($this->error() != 'not an error' || $this->affectedRows() == 0) {
            $q = "INSERT INTO " . USAGE_TABLE . "
                  ('app_id', 'allowed', 'rejected')
                  VALUES
                  ('" . $appId . "', " . ($field == 'allowed' ? 1 : 0) . ", " . ($field == 'rejected' ? 1 : 0) . ")";
            $this->query($q);
        }
    }

    public function getAppUsage($appId)
    {
        $q   = "SELECT *
              FROM " . USAGE_TABLE . "
              WHERE app_id = " . intval($appId);
        $r   = $this->query($q);
        $row = $this->fetchAssoc($r);

        return $row ?: [];
    }

    public function resetAppUsage($appId)
    {
        $q = "UPDATE " . USAGE_TABLE . "
              SET allowed = 0, rejected = 0
              WHERE app_id = " . intval($appId);
        $this->query($q);

        if ($this->error() != 'not an error') {
            return $this->error();
        }

        return;
    }
}
