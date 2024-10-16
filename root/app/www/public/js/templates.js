function viewTemplate()
{
    if ($('#template-selection').val() == '0') {
        toast('Templates', 'Select a template to view it', 'error');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax.php',
        data: '&m=viewTemplate&template=' + $('#template-selection').val(),
        success: function (resultData) {
            $('#template-viewer').html(resultData)
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
        url: 'ajax.php',
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

            $('#access-template').val(0);
            toast('Templates', 'The selected template access has been applied', 'info');
        }
    });
}
// ---------------------------------------------------------------------------------------------
