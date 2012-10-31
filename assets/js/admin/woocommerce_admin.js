/**
 * WooCommerce Admin JS
 */
jQuery(function(){

	// Live validation
	jQuery('body').on(
		'keyup',
		'.wc_input_price',
		function() {
			var $this = jQuery(this);
			var value = jQuery(this).val();
			if ( value !== '' && !jQuery.isNumeric( value ) ) {
				$this.addClass('wc-error');
			} else {
				$this.removeClass('wc-error');
			}
		}
	).keyup();

	// Tooltips
	jQuery(".tips, .help_tip").tipTip({
    	'attribute' : 'data-tip',
    	'fadeIn' : 50,
    	'fadeOut' : 50,
    	'delay' : 200
    });

    jQuery('select.availability').change(function(){
		if (jQuery(this).val()=="specific") {
			jQuery(this).closest('tr').next('tr').show();
		} else {
			jQuery(this).closest('tr').next('tr').hide();
		}
	}).change();

	// Hidden options
	jQuery('.hide_options_if_checked').each(function(){

		jQuery(this).find('input:eq(0)').change(function() {

			if (jQuery(this).is(':checked')) {
				jQuery(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').hide();
			} else {
				jQuery(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').show();
			}

		}).change();

	});

	jQuery('.show_options_if_checked').each(function(){

		jQuery(this).find('input:eq(0)').change(function() {

			if (jQuery(this).is(':checked')) {
				jQuery(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').show();
			} else {
				jQuery(this).closest('fieldset, tr').nextUntil( '.hide_options_if_checked, .show_options_if_checked', '.hidden_option').hide();
			}

		}).change();

	});

	jQuery('input#woocommerce_demo_store').change(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#woocommerce_demo_store_notice').closest('tr').show();
		} else {
			jQuery('#woocommerce_demo_store_notice').closest('tr').hide();
		}
	}).change();

});