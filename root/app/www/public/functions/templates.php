<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getTemplateOptions()
{
    global $starrApps;

    $unsorted = [];
    foreach ($starrApps as $starrApp) {
        $dir = opendir('templates/' . $starrApp);
        while ($template = readdir($dir)) {
            if (!str_contains($template, '.json')) {
                continue;
            }
    
            $unsorted[$starrApp][$template] = $template;
        }
        closedir($dir);
    }

    //-- SORT & REMOVE EMPTY
    foreach ($unsorted as $starr => $templates) {
        krsort($unsorted[$starr]);

        if (!$unsorted[$starr]) {
            unset($unsorted[$starr]);
        }
    }

    $templateOptions = '';
    foreach ($unsorted as $starrApp => $templates) {
        $templateOptions .= '<optgroup label="'. ucfirst($starrApp) .'">';

        foreach ($templates as $template) {
            $templateOptions .= '<option value="' . $starrApp . '/' . $template . '">' . $template . '</option>';
        }
        $templateOptions .= '</optgroup>';
    }

    return '<option value="0">-- Select a template --</option>' . $templateOptions;
}
