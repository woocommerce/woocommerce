jQuery( function ( $ ) {

	// Run tipTip
	function runTipTip() {
		// Remove any lingering tooltips
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

	// Allow Tabbing
	$( '#titlediv' ).find( '#title' ).keyup( function( event ) {
		var code = event.keyCode || event.which;

		// Tab key
		if ( code === '9' && $( '#woocommerce-coupon-description' ).size() > 0 ) {
			event.stopPropagation();
			$( '#woocommerce-coupon-description' ).focus();
			return false;
		}
	});

	$( '.wc-metaboxes-wrapper' ).on( 'click', '.wc-metabox > h3', function() {
		$( this ).parent( '.wc-metabox' ).toggleClass( 'closed' ).toggleClass( 'open' );
	});

	// Tabbed Panels
	$( document.body ).on( 'wc-init-tabbed-panels', function() {
		$( 'ul.wc-tabs' ).show();
		$( 'ul.wc-tabs a' ).click( function( e ) {
			e.preventDefault();
			var panel_wrap = $( this ).closest( 'div.panel-wrap' );
			$( 'ul.wc-tabs li', panel_wrap ).removeClass( 'active' );
			$( this ).parent().addClass( 'active' );
			$( 'div.panel', panel_wrap ).hide();
			$( $( this ).attr( 'href' ) ).show();
		});
		$( 'div.panel-wrap' ).each( function() {
			$( this ).find( 'ul.wc-tabs li' ).eq( 0 ).find( 'a' ).click();
		});
	}).trigger( 'wc-init-tabbed-panels' );

	// Date Picker
	$( document.body ).on( 'wc-init-datepickers', function() {
		$( '.date-picker-field, .date-picker' ).datepicker({
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true
		});
	}).trigger( 'wc-init-datepickers' );

	// Meta-Boxes - Open/close
	$( '.wc-metaboxes-wrapper' ).on( 'click', '.wc-metabox h3', function( event ) {
		// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
		if ( $( event.target ).filter( ':input, option, .sort' ).length ) {
			return;
		}

		$( this ).next( '.wc-metabox-content' ).stop().slideToggle();
	})
	.on( 'click', '.expand_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .wc-metabox-content' ).show();
		return false;
	})
	.on( 'click', '.close_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .wc-metabox-content' ).hide();
		return false;
	});
	$( '.wc-metabox.closed' ).each( function() {
		$( this ).find( '.wc-metabox-content' ).hide();
	});
});
