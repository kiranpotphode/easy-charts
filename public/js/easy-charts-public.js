function get_ec_oject(obj) {
    return this[obj];
}

jQuery.fn.get_ec_oject = function(obj) {
    return get_ec_oject(obj);
};

(function($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     */

    $.fn.ec_draw_chart = function() {
        var obj;
        obj = $(this[0]).data('object');

        var ec_chart_data = $(this[0]).get_ec_oject(obj);

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
    };

    $(document).ready(function($) {

        $('.ec-uv-chart-container').each(function() {
            $(this).ec_draw_chart();
        });

        $('.uv-chart-div svg.uv-frame g.uv-download-options').bind('mouseenter', function(event) {
            var svg = $(this).parents('.uv-chart-div svg.uv-frame');

            svg[0].setAttribute('width', svg[0].getBoundingClientRect().width);
            svg[0].setAttribute('height', svg[0].getBoundingClientRect().height);

        });

    });
})(jQuery);
