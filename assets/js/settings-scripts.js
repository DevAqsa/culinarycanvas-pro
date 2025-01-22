jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.color-picker').wpColorPicker();

    // Handle tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var tab = $this.attr('href').split('tab=')[1];

        // Update URL without reloading page
        window.history.pushState({}, '', $this.attr('href'));

        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');

        // Show active tab content
        $('.tab-content').hide();
        $('#' + tab + '-content').show();
    });

    // Show/hide dependent fields
    $('input[type="checkbox"]').on('change', function() {
        var $dependent = $(this).closest('tr').next('.dependent-field');
        if ($dependent.length) {
            if ($(this).is(':checked')) {
                $dependent.show();
            } else {
                $dependent.hide();
            }
        }
    });

    // Initialize on load
    $('input[type="checkbox"]').trigger('change');

    // Handle import/export
    $('#export-settings').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ccp_export_settings',
                nonce: $('#_wpnonce').val()
            },
            success: function(response) {
                if (response.success) {
                    var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data.settings));
                    var downloadAnchorNode = document.createElement('a');
                    downloadAnchorNode.setAttribute("href", dataStr);
                    downloadAnchorNode.setAttribute("download", "recipe-settings-" + response.data.date + ".json");
                    document.body.appendChild(downloadAnchorNode);
                    downloadAnchorNode.click();
                    downloadAnchorNode.remove();
                }
            }
        });
    });

    // Handle settings import
    $('#import-settings').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function(e) {
            try {
                var settings = JSON.parse(e.target.result);
                
                if (confirm(ccpAdmin.strings.import_confirm)) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ccp_import_settings',
                            settings: JSON.stringify(settings),
                            nonce: $('#_wpnonce').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data);
                            }
                        }
                    });
                }
            } catch (error) {
                alert(ccpAdmin.strings.import_error);
            }
        };
        reader.readAsText(file);
    });

    // Handle settings reset
    $('#reset-settings').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(ccpAdmin.strings.reset_confirm)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ccp_reset_settings',
                    nonce: $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
});