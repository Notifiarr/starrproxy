<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getTemplateOptions()
{
    $templateList = getTemplateList();

    $templateOptions = '';
    foreach ($templateList as $app => $appTemplates) {
        $templateOptions .= '<optgroup label="' . $app . '">';

        foreach ($appTemplates as $appTemplate) {
            $custom = str_contains($appTemplate['location'], APP_USER_TEMPLATES_PATH);

            if (TEMPLATE_ORDER == 1) {
                $templateOptions .= '<option value="' . $appTemplate['location'] . $appTemplate['item'] . '/' . $app . '.json">' . $appTemplate['item'] . ($custom ? ' [Custom]' : '') . '</option>';
            } elseif (TEMPLATE_ORDER == 2) {
                $templateOptions .= '<option value="' . $appTemplate['location'] . $app . '/' . $appTemplate['item'] . '.json">' . $appTemplate['item'] . ($custom ? ' [Custom]' : '') . '</option>';
            }
        }
        $templateOptions .= '</optgroup>';
    }

    return '<option value="0">-- Select a template --</option>' . $templateOptions;
}

function getTemplateList()
{
    $templateLocations = [ABSOLUTE_PATH . 'templates/', APP_USER_TEMPLATES_PATH];
    $list              = [];
    foreach (StarrApps::LIST as $starrApp) {
        foreach ($templateLocations as $templateLocation) {
            if (is_dir($templateLocation . $starrApp)) {
                $dir = opendir($templateLocation . $starrApp);
                while ($template = readdir($dir)) {
                    if (!str_contains($template, '.json')) {
                        continue;
                    }

                    $template = str_replace('.json', '', $template);
                    if (TEMPLATE_ORDER == 1) {
                        $list[$template][] = ['location' => $templateLocation, 'item' => $starrApp];
                    } elseif (TEMPLATE_ORDER == 2) {
                        $list[$starrApp][] = ['location' => $templateLocation, 'item' => $template];
                    }
                }
                closedir($dir);
                ksort($list);
            }
        }
    }

    return $list;
}
