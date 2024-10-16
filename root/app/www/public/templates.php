<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/
?>

<div class="col-sm-12 p3">
    <h4>Template viewer</h4>
    <select class="form-select" id="template-selection" onchange="viewTemplate()"><?= getTemplateOptions() ?></select>
    <div id="template-viewer" class="mt-5"></div>
</div>
