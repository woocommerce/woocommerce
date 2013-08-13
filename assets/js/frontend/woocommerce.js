jQuery(document).ready(function($) {

	// Placeholder
	$('.woocommerce textarea[placeholder], .woocommerce-page textarea[placeholder], .woocommerce input[placeholder], .woocommerce-page input[placeholder]').placeholder();

	// Orderby
	$('select.orderby').change(function(){
		$(this).closest('form').submit();
	});

	// Quantity buttons
	$("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" class="plus" />').prepend('<input type="button" value="-" class="minus" />');

	// Target quantity inputs on product pages
	$("input.qty:not(.product-quantity input.qty)").each(function(){

		var min = parseFloat( $(this).attr('min') );

		if ( min && min > 0 && parseFloat( $(this).val() ) < min ) {
			$(this).val( min );
		}

	});

	$(document).on( 'click', '.plus, .minus', function() {

		// Get values
		var $qty 		= $(this).closest('.quantity').find(".qty");
	    var currentVal 	= parseFloat( $qty.val() );
	    var max 		= parseFloat( $qty.attr('max') );
	    var min 		= parseFloat( $qty.attr('min') );
	    var step 		= $qty.attr('step');

	    // Format values
	    if ( ! currentVal || currentVal == "" || currentVal == "NaN" ) currentVal = 0;
	    if ( max == "" || max == "NaN" ) max = '';
	    if ( min == "" || min == "NaN" ) min = 0;
	    if ( step == 'any' || step == "" || step == undefined || parseFloat( step ) == "NaN" ) step = 1;

	    // Change the value
	    if ( $(this).is('.plus') ) {

		    if ( max && ( max == currentVal || currentVal > max ) ) {
		    	$qty.val( max );
		    } else {
		    	$qty.val( currentVal + parseFloat( step ) );
		    }

	    } else {

		    if ( min && ( min==currentVal || currentVal < min ) ) {
		    	$qty.val( min );
		    } else if ( currentVal > 0 ) {
		    	$qty.val( currentVal - parseFloat( step ) );
		    }

	    }

	    // Trigger change event
	    $qty.trigger('change');
	});

	/* State/Country select boxes */
	var states_json = woocommerce_params.countries.replace(/&quot;/g, '"');
	var states = $.parseJSON( states_json );

	$('select.country_to_state').change(function(){

		var country = $(this).val();

		var $statebox = $(this).closest('div').find('#billing_state, #shipping_state, #calc_shipping_state');
		var $parent = $statebox.parent();

		var input_name = $statebox.attr('name');
		var input_id = $statebox.attr('id');
		var value = $statebox.val();
		var placeholder = $statebox.attr('placeholder');

		if (states[country]) {
			if (states[country].length == 0) {

				$statebox.parent().hide().find('.chzn-container').remove();
				$statebox.replaceWith('<input type="hidden" class="hidden" name="' + input_name + '" id="' + input_id + '" value="" placeholder="' + placeholder + '" />');

				$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);

			} else {

				var options = '';
				var state = states[country];
				for(var index in state) {
					options = options + '<option value="' + index + '">' + state[index] + '</option>';
				}
				$statebox.parent().show();
				if ($statebox.is('input')) {
					// Change for select
					$statebox.replaceWith('<select name="' + input_name + '" id="' + input_id + '" class="state_select" placeholder="' + placeholder + '"></select>');
					$statebox = $(this).closest('div').find('#billing_state, #shipping_state, #calc_shipping_state');
				}
				$statebox.html( '<option value="">' + woocommerce_params.i18n_select_state_text + '</option>' + options);

				$statebox.val(value);

				$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);

			}
		} else {
			if ($statebox.is('select')) {

				$parent.show().find('.chzn-container').remove();
				$statebox.replaceWith('<input type="text" class="input-text" name="' + input_name + '" id="' + input_id + '" placeholder="' + placeholder + '" />');

				$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);

			} else if ($statebox.is('.hidden')) {

				$parent.show().find('.chzn-container').remove();
				$statebox.replaceWith('<input type="text" class="input-text" name="' + input_name + '" id="' + input_id + '" placeholder="' + placeholder + '" />');

				$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);

			}
		}

		$('body').trigger('country_to_state_changing', [country, $(this).closest('div')]);

	});

});