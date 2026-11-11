let currentLog = null;
let logSubscription = null;
let logAutoRefresh = true;

let appLog = null;
let appKey = null;
let appLogSubscription = null;

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
//-- The server always renders the first tab, switch back to the one the user had open without the fade
function showTab(container, href)
{
    $(container).find('.nav-link').removeClass('active').attr('aria-selected', 'false');
    $(container).find('.nav-link[href="' + href + '"]').addClass('active').attr('aria-selected', 'true');
    $(container).find('.tab-pane').removeClass('show active');
    $(container).find(href).addClass('show active');
}
// -------------------------------------------------------------------------------------------
//-- By path, the sidebar index shifts when a log is added or removed
function highlightLog(log)
{
    $('[id^=app-log-]').removeClass('text-info').filter(function () {
        return $(this).attr('onclick').startsWith('viewLog(\'' + log + '\'');
    }).addClass('text-info');
}
// -------------------------------------------------------------------------------------------
function subscribeLogs()
{
    if (logSubscription) {
        logSubscription.close();
        logSubscription = null;
    }

    if (!logAutoRefresh) {
        return;
    }

    const handlers = {
        logList: function (html) {
            $('#log-sidebar').html(html);

            if (currentLog) {
                highlightLog(currentLog.log);
            }
        }
    };

    if (currentLog) {
        handlers.log = function (html) {
            keepScroll('#log-viewer', function () {
                $('#log-viewer').html(html);
            });
        };
    }

    logSubscription = sseSubscribe(handlers, currentLog ? { log: currentLog.log, page: currentLog.page } : {});
}
// -------------------------------------------------------------------------------------------
function viewLog(log, index, page = 1)
{
    loadingStart();

    $('#log-viewer').html('Loading log...');
    highlightLog(log);

    currentLog = { log: log, page: page };

    $.post('ajax/logs.php', { m: 'viewLog', log: log, page: page, index: index }, function (resultData) {
        $('#log-viewer').html(resultData);
        loadingStop();
        subscribeLogs();
    });
}
// -------------------------------------------------------------------------------------------
function toggleLogAutoRefresh()
{
    logAutoRefresh = !logAutoRefresh;

    subscribeLogs();
    setRefreshButton('#auto-refresh-toggle', logAutoRefresh);
    $('#auto-refresh-status').text(logAutoRefresh ? '3s' : 'off');
}
// -------------------------------------------------------------------------------------------
function subscribeAppLog()
{
    appLogSubscription = sseSubscribe({
        appLog: function (html) {
            const body = '#viewAppLog .modal-body';
            const activeTab = $(body).find('.nav-link.active').attr('href') || '#access';

            keepScroll(body, function () {
                $(body).html(html);
                showTab(body, activeTab);
            });
        }
    }, { log: appLog, key: appKey });
}
// -------------------------------------------------------------------------------------------
function viewAppLog(log, key, appName)
{
    loadingStart();

    appLog = log;
    appKey = key;

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
                subscribeAppLog();
            },
            onClose: function () {
                if (appLogSubscription) {
                    appLogSubscription.close();
                    appLogSubscription = null;
                }

                appLog = null;
                appKey = null;
            }
        });
    });
}
// -------------------------------------------------------------------------------------------
function toggleAppLogAutoRefresh()
{
    if (appLogSubscription) {
        appLogSubscription.close();
        appLogSubscription = null;
    } else {
        subscribeAppLog();
    }

    setRefreshButton('#app-log-refresh-toggle', !!appLogSubscription);
}
// -------------------------------------------------------------------------------------------
function deleteLog(log)
{
    if (confirm('Are you sure you want to delete the log: ' + log + '?')) {
        logAutoRefresh = false;
        subscribeLogs();

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
        subscribeLogs();
    }
});
