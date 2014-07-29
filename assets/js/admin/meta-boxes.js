/*global woocommerce_admin_meta_boxes */
jQuery( function ( $ ) {

	// run tip tip
	function runTipTip() {
		// remove any lingering tooltips
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );
		$( '.tips' ).tipTip({
			'attribute': 'data-tip',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 200
		});
	}

	runTipTip();

	// Allow tabbing
	$('#titlediv #title').keyup(function( event ) {
		var code = event.keyCode || event.which;

		if ( code == '9' && $('#woocommerce-coupon-description').size() > 0 ) {
			event.stopPropagation();
			$('#woocommerce-coupon-description').focus();
			return false;
		}
	});

	$(function(){
		jQuery('.wc-metabox > h3').click( function(event){
			$( this ).parent( '.wc-metabox' ).toggleClass( 'closed' ).toggleClass( 'open' );
		});
	});

	// TABS
	$('ul.wc-tabs').show();
	$('div.panel-wrap').each(function(){
		$(this).find('div.panel:not(:first)').hide();
	});
	$('ul.wc-tabs a').click(function(){
		var panel_wrap =  $(this).closest('div.panel-wrap');
		$('ul.wc-tabs li', panel_wrap).removeClass('active');
		$(this).parent().addClass('active');
		$('div.panel', panel_wrap).hide();
		$( $(this).attr('href') ).show();
		return false;
	});
	$('ul.wc-tabs li:visible').eq(0).find('a').click();

	// Chosen selects
	jQuery("select.chosen_select").chosen();

	jQuery("select.chosen_select_nostd").chosen({
		allow_single_deselect: 'true'
	});

	// Ajax Chosen Product Selectors
	jQuery("select.ajax_chosen_select_products").ajaxChosen({
		method: 	'GET',
		url: 		woocommerce_admin_meta_boxes.ajax_url,
		dataType: 	'json',
		afterTypeDelay: 100,
		data:		{
			action: 		'woocommerce_json_search_products',
			security: 		woocommerce_admin_meta_boxes.search_products_nonce
		}
	}, function (data) {
		var terms = {};

		$.each(data, function (i, val) {
			terms[i] = val;
		});

		return terms;
	});

	/**
	 * Load Chosen for select products and variations
	 *
	 * @return {void}
	 */
	function loadSelectProductAndVariation() {
		$( 'select.ajax_chosen_select_products_and_variations' ).ajaxChosen({
			method:         'GET',
			url:            woocommerce_admin_meta_boxes.ajax_url,
			dataType:       'json',
			afterTypeDelay: 100,
			data:           {
				action:   'woocommerce_json_search_products_and_variations',
				security: woocommerce_admin_meta_boxes.search_products_nonce
			}
		},
		function ( data ) {
			var terms = {};

			$.each(data, function ( i, val ) {
				terms[i] = val;
			});

			return terms;
		});
	}

	// Run on document load
	loadSelectProductAndVariation();

	// Load chosen inside WC Backbone Modal
	$( 'body' ).on( 'wc_backbone_modal_loaded', function ( e, target ) {
		if ( '#wc-modal-add-products' === target ) {
			loadSelectProductAndVariation();
		}
	});

	jQuery("select.ajax_chosen_select_downloadable_products_and_variations").ajaxChosen({
		method: 	'GET',
		url: 		woocommerce_admin_meta_boxes.ajax_url,
		dataType: 	'json',
		afterTypeDelay: 100,
		data:		{
			action: 		'woocommerce_json_search_downloadable_products_and_variations',
			security: 		woocommerce_admin_meta_boxes.search_products_nonce
		}
	}, function (data) {

		var terms = {};

		$.each(data, function (i, val) {
			terms[i] = val;
		});

		return terms;
	});

	$( ".date-picker" ).datepicker({
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "button",
		buttonImage: woocommerce_admin_meta_boxes.calendar_image,
		buttonImageOnly: true
	});

	$( ".date-picker-field" ).datepicker({
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
	});

	// META BOXES - Open/close
	jQuery('.wc-metaboxes-wrapper').on('click', '.wc-metabox h3', function(event){
		// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
		if ($(event.target).filter(':input, option').length) return;

		jQuery(this).next('.wc-metabox-content').toggle();
	})
	.on('click', '.expand_all', function(event){
		jQuery(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > table').show();
		return false;
	})
	.on('click', '.close_all', function(event){
		jQuery(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > table').hide();
		return false;
	});
	jQuery('.wc-metabox.closed').each(function(){
		jQuery(this).find('.wc-metabox-content').hide();
	});

});
