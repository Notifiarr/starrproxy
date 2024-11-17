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
            let type = 'success';
            if (resultData.error) {
                type = 'error';
            }

            toast('Starr test', resultData.result, type);
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
function openAppStarrAccess(app, id, clone = '')
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/starr.php',
        data: '&m=openAppStarrAccess&app=' + app + '&id=' + id + '&clone=' + clone,
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
    
                reload();
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