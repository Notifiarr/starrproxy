function testStarr(starrId, app)
{
    if (!$('#instance-url-' + starrId).val()) {
        toast('Starr test', 'The url is required before testing', 'error');
        return;
    }

    if (!$('#instance-apikey-' + starrId).val()) {
        toast('Starr test', 'The apikey is required before testing', 'error');
        return;
    }

    let apikey = $('#instance-apikey-' + starrId).val();
    if ($('#instance-apikey-' + starrId).val().includes('..')) {
        apikey = $('#instance-apikey-' + starrId).data('apikey');
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=testStarr&starrId=' + starrId + '&url=' + $('#instance-url-' + starrId).val() + '&apikey=' + apikey + '&app=' + app,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Starr test', resultData.error, 'error');
                return;
            }

            toast('Starr test', resultData.result, 'success');
        }
    });
}
// -------------------------------------------------------------------------------------------
function saveStarr(starrId, app)
{
    const apikey = $('#instance-apikey-' + starrId).val();
    if (apikey.includes('..')) {
        $('#instance-apikey-' + starrId).val($('#instance-apikey-' + starrId).data('apikey'));
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=saveStarr&starrId=' + starrId + '&app=' + app + '&url=' + $('#instance-url-' + starrId).val() + '&apikey=' + $('#instance-apikey-' + starrId).val() + '&username=' + encodeURIComponent($('#instance-username-' + starrId).val()) + '&password=' + encodeURIComponent($('#instance-password-' + starrId).val()),
        success: function (resultData) {
            if (resultData) {
                toast('Starr apps', resultData, 'error');
                return;
            }

            reload();
        }
    });
}
// -------------------------------------------------------------------------------------------
function deleteStarr(starrId, app)
{
    if (confirm('Are you sure you want to delete this instance?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/starr.php',
            data: '&m=deleteStarr&starrId=' + starrId + '&app=' + app,
            success: function (resultData) {
                if (resultData) {
                    toast('Starr apps', resultData, 'error');
                    return;
                }

                reload();
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
function openAddApp()
{
    dialogOpen({
        id: 'addApp',
        title: 'Add app',
        size: 'lg',
        body: $('#add-app-template').html(),
        footer: '<button class="btn btn-outline-info" onclick="testAddApp()">Test</button> <button class="btn btn-success" onclick="saveAddApp()">Add</button>',
        onOpen: function () {
            $('#add-app-select').on('change', function () {
                const selectedApp = $(this).val();
                $('#add-app-custom-fields').toggleClass('d-none', selectedApp !== '__new__');
                $('#add-app-instance-fields').toggleClass('d-none', !selectedApp);
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function getAddAppDetails()
{
    const selectedApp = $('#add-app-select').val();
    if (!selectedApp) {
        toast('Add app', 'Select an app first', 'error');
        return null;
    }

    const app = selectedApp === '__new__' ? $.trim($('#custom-app-name').val()).toLowerCase() : selectedApp;
    const url = $.trim($('#add-app-url').val());
    const apikey = $.trim($('#add-app-apikey').val());

    if (!app) {
        toast('Add app', 'The custom app name is required', 'error');
        return null;
    }
    if (!url || !apikey) {
        toast('Add app', 'URL and API key are required', 'error');
        return null;
    }

    return { app: app, url: url, apikey: apikey };
}
// -------------------------------------------------------------------------------------------
function addAppError(message, definitionCreated)
{
    loadingStop();

    if (definitionCreated) {
        message += ' The custom app definition was saved, but no instance was added. It remains available in the Custom group the next time you open Add app.';
    }

    toast('Add app', message, 'error');
}
// -------------------------------------------------------------------------------------------
function testAddApp()
{
    //-- A new custom app has no definition to test against yet, Add creates it and then tests
    if ($('#add-app-select').val() === '__new__') {
        toast('Add app', 'Add creates the custom app and tests the connection before saving the instance', 'info');
        return;
    }

    const details = getAddAppDetails();
    if (!details) {
        return;
    }

    //-- No loading box for a test, the button shows it is running
    const testButton = $('#addApp .btn-outline-info').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing');

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=testStarr&app=' + encodeURIComponent(details.app) + '&url=' + encodeURIComponent(details.url) + '&apikey=' + encodeURIComponent(details.apikey),
        dataType: 'json',
        complete: function () {
            testButton.prop('disabled', false).text('Test');
        },
        error: function (xhr) {
            addAppError('Connection test failed (HTTP ' + xhr.status + ')', false);
        },
        success: function (resultData) {
            if (resultData.error) {
                toast('Add app', resultData.error, 'error');
                return;
            }

            toast('Add app', resultData.result, 'success');
        }
    });
}
// -------------------------------------------------------------------------------------------
function saveAddApp()
{
    const details = getAddAppDetails();
    if (!details) {
        return;
    }

    loadingStart();

    if ($('#add-app-select').val() === '__new__') {
        createAddAppDefinition(details, function () {
            testAndSaveAddApp(details, true);
        });
        return;
    }

    testAndSaveAddApp(details, false);
}
// -------------------------------------------------------------------------------------------
function createAddAppDefinition(details, callback)
{
    const iconFile = $('#custom-app-icon')[0].files[0];

    $.post('ajax/customapps.php', {
        m: 'saveCustomApp',
        id: 0,
        name: details.app,
        label: $('#custom-app-label').val(),
        api_version: $('#custom-app-api-version').val(),
        openapi_url: $('#custom-app-openapi-url').val()
    }, null, 'json').done(function (saved) {
        if (saved.error) {
            addAppError(saved.error, false);
            return;
        }

        //-- Select the saved definition so a retry after a failed instance step does not create it again
        const customAppLabel = $('#custom-app-label').val();
        $('#add-app-select optgroup[label="Custom"]').append(new Option(customAppLabel, details.app));
        $($('#add-app-template')[0].content).find('optgroup[label="Custom"]').append(new Option(customAppLabel, details.app));
        $('#add-app-select').val(details.app).trigger('change');

        if (!iconFile) {
            callback();
            return;
        }

        const formData = new FormData();
        formData.append('m', 'uploadCustomAppIcon');
        formData.append('id', saved.id);
        formData.append('icon', iconFile);

        $.ajax({ type: 'POST', url: 'ajax/customapps.php', data: formData, processData: false, contentType: false, dataType: 'json' }).done(function (uploaded) {
            if (uploaded.error) {
                addAppError('The icon was rejected: ' + uploaded.error, true);
                return;
            }

            callback();
        }).fail(function (xhr) {
            addAppError('Icon upload failed (HTTP ' + xhr.status + ')', true);
        });
    }).fail(function (xhr) {
        addAppError('Failed to save the custom app definition (HTTP ' + xhr.status + ')', false);
    });
}
// -------------------------------------------------------------------------------------------
function testAndSaveAddApp(details, definitionCreated)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=saveStarr&starrId=99&app=' + encodeURIComponent(details.app) + '&url=' + encodeURIComponent(details.url) + '&apikey=' + encodeURIComponent(details.apikey) + '&username=' + encodeURIComponent($('#add-app-username').val()) + '&password=' + encodeURIComponent($('#add-app-password').val()) + '&requireTest=1',
        error: function (xhr) {
            addAppError('Failed to save the instance (HTTP ' + xhr.status + ')', definitionCreated);
        },
        success: function (saveResult) {
            if (saveResult) {
                addAppError(saveResult, definitionCreated);
                return;
            }

            window.location.href = '/?app=' + encodeURIComponent(details.app);
        }
    });
}
// -------------------------------------------------------------------------------------------
function openAppStarrAccess(app, id, clone = '')
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=openAppStarrAccess&app=' + app + '&id=' + id + '&clone=' + clone,
        error: function (xhr) {
            loadingStop();
            toast('Error', 'Failed to load access form (HTTP ' + xhr.status + '). Check the system log for details.', 'error');
        },
        success: function (resultData) {
            dialogOpen({
                id: 'openAppStarrAccess',
                title: 'Grant starr API access',
                size: 'lg',
                body: resultData,
                onOpen: function() {
                    $('#access-template').select2({
                        theme: 'bootstrap-5'
                    });

                    loadingStop();
                }
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function saveAppStarrAccess(app, id)
{
    let error = '';
    if (!$('#access-name').val()) {
        error = 'App name is required';
    }
    if (!$('#access-apikey').val()) {
        error = 'App apikey is required';
    }
    if (!$('#access-instance').val()) {
        error = 'App instance is required';
    }

    if (error) {
        toast('API access', error, 'error');
        return;
    }

    loadingStart();

    let params = '&app=' + app;
    params += '&name=' + $('#access-name').val();
    params += '&apikey=' + $('#access-apikey').val();
    params += '&id=' + id;
    params += '&starr_id=' + $('#access-instance').val();
    params += '&template=' + $('#access-template').val();
    params += '&redactions=' + $('#access-redactions').val();

    $.each($('[id^=endpoint-counter-]'), function() {
        const counter = $(this).attr('id').replace('endpoint-counter-', '');
        params += '&endpoint-' + counter + '=' + $(this).data('endpoint');
        params += '&method-' + counter + '=' + $(this).data('method');
        params += '&enabled-' + counter + '=' + ($(this).prop('checked') ? 1 : 0);
    });

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=saveAppStarrAccess' + params,
        success: function (resultData) {
            loadingStop();

            if (resultData) {
                toast('App access', resultData, 'error');
                return;
            }

            reload();
        }
    });
}
// -------------------------------------------------------------------------------------------
function deleteAppStarrAccess(app, id)
{
    if (confirm('Are you sure you want to delete this apps access to ' + app + '?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/starr.php',
            data: '&m=deleteAppStarrAccess&app=' + app + '&id=' + id,
            success: function (resultData) {
                if (resultData) {
                    toast('App access', resultData, 'error');
                    return;
                }

                reload();
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
function resetUsageCounts(app, id)
{
    $('#' + app + '-' + id + '-requestCount').html('0');
    $('#' + app + '-' + id + '-allowedCount').html('0');
    $('#' + app + '-' + id + '-blockedCount').html('0');
}
// -------------------------------------------------------------------------------------------
function resetUsage(app, id)
{
    if (confirm('Are you sure you want to reset the usage counter?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/starr.php',
            data: '&m=resetUsage&app=' + app + '&id=' + id,
            success: function (resultData) {
                if (resultData) {
                    toast('Usage', resultData, 'error');
                    return;
                }

                resetUsageCounts(app, id);
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
function addEndpointAccess(app, id, endpoint, method, endpointHash)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=addEndpointAccess&app=' + app + '&id=' + id + '&endpoint=' + endpoint + '&method=' + method,
        success: function (resultData) {
            if (resultData) {
                toast('App access', resultData, 'error');
                return;
            }

            $('#disallowed-endpoint-' + endpointHash + ', #allowed-endpoint-' + endpointHash).toggle();
            toast('Endpoint access', 'The ' + endpoint + ' endpoint has been allowed for this app', 'success');
        }
    });
}
// -------------------------------------------------------------------------------------------
function removeEndpointAccess(app, id, endpoint, method, endpointHash)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=removeEndpointAccess&app=' + app + '&id=' + id + '&endpoint=' + endpoint + '&method=' + method,
        success: function (resultData) {
            if (resultData) {
                toast('App access', resultData, 'error');
                return;
            }

            $('#disallowed-endpoint-' + endpointHash + ', #allowed-endpoint-' + endpointHash).toggle();
            toast('Endpoint access', 'The ' + endpoint + ' endpoint has been blocked for this app', 'success');
        }
    });
}
// -------------------------------------------------------------------------------------------
function viewAppEndpointDiff(appId)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=viewAppEndpointDiff&appId=' + appId,
        success: function (resultData) {
            dialogOpen({
                id: 'viewAppEndpointDiff',
                title: 'Template endpoint differences',
                size: 'lg',
                body: resultData,
                onOpen: function() {
                    loadingStop();
                }
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function autoAdjustAppEndpoints(appId)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=autoAdjustAppEndpoints&appId=' + appId,
        success: function () {
            reload();
        }
    });
}
// -------------------------------------------------------------------------------------------
