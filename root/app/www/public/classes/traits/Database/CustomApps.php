<?php

/*
----------------------------------
 ------  Created: 022426   ------
 ------  Joly0             ------
----------------------------------
*/

trait CustomApps
{
    //-- BOUND PARAMETERS, prepare() IS addslashes() WHICH DOES NOT ESCAPE QUOTES FOR SQLITE
    private function customAppsQuery($q, $params = [])
    {
        $stmt = $this->db->prepare($q);
        if (!$stmt) {
            return false;
        }

        foreach ($params as $param => $value) {
            $stmt->bindValue(':' . $param, $value, is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }

        return $stmt->execute();
    }

    public function getCustomApps()
    {
        $apps = [];
        $r    = $this->customAppsQuery("SELECT * FROM custom_apps ORDER BY label ASC");

        while ($r && $row = $r->fetchArray(SQLITE3_ASSOC)) {
            $apps[] = $row;
        }

        return $apps;
    }

    public function getCustomApp($id)
    {
        $r = $this->customAppsQuery("SELECT * FROM custom_apps WHERE id = :id", ['id' => intval($id)]);

        return ($r ? $r->fetchArray(SQLITE3_ASSOC) : []) ?: [];
    }

    public function addCustomApp($fields = [])
    {
        $r = $this->customAppsQuery("INSERT INTO custom_apps (name, label, api_version, openapi_url) VALUES (:name, :label, :api_version, :openapi_url)", [
            'name'        => $fields['name'],
            'label'       => $fields['label'],
            'api_version' => $fields['api_version'],
            'openapi_url' => $fields['openapi_url']
        ]);

        return $r ? '' : 'addCustomApp() :: ' . $this->db->lastErrorMsg();
    }

    public function updateCustomApp($id, $fields = [])
    {
        $columns = array_intersect(array_keys($fields), ['label', 'api_version', 'openapi_url', 'icon']);
        if (!$columns) {
            return;
        }

        $set = implode(', ', array_map(fn($column) => $column . ' = :' . $column, $columns));
        $r   = $this->customAppsQuery("UPDATE custom_apps SET " . $set . " WHERE id = :id", array_intersect_key($fields, array_flip($columns)) + ['id' => intval($id)]);

        return $r ? '' : 'updateCustomApp() :: ' . $this->db->lastErrorMsg();
    }

    public function deleteCustomApp($id)
    {
        $r = $this->customAppsQuery("DELETE FROM custom_apps WHERE id = :id", ['id' => intval($id)]);

        return $r ? '' : 'deleteCustomApp() :: ' . $this->db->lastErrorMsg();
    }
}
