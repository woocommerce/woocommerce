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
		$('.wc-metabox > h3').click( function(event){
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

	$('body').on( 'wc-init-datepickers', function() {
		$( ".date-picker-field, .date-picker" ).datepicker({
			dateFormat: "yy-mm-dd",
			numberOfMonths: 1,
			showButtonPanel: true,
		});
	});

	$('body').trigger( 'wc-init-datepickers' );

	// META BOXES - Open/close
	$('.wc-metaboxes-wrapper').on('click', '.wc-metabox h3', function(event){
		// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
		if ($(event.target).filter(':input, option').length) return;

		$(this).next('.wc-metabox-content').stop().slideToggle();
	})
	.on('click', '.expand_all', function(event){
		$(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > .wc-metabox-content').show();
		return false;
	})
	.on('click', '.close_all', function(event){
		$(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > .wc-metabox-content').hide();
		return false;
	});
	$('.wc-metabox.closed').each(function(){
		$(this).find('.wc-metabox-content').hide();
	});

});
