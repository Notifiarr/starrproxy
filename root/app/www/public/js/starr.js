function testStarr(app, instance)
{
    if (!$('#instance-url-' + instance).val()) {
        toast('Starr Test', 'The url is required before testing', 'error');
        return;
    }

    if (!$('#instance-apikey-' + instance).val()) {
        toast('Starr Test', 'The apikey is required before testing', 'error');
        return;
    }

    let apikey = $('#instance-apikey-' + instance).val();
    if ($('#instance-apikey-' + instance).val().includes('..')) {
        apikey = $('#instance-apikey-' + instance).data('apikey');
    }

    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=testStarr&app=' + app + '&url=' + $('#instance-url-' + instance).val() + '&apikey=' + apikey,
        dataType: 'json',
        success: function (resultData) {
            let type = 'success';
            if (resultData.error) {
                type = 'error';
            }

            toast('Starr Test', resultData.result, type);
        }
    });
}
// -------------------------------------------------------------------------------------------
function saveStarr(app, instance)
{
    const apikey = $('#instance-apikey-' + instance).val();
    if (apikey.includes('..')) {
        $('#instance-apikey-' + instance).val($('#instance-apikey-' + instance).data('apikey'));
    }

    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=saveStarr&app=' + app + '&instance=' + instance + '&url=' + $('#instance-url-' + instance).val() + '&apikey=' + $('#instance-apikey-' + instance).val(),
        success: function () {
            window.location.href = '?app=' + app;
        }
    });
}
// -------------------------------------------------------------------------------------------
function deleteStarr(app, instance)
{
    if (confirm('Are you sure you want to delete this ' + app + ' instance?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax.php',
            data: '&m=deleteStarr&app=' + app + '&instance=' + instance,
            success: function () {
                window.location.href = '?app=' + app;
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
function openAppStarrAccess(app, id, clone = '')
{
    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=newAppStarrAccess&app=' + app + '&id=' + id + '&clone=' + clone,
        success: function (resultData) {
            dialogOpen({
                id: 'openAppStarrAccess',
                title: 'Grant starr API access',
                size: 'lg',
                body: resultData
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
    if (!$('#access-instances').val()) {
        error = 'App instances is required';
    }

    if (error) {
        toast('API Access', error, 'error');
        return;
    }

    let params = '';
    $.each($('[id^=endpoint-counter-]'), function() {
        const counter = $(this).attr('id').replace('endpoint-counter-', '');
        params += '&endpoint-' + counter + '=' + $(this).data('endpoint');
        params += '&method-' + counter + '=' + $(this).data('method');
        params += '&enabled-' + counter + '=' + ($(this).prop('checked') ? 1 : 0);
    });

    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=saveAppStarrAccess&app=' + app + '&name=' + $('#access-name').val() + '&apikey=' + $('#access-apikey').val() + '&id=' + id + '&instances=' + $('#access-instances').val() + params,
        success: function () {
            window.location.href = '?app=' + app;
        }
    });
}
// -------------------------------------------------------------------------------------------
function deleteAppStarrAccess(app, id)
{
    if (confirm('Are you sure you want to delete this apps access to ' + app + '?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax.php',
            data: '&m=deleteAppStarrAccess&app=' + app + '&id=' + id,
            success: function () {
                window.location.href = '?app=' + app;
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
function openAppAccessLog(starr, appIndex, app, key)
{
    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=openAppAccessLog&accessApp=' + app + '&accessId=' + appIndex + '&key=' + key + '&app=' + starr,
        success: function (resultData) {
            dialogOpen({
                id: 'openAppAccessLog',
                title: 'Access log viewer: ' + app + ' (filter: ' + key + ')',
                size: 'xxl',
                body: resultData
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function openTemplateStarrAccess(app, id)
{
    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=openTemplateStarrAccess&app=' + app + '&id=' + id,
        success: function (resultData) {
            dialogOpen({
                id: 'openTemplateStarrAccess',
                title: 'Create new template for ' + app,
                size: 'lg',
                body: resultData
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function saveTemplateStarrAccess(app, id)
{
    if (!$('#new-template-name').val()) {
        toast('Templates', 'Template name is required', 'error');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=saveTemplateStarrAccess&app=' + app + '&id=' + id + '&name=' + encodeURIComponent($('#new-template-name').val()),
        success: function () {
            dialogClose('openTemplateStarrAccess');
            toast('Templates', 'The template has been added', 'info');
        }
    });
}
// -------------------------------------------------------------------------------------------
function resetUsage(app, id)
{
    if (confirm('Are you sure you want to reset the usage counter?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax.php',
            data: '&m=resetUsage&app=' + app + '&id=' + id,
            success: function () {
                window.location.href = '?app=' + app;
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
function addEndpointAccess(app, id, endpoint, method, endpointHash)
{
    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=addEndpointAccess&app=' + app + '&id=' + id + '&endpoint=' + endpoint + '&method=' + method,
        success: function () {
            $('#disallowed-endpoint-' + endpointHash + ', #allowed-endpoint-' + endpointHash).toggle();
            toast('Endpoint access', 'The ' + endpoint + ' endpoint has been allowed for this app', 'success');
        }
    });
}
// -------------------------------------------------------------------------------------------
