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
           // if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;

                jQuery( ".chart-tooltip" ).remove();

                /*if (item.series.label=="<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>") {

                	var y = item.datapoint[1].toFixed(2);
                	showTooltip(item.pageX, item.pageY, "<?php echo get_woocommerce_currency_symbol(); ?>" + y);

                } else*/ if ( item.series.points.show ) {

                	var y = item.datapoint[1];
                	showTooltip( item.pageX, item.pageY, item.series.label + ": " + y );

                }
            //}
        }
        else {
            jQuery(".chart-tooltip").remove();
            previousPoint = null;
        }
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