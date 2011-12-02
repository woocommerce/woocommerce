jQuery(document).ready(function($) {
	
	// Get markup ready for slider
	$('input#min_price, input#max_price').hide();
	$('.price_slider, .price_label').show();
	
	// Price slider uses jquery ui
	var min_price = $('.price_slider_amount #min_price').attr('data-min');
	var max_price = $('.price_slider_amount #max_price').attr('data-max');
	
	current_min_price = parseInt(min_price);
	current_max_price = parseInt(max_price);
	
	if (woocommerce_price_slider_params.min_price) current_min_price = parseInt(woocommerce_price_slider_params.min_price);
	if (woocommerce_price_slider_params.max_price) current_max_price = parseInt(woocommerce_price_slider_params.max_price);
	
	$('.price_slider').slider({
		range: true,
		animate: true,
		min: min_price,
		max: max_price,
		values: [current_min_price,current_max_price],
		create : function( event, ui ) {

			if (woocommerce_price_slider_params.currency_pos == "left"){
				$( ".price_slider_amount span" ).html( woocommerce_price_slider_params.currency_symbol + current_min_price + " - " + woocommerce_price_slider_params.currency_symbol + current_max_price );
			} else if (woocommerce_price_slider_params.currency_pos == "left_space") {
				$( ".price_slider_amount span" ).html( woocommerce_price_slider_params.currency_symbol + " " + current_min_price + " - " + woocommerce_price_slider_params.currency_symbol + " " + current_max_price );
			} else if (woocommerce_price_slider_params.currency_pos == "right") {
				$( ".price_slider_amount span" ).html( current_min_price + woocommerce_price_slider_params.currency_symbol + " - " + current_max_price + woocommerce_price_slider_params.currency_symbol );
			} else if (woocommerce_price_slider_params.currency_pos == "right_space") {
				$( ".price_slider_amount span" ).html( current_min_price + " " + woocommerce_price_slider_params.currency_symbol + " - " + current_max_price + " " + woocommerce_price_slider_params.currency_symbol );
			}
			
			$( ".price_slider_amount #min_price" ).val(current_min_price);
			$( ".price_slider_amount #max_price" ).val(current_max_price);
		},
		slide: function( event, ui ) {
			
			if (woocommerce_price_slider_params.currency_pos == "left"){
				$( ".price_slider_amount span" ).html( woocommerce_price_slider_params.currency_symbol + ui.values[ 0 ] + " - " + woocommerce_price_slider_params.currency_symbol + ui.values[ 1 ] );
			} else if (woocommerce_price_slider_params.currency_pos == "left_space") {
				$( ".price_slider_amount span" ).html( woocommerce_price_slider_params.currency_symbol + " " + ui.values[ 0 ] + " - " + woocommerce_price_slider_params.currency_symbol + " " + ui.values[ 1 ] );
			} else if (woocommerce_price_slider_params.currency_pos == "right") {
				$( ".price_slider_amount span" ).html( ui.values[ 0 ] + woocommerce_price_slider_params.currency_symbol + " - " + ui.values[ 1 ] + woocommerce_price_slider_params.currency_symbol );
			} else if (woocommerce_price_slider_params.currency_pos == "right_space") {
				$( ".price_slider_amount span" ).html( ui.values[ 0 ] + " " + woocommerce_price_slider_params.currency_symbol + " - " + ui.values[ 1 ] + " " + woocommerce_price_slider_params.currency_symbol );
			}
			$( "input#min_price" ).val(ui.values[ 0 ]);
			$( "input#max_price" ).val(ui.values[ 1 ]);
		}
	});
	
});