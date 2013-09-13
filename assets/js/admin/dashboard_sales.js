function WC_Dashboard_Sales(options) {
    this.options = {
        series: {
            lines: { show: true, fill: true },
            points: { show: true }
        },
        grid: {
            show: true,
            aboveData: false,
            color: '#ccc',
            backgroundColor: '#fff',
            borderWidth: 2,
            borderColor: '#ccc',
            clickable: false,
            hoverable: true,
            markings: this.weekendAreas
        },
        xaxis: { 
            mode: "time",
            timeformat: "%d %b", 
            monthNames: params.month_names,
            tickLength: 1,
            minTickSize: [1, "day"]
        },
        yaxes: [ { min: 0, tickSize: 10, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
        colors: ["#8a4b75", "#47a03e"],
        legend: {
            show: true,
            position: "nw"
        }
    }
}

WC_Dashboard_Sales.prototype.weekendAreas = function(axes) {
    var markings = [];
    var d = new Date(axes.xaxis.min);
    // go to the first Saturday
    d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
    d.setUTCSeconds(0);
    d.setUTCMinutes(0);
    d.setUTCHours(0);
    var i = d.getTime();
    do {
        // when we don't set yaxis, the rectangle automatically
        // extends to infinity upwards and downwards
        markings.push({ xaxis: { from: i, to: i + 2 * 24 * 60 * 60 * 1000 } });
        i += 7 * 24 * 60 * 60 * 1000;
    } while (i < axes.xaxis.max);

    return markings;
}

WC_Dashboard_Sales.prototype.plot = function() {
	order_data = jQuery.parseJSON( params.order_data.replace(/&quot;/g, '"') );
	
	var d = order_data.order_counts;
    var d2 = order_data.order_amounts;
	
	for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;
    for (var i = 0; i < d2.length; ++i) d2[i][0] += 60 * 60 * 1000;
	
	var placeholder = jQuery("#placeholder");

	var plot = jQuery.plot(placeholder, [ { label: params.number_of_sales, data: d }, { label: params.sales_amount, data: d2, yaxis: 2 } ], this.options);
 	
 	placeholder.resize();

    var self = this;
    var previousPoint = null;
    jQuery("#placeholder").bind("plothover", function (event, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;
                
                jQuery("#tooltip").remove();
                
                if (item.series.label==params.number_of_sales) {
                    
                    var y = item.datapoint[1];
                    self.showTooltip(item.pageX, item.pageY, params.sold + ": " + y);
                    
                } else {
                    
                    var y = item.datapoint[1].toFixed(2);
                    self.showTooltip(item.pageX, item.pageY, params.earned + ": " + params.currency_symbol + y);
                
                }

            }
        }
        else {
            jQuery("#tooltip").remove();
            previousPoint = null;            
        }
    });
}

WC_Dashboard_Sales.prototype.showTooltip = function(x, y, contents) {
    jQuery('<div id="tooltip">' + contents + '</div>').css( {
        position: 'absolute',
        display: 'none',
        top: y + 5,
        left: x + 5,
	    padding: '5px 10px',  
		border: '3px solid #3da5d5',  
		background: '#288ab7'
    }).appendTo("body").fadeIn(200);
}

var wc_dashboard_sales = new WC_Dashboard_Sales();

wc_dashboard_sales.plot();
