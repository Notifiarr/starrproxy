function viewTemplate(template, index)
{
    loadingStart();

    $('[class^=app-index-]').removeClass('text-info');
    $('.app-index-' + index).addClass('text-info');

    $.ajax({
        type: 'POST',
        url: 'ajax/templates.php',
        data: '&m=viewTemplate&template=' + template,
        success: function (resultData) {
            $('#template-viewer').html(resultData);
            loadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function applyTemplateOptions()
{
    if ($('#access-template').val() == '0') {
        return;
    }

    $.each($('[id^=endpoint-counter-]'), function() {
        $(this).prop('checked', false);
    });

    $.ajax({
        type: 'POST',
        url: 'ajax/templates.php',
        data: '&m=applyTemplateOptions&template=' + $('#access-template').val(),
        dataType: 'json',
        success: function (resultData) {
            $.each($('[id^=endpoint-counter-]'), function() {
                const loopEndpoint  = $(this).data('endpoint');
                const loopMethod    = $(this).data('method');
                const loopId        = $(this).prop('id');

                $.each(resultData, function(endpoint, methods) {
                    if (loopEndpoint == endpoint && methods.includes(loopMethod)) {
                        $('#' + loopId).prop('checked', true);
                    }
                });
            });

            toast('Templates', 'The selected template access has been applied', 'info');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function deleteCustomTemplate(app, item)
{
    if (confirm('Are you sure you want to delete this template?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/templates.php',
            data: '&m=deleteCustomTemplate&app=' + app + '&item=' + item,
            success: function () {
                reload();
            }
        });
    }
}
// ---------------------------------------------------------------------------------------------
function openTemplateStarrAccess(app, id)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/templates.php',
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
        url: 'ajax/templates.php',
        data: '&m=saveTemplateStarrAccess&app=' + app + '&id=' + id + '&name=' + encodeURIComponent($('#new-template-name').val()),
        success: function () {
            dialogClose('openTemplateStarrAccess');
            toast('Templates', 'The template has been added', 'info');
        }
    });
}
// -------------------------------------------------------------------------------------------
