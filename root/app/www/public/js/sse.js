//-- Subscribes to named events from sse.php, handlers is { eventName: function (html) {} } and params go into the url
//-- sse.php answers once per request and the browser reconnects on its own, the subscription closes while the tab is hidden
function sseSubscribe(handlers, params = {})
{
    const url = 'sse.php?' + $.param(Object.assign({ events: Object.keys(handlers).join(',') }, params));
    let source = null;

    function reconnect()
    {
        if (source) {
            source.close();
            source = null;
        }

        if (document.hidden) {
            return;
        }

        source = new EventSource(url);
        $.each(handlers, function (event, handler) {
            source.addEventListener(event, function (message) {
                handler(JSON.parse(message.data));
            });
        });
    }

    document.addEventListener('visibilitychange', reconnect);
    reconnect();

    return {
        close: function () {
            document.removeEventListener('visibilitychange', reconnect);

            if (source) {
                source.close();
                source = null;
            }
        }
    };
}
