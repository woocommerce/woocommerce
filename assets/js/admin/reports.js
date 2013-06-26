jQuery(document).ready(function($) {

    function showTooltip(x, y, contents) {
        jQuery('<div class="chart-tooltip">' + contents + '</div>').css( {
            top: y - 16,
       		left: x + 20
        }).appendTo("body").fadeIn(200);
    }

    var previousPoint = null;

    jQuery(".chart-placeholder").bind( "plothover", function (event, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;

                jQuery( ".chart-tooltip" ).remove();

                if ( item.series.points.show ) {

                	var y = item.datapoint[1];

                    if ( ! item.series.prepend_tooltip )
                        item.series.prepend_tooltip = item.series.label + ": ";

                	showTooltip( item.pageX, item.pageY, item.series.prepend_tooltip + y );

                }
            }
        }
        else {
            jQuery(".chart-tooltip").remove();
            previousPoint = null;
        }
    });

    $('.wc_sparkline.bars').each(function() {
        var chart_data = $(this).data('sparkline');

        var options = {
            grid: {
                show: false
            }
        };

        // main series
        var series = [{
            data: chart_data,
            color: $(this).data('color'),
            bars: { fillColor: $(this).data('color'), fill: true, show: true, lineWidth: 1, barWidth: $(this).data('barwidth'), align: 'center' },
            shadowSize: 0
        }];

        // draw the sparkline
        var plot = $.plot( $(this), series, options );
    });

    $('.wc_sparkline.lines').each(function() {
        var chart_data = $(this).data('sparkline');

        var options = {
            grid: {
                show: false
            }
        };

        // main series
        var series = [{
            data: chart_data,
            color: $(this).data('color'),
            lines: { fill: false, show: true, lineWidth: 1, align: 'center' },
            shadowSize: 0
        }];

        // draw the sparkline
        var plot = $.plot( $(this), series, options );
    });

    var dates = jQuery( ".range_datepicker" ).datepicker({
        defaultDate: "",
        dateFormat: "yy-mm-dd",
        numberOfMonths: 1,
        maxDate: "+0D",
        showButtonPanel: true,
        showOn: "focus",
        buttonImageOnly: true,
        onSelect: function( selectedDate ) {
            var option = jQuery(this).is('.from') ? "minDate" : "maxDate",
                instance = jQuery( this ).data( "datepicker" ),
                date = jQuery.datepicker.parseDate(
                    instance.settings.dateFormat ||
                    jQuery.datepicker._defaults.dateFormat,
                    selectedDate, instance.settings );
            dates.not( this ).datepicker( "option", option, date );
        }
    });
});