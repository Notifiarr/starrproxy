const LOG_REFRESH_INTERVAL = 3000;
const LOG_LIST_REFRESH_INTERVAL = 10000;

let currentLog = null;

//-- Polls tick() every interval ms, waits for the previous request to finish and pauses while the browser tab is hidden
function createPoller(interval, tick)
{
    let timer = null;
    let running = false;

    function schedule()
    {
        clearTimeout(timer);
        timer = running && !document.hidden ? setTimeout(run, interval) : null;
    }

    function run()
    {
        $.when(tick()).always(schedule);
    }

    document.addEventListener('visibilitychange', schedule);

    return {
        start: function () {
            running = true;
            schedule();
        },
        stop: function () {
            running = false;
            schedule();
        },
        toggle: function () {
            running ? this.stop() : this.start();
            return running;
        }
    };
}
// -------------------------------------------------------------------------------------------
function setRefreshButton(button, enabled)
{
    $(button).find('i').toggleClass('fa-sync', enabled).toggleClass('fa-pause', !enabled);
    $(button).attr('title', enabled ? 'Pause auto-refresh' : 'Resume auto-refresh');
}
// -------------------------------------------------------------------------------------------
function keepScroll(container, update)
{
    const containerScroll = $(container).scrollTop();
    const windowScroll = window.scrollY;

    update();

    $(container).scrollTop(containerScroll);
    window.scrollTo(0, windowScroll);
}
// -------------------------------------------------------------------------------------------
const logPoller = createPoller(LOG_REFRESH_INTERVAL, function () {
    if (!currentLog) {
        return;
    }

    return $.post('ajax/logs.php', { m: 'viewLog', log: currentLog.log, page: currentLog.page, index: currentLog.index }, function (resultData) {
        keepScroll('#log-viewer', function () {
            $('#log-viewer').html(resultData);
        });
    });
});
// -------------------------------------------------------------------------------------------
const logListPoller = createPoller(LOG_LIST_REFRESH_INTERVAL, function () {
    return $.post('ajax/logs.php', { m: 'refreshLogList' }, function (resultData) {
        $('#log-sidebar').html(resultData);

        if (currentLog) {
            $('#app-log-' + currentLog.index).addClass('text-info');
        }
    });
});
// -------------------------------------------------------------------------------------------
let appLog = null;

const appLogPoller = createPoller(LOG_REFRESH_INTERVAL, function () {
    if (!appLog || !$('#viewAppLog').is(':visible')) {
        return;
    }

    const activeTab = $('#viewAppLog .nav-link.active').attr('href') || '#access';

    return $.post('ajax/logs.php', { m: 'viewAppLog', key: appLog.key, log: appLog.log, activeTab: activeTab }, function (resultData) {
        keepScroll('#viewAppLog .modal-body', function () {
            $('#viewAppLog .modal-body').html(resultData);
        });
    });
});
// -------------------------------------------------------------------------------------------
function viewLog(log, index, page = 1)
{
    loadingStart();

    $('#log-viewer').html('Loading log...');
    $('[id^=app-log-]').removeClass('text-info');
    $('#app-log-' + index).addClass('text-info');

    currentLog = { log: log, index: index, page: page };

    $.post('ajax/logs.php', { m: 'viewLog', log: log, page: page, index: index }, function (resultData) {
        $('#log-viewer').html(resultData);
        loadingStop();
    });
}
// -------------------------------------------------------------------------------------------
function toggleLogAutoRefresh()
{
    const enabled = logPoller.toggle();

    enabled ? logListPoller.start() : logListPoller.stop();
    setRefreshButton('#auto-refresh-toggle', enabled);
    $('#auto-refresh-status').text(enabled ? LOG_REFRESH_INTERVAL / 1000 + 's' : 'off');
}
// -------------------------------------------------------------------------------------------
function viewAppLog(log, key, appName)
{
    loadingStart();

    appLog = { log: log, key: key };

    $.post('ajax/logs.php', { m: 'viewAppLog', key: key, log: log }, function (resultData) {
        dialogOpen({
            id: 'viewAppLog',
            title: 'Access log viewer: ' + appName + ' (filter: ' + key + ')' +
                   ' <button id="app-log-refresh-toggle" class="btn btn-sm btn-outline-success border-light ms-3" onclick="toggleAppLogAutoRefresh()" title="Pause auto-refresh">' +
                   '<i class="fas fa-sync"></i></button>',
            size: 'xxl',
            body: resultData,
            onOpen: function () {
                loadingStop();
                appLogPoller.start();
            },
            onClose: function () {
                appLogPoller.stop();
                appLog = null;
            }
        });
    });
}
// -------------------------------------------------------------------------------------------
function toggleAppLogAutoRefresh()
{
    setRefreshButton('#app-log-refresh-toggle', appLogPoller.toggle());
}
// -------------------------------------------------------------------------------------------
function deleteLog(log)
{
    if (confirm('Are you sure you want to delete the log: ' + log + '?')) {
        logPoller.stop();

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
$(document).ready(function () {
    if ($('#log-sidebar').length) {
        logPoller.start();
        logListPoller.start();
    }
});
