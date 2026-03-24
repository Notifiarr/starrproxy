<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}
?>

<div class="col-sm-12 p3">
    <h2>Help</h2>
    <h5 class="mt-3 text-warning">Usage</h5>
    Add your starr apps to the system, then create proxy access for the starr app by adding a 3<sup>rd</sup> party app/script and settings its access permissions<br>

    <h5 class="mt-4 text-warning">App permissions and templates</h5>
    There are applied settings, templates & custom templates. Templates are not "saved" with the app but instead "applied" to the app as a way to quickly setup basic access for it.
    <ul>
        <li>Settings: This is what the system uses to allow/reject access to an app. It is the list of endpoints when you modify an app.</li>
        <li>Native template: This is a pre-built list of what an app uses that can be used as a starting point. It will disable/enable the endpoints needed to allow that app to function and then you can customize the selection after.</li>
        <li>Custom template: This has the same use case as the native ones but allow for custom apps or scripts to have a pre-built list of endpoints in case you add it multiple times.</li>
    </ul>

    <h5 class="mt-4 text-warning">Giving a new app access</h5>
    Existing templates:<br>
    <ul>
        <li>Select the template from the dropdown to automaticlally enable all needed endpoints/methods</li>
    </ul>
    No template:<br>
    <ul>
        <li>The inclusion method is suggested here. Add the app with no access and watch the logs for errors and enable the <b>needed</b> endpoints and methods</li>
        <li>The bottom of the log viewer will show all endpoints requested by the app</li>
    </ul>

    <h5 class="mt-4 text-warning">3<sup>rd</sup> party implementation</h5>
    You are going to use the URL and apikey from the proxy in all apps instead of the real starr url and apikey
    <ul>
        <li>App: notifiarr</li>
        <li>Instance: Radarr</li>
        <li>URL: <?= APP_URL ?></li>
        <li>Apikey: The one generated in the Radarr section for the app</li>
        <li>Open the notifiarr client, click Starr apps, use the proxy info</li>
    </ul>

    <h5 class="mt-4 text-warning">Instance names</h5>
    These names are pulled from the starr apps themself, rename them there and then click "Save Instance" and it will update them here.

    <h5 class="mt-4 text-warning">API Methods</h5>
    <ul>
        <li>get: Asking the api for data</li>
        <li>post: Asking the api to add something</li>
        <li>put: Asking the api to update something</li>
        <li>delete: Asking the api to remove something</li>
    </ul>
</div>
