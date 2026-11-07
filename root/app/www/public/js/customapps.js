function loadCustomApps()
{
    $.post('ajax/customapps.php', { m: 'getCustomApps' }, function (resultData) {
        $('#custom-apps-list').html(resultData);
    }).fail(function (xhr) {
        $('#custom-apps-list').text('Failed to load custom apps (HTTP ' + xhr.status + ')').addClass('text-danger');
    });
}
// -------------------------------------------------------------------------------------------
function openCustomAppForm(id = 0)
{
    loadingStart();

    $.post('ajax/customapps.php', { m: 'openCustomAppForm', id: id }, function (resultData) {
        dialogOpen({
            id: 'customAppForm',
            title: id ? 'Edit Custom App' : 'Add Custom App',
            size: 'lg',
            body: resultData,
            footer: '<button class="btn btn-success" onclick="saveCustomApp(' + id + ')"><i class="far fa-save"></i> Save</button>',
            onOpen: function () {
                loadingStop();
            }
        });
    });
}
// -------------------------------------------------------------------------------------------
function saveCustomApp(id)
{
    const iconFile = $('#custom-app-icon')[0].files[0];

    loadingStart();

    $.post('ajax/customapps.php', {
        m: 'saveCustomApp',
        id: id,
        name: $('#custom-app-name').val(),
        label: $('#custom-app-label').val(),
        api_version: $('#custom-app-api-version').val(),
        openapi_url: $('#custom-app-openapi-url').val()
    }, null, 'json').done(function (saved) {
        if (saved.error) {
            loadingStop();
            toast('Custom App', saved.error, 'error');
            return;
        }

        if (!iconFile) {
            reload();
            return;
        }

        const formData = new FormData();
        formData.append('m', 'uploadCustomAppIcon');
        formData.append('id', saved.id);
        formData.append('icon', iconFile);

        $.ajax({ type: 'POST', url: 'ajax/customapps.php', data: formData, processData: false, contentType: false, dataType: 'json' }).done(function (uploaded) {
            if (uploaded.error) {
                loadingStop();
                toast('Custom App', 'App saved but the icon was rejected: ' + uploaded.error, 'error');
                return;
            }

            reload();
        });
    });
}
// -------------------------------------------------------------------------------------------
function deleteCustomApp(id)
{
    if (!confirm('Are you sure you want to delete this custom app?')) {
        return;
    }

    $.post('ajax/customapps.php', { m: 'deleteCustomApp', id: id }, null, 'json').done(function (resultData) {
        if (resultData.error) {
            toast('Custom App', resultData.error, 'error');
            return;
        }

        reload();
    });
}
// -------------------------------------------------------------------------------------------
$(document).ready(function () {
    if ($('#custom-apps-list').length) {
        loadCustomApps();
    }
});
