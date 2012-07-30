jQuery(document).ready(function($) {

	// Orderby
	$('select.orderby').change(function(){
		$(this).closest('form').submit();
	});
	
	// Quantity buttons
	$("div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)").addClass('buttons_added').append('<input type="button" value="+" class="plus" />').prepend('<input type="button" value="-" class="minus" />');
	
	// Target quantity inputs on product pages
	$("input.qty:not(.product-quantity input.qty)").each(function(){
		
		var min = parseInt($(this).attr('data-min'));
		
		if (min && min > 1 && parseInt($(this).val()) < min) {
			$(this).val(min);
		}
		
	});
	
	$(".plus").live('click', function() {
	    var currentVal = parseInt($(this).prev(".qty").val());
	    if (!currentVal || currentVal=="" || currentVal == "NaN") currentVal = 0;
	    
	    $qty = $(this).prev(".qty");
	    
	    var max = parseInt($qty.attr('data-max'));
	    if (max=="" || max == "NaN") max = '';
	    
	    if (max && (max==currentVal || currentVal>max)) {
	    	$qty.val(max); 
	    } else {
	    	$qty.val(currentVal + 1); 
	    }
	    
	    $qty.trigger('change');
	});
	
	$(".minus").live('click', function() {
		var currentVal = parseInt($(this).next(".qty").val());
	    if (!currentVal || currentVal=="" || currentVal == "NaN") currentVal = 0;
	    
	    $qty = $(this).next(".qty");
	    
	    var min = parseInt($qty.attr('data-min'));
	    if (min=="" || min == "NaN") min = 0;
	    
	    if (min && (min==currentVal || currentVal<min)) {
	    	$qty.val(min); 
	    } else if (currentVal > 0) {
	    	$qty.val(currentVal - 1);
	    }
	    
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
		
		if (states[country]) {
			if (states[country].length == 0) {
				
				// Empty array means state field is not used
				$parent.fadeOut(200, function() {
					$statebox.parent().find('.chzn-container').remove();
					$statebox.replaceWith('<input type="hidden" class="hidden" name="' + input_name + '" id="' + input_id + '" value="" />');
					
					$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);
				});
				
			} else {
				
				$parent.fadeOut(200, function() {
					var options = '';
					var state = states[country];
					for(var index in state) {
						options = options + '<option value="' + index + '">' + state[index] + '</option>';
					}
					if ($statebox.is('input')) {
						// Change for select
						$statebox.replaceWith('<select name="' + input_name + '" id="' + input_id + '" class="state_select"></select>');
						$statebox = $(this).closest('div').find('#billing_state, #shipping_state, #calc_shipping_state');
					}
					$statebox.html( '<option value="">' + woocommerce_params.select_state_text + '</option>' + options);
					
					$statebox.val(value);
					
					$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);
					
					$parent.fadeIn(500);
				});
			
			}
		} else {
			if ($statebox.is('select')) {
				
				$parent.fadeOut(200, function() {
					$parent.find('.chzn-container').remove();
					$statebox.replaceWith('<input type="text" class="input-text" name="' + input_name + '" id="' + input_id + '" />');
					
					$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);
					$parent.fadeIn(500);
				});
				
			} else if ($statebox.is('.hidden')) {
				
				$parent.find('.chzn-container').remove();
				$statebox.replaceWith('<input type="text" class="input-text" name="' + input_name + '" id="' + input_id + '" />');
				
				$('body').trigger('country_to_state_changed', [country, $(this).closest('div')]);
				$parent.delay(200).fadeIn(500);
				
			}
		}
		
		$('body').delay(200).trigger('country_to_state_changing', [country, $(this).closest('div')]);
		
	});

});