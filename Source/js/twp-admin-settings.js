/**
 * Handles admin page events.
 */
;(function () {
    if (typeof jQuery === 'undefined') return;
    jQuery(document).ready(function ($) {
        var textarea = $('#twp_options\\[twp-service-workers-options\\]');
        var value = textarea.val().trim();
        textarea.val(value + (value.length > 0 ? '\n' : ''));

        var checkbox = $('#twp_options\\[twp-enable-service-workers\\]');
        var checked = !checkbox.prop('checked');
        textarea.prop('readOnly', checked);
        $('#twp-service-worker-url').prop('readOnly', checked);
        $('#twp-service-worker-scope').prop('readOnly', checked);

        checkbox.change(function () {
            var checked = !$('#twp_options\\[twp-enable-service-workers\\]').prop('checked');
            $('#twp_options\\[twp-service-workers-options\\]').prop('readOnly', checked);
            $('#twp-service-worker-url').prop('readOnly', checked);
            $('#twp-service-worker-scope').prop('readOnly', checked);
        });

        $('#twp-service-worker-clear').click(function () {
            $('#twp_options\\[twp-service-workers-options\\]').val('');
        });

        $('#twp-service-worker-add').click(function () {
            var textarea = $('#twp_options\\[twp-service-workers-options\\]');
            var url = $('#twp-service-worker-url').val().trim();
            var scope = $('#twp-service-worker-scope').val().trim();
            if (url.length > 0) textarea.val(textarea.val() +
                url + (scope.length > 0 ? '|' + scope : '') + '\n');
        });
    });
})();