(function($) {
    'use strict';
    $('document').ready(function() {

        var data = [
            ['', 'Kia', 'Nissan', 'Toyota', 'Honda'],
            ['2008', 10, 11, 12, 13],
            ['2009', 20, 11, 14, 13],
            ['2010', 30, 15, 12, 13]
        ];


        if (typeof(ec_chart) != 'undefined') {
            if (ec_chart.chart_data != null) {
                data = ec_chart.chart_data;
            }
        }

        $('.ec-color-picker').wpColorPicker();
        $(".ec-field-buttonset").buttonset();

        $('.ec-field-slider').each(function(index, el) {

            $(this).slider({
                range: "max",
                min: 0,
                max: 1,
                value: $($(this).data('attach')).val(),
                step: 0.1,
                slide: function(event, ui) {
                    $($(this).data('attach')).val(ui.value);
                }
            });
        });


        $('.resp-tabs-container').pwstabs({
            tabsPosition: 'vertical',
            responsive: false,
            containerWidth: '100%',
            theme: 'pws_theme_orange',
            effect: 'slidedown'
        });

        function ec_save_chart_data_ajax(table) {
            $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'action': 'easy_charts_save_chart_data',
                        'chart_id': ec_chart.chart_id,
                        '_nonce_check': ec_chart.ec_ajax_nonce,
                        'chart_data': JSON.stringify(table.getData())
                    },
                })
                .done(function(updated_data) {
                    $('.uv-div-' + ec_chart.chart_id).html('');

                    var graphdef = {
                        categories: updated_data.chart_categories,
                        dataset: updated_data.chart_data,
                    };

                    var chartObject = uv.chart(updated_data.chart_type, graphdef, chartConfiguration);
                })
                .fail(function() {})
                .always(function() {});
        }

        var container = document.getElementById("handsontable");

        if (container != null) {

            var hot = new Handsontable(container, {
                data: data,
                stretchH: 'all',
                cell: [{
                    row: 0,
                    col: 0,
                    readOnly: true
                }],
                fixedRowsTop: 1,
                fixedColumnsTop: 1,
                fixedColumnsLeft: 1,
                rowHeaders: true,
                colHeaders: true,
                manualColumnMove: true,
                manualRowMove: true,
                minSpareRows: 0,
                minSpareCols: 0,
                contextMenu: true,
                autoWrapCol: true,
                autoWrapRow: true,
                afterChange: function(change, source) {},
                afterColumnMove: function(startColumn, endColumn) {},
                afterRowMove: function(startColumn, endColumn) {}
            });
        }

        $('#ec-button-add-col').on('click', function(event) {
            event.preventDefault();
            hot.alter('insert_col', null);
        });
        $('#ec-button-remove-col').on('click', function(event) {
            event.preventDefault();
            hot.alter('remove_col', null);
        });
        $('#ec-button-add-row').on('click', function(event) {
            event.preventDefault();
            hot.alter('insert_row', null);
        });
        $('#ec-button-remove-row').on('click', function(event) {
            event.preventDefault();
            hot.alter('remove_row', null);
        });

        $('#ec-button-save-data').on('click', function(event) {
            event.preventDefault();

            if (hot.countEmptyCols() == 0 && hot.countEmptyRows() == 0) {

                ec_save_chart_data_ajax(hot);
            } else {
                $("#dialog-confirm").dialog({
                    resizable: false,
                    height: 400,
                    modal: true,
                    buttons: {
                        "Ok": function() {
                            $(this).dialog("close");
                        }
                    }
                });
            }

        });

        if (typeof(ec_chart_data) != 'undefined') {
            var graphdef = {
                categories: [],
                dataset: {}
            };

            var chartType = ec_chart_data.chart_type;
            var chartCategories = ec_chart_data.chart_categories;
            var chartDataset = ec_chart_data.chart_data;
            var chartConfiguration = ec_chart_data.chart_configuration;

            graphdef = {
                categories: chartCategories,
                dataset: chartDataset,
            };

            var chartObject = uv.chart(chartType, graphdef, chartConfiguration);
        }

        $('.uv-chart-div svg.uv-frame g.uv-download-options').bind('mouseenter', function(event) {
            var svg = $(this).parents('.uv-chart-div svg.uv-frame');

            svg[0].setAttribute('width', svg[0].getBoundingClientRect().width);
            svg[0].setAttribute('height', svg[0].getBoundingClientRect().height);

        });

    });

})(jQuery);
