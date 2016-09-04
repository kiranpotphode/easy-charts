jQuery(document).ready(function($) {
    var charts;
    (function() {
        tinymce.PluginManager.add('easy_charts_insert_chart_tc_button', function(editor, url) {
            editor.addButton('easy_charts_insert_chart_tc_button', {
                icon: 'icon dashicons-chart-pie',
                tooltip: 'Insert Easy Chart',
                onclick: function() {
                    $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                'action': 'easy_charts_get_published_charts'
                            },
                        })
                        .done(function(updated_data) {
                            charts = updated_data
                            editor.windowManager.open({
                                title: 'Insert chart',
                                body: [{
                                    type: 'listbox',
                                    name: 'level',
                                    label: 'Select Chart',
                                    'values': charts
                                }],
                                onsubmit: function(e) {
                                    editor.insertContent(e.data.level);
                                }
                            });
                        })
                        .fail(function() {})
                        .always(function() {});
                }
            });
        });
    })();
});
