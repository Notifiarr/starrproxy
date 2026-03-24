function viewLog(log, index, page = 1)
{
    loadingStart();

    $('#log-viewer').html('Loading log...');
    $('[id^=app-log-]').removeClass('text-info');
    $('#app-log-' + index).addClass('text-info');

    $.ajax({
        type: 'POST',
        url: 'ajax/logs.php',
        data: '&m=viewLog&log=' + log + '&page=' + page + '&index=' + index,
        success: function (resultData) {
            $('#log-viewer').html(resultData);
            loadingStop();
        }
    });
}
// -------------------------------------------------------------------------------------------
function viewAppLog(log, key, appName)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/logs.php',
        data: '&m=viewAppLog&key=' + key + '&log=' + log,
        success: function (resultData) {
            dialogOpen({
                id: 'viewAppLog',
                title: 'Access log viewer: ' + appName + ' (filter: ' + key + ')',
                size: 'xxl',
                body: resultData,
                onOpen: function() {
                    loadingStop();
                }
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function deleteLog(log)
{
    if (confirm('Are you sure you want to delete the log: ' + log + '?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/logs.php',
            data: '&m=deleteLog&log=' + log,
            success: function () {
                reload();
            }
        });
    }
}
// -------------------------------------------------------------------------------------------
