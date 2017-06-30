/*global ajaxurl, woocommerce_term_ordering_params */

/* Modifided script from the simple-page-ordering plugin */
jQuery( function( $ ) {

	$( 'table.widefat.wp-list-table tr' ).append(
		'<td class="column-handle"></td>'
	);

	$( 'table.widefat.wp-list-table' ).sortable({
		items: 'tbody tr:not(.inline-edit-row)',
		cursor: 'move',
		handle: '.column-handle',
		axis: 'y',
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'product-cat-placeholder',
		scrollSensitivity: 40,
		start: function( event, ui ) {
			if ( ! ui.item.hasClass( 'alternate' ) ) {
				ui.item.css( 'background-color', '#ffffff' );
			}
			ui.item.children( 'td, th' ).css( 'border-bottom-width', '0' );
			ui.item.css( 'outline', '1px solid #aaa' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
			ui.item.children( 'td, th' ).css( 'border-bottom-width', '1px' );
		},
		update: function( event, ui ) {
			var termid     = ui.item.find( '.check-column input' ).val(); // this post id
			var termparent = ui.item.find( '.parent' ).html();            // post parent

			var prevtermid = ui.item.prev().find( '.check-column input' ).val();
			var nexttermid = ui.item.next().find( '.check-column input' ).val();

			// Can only sort in same tree
			var prevtermparent, nexttermparent;
			if ( prevtermid !== undefined ) {
				prevtermparent = ui.item.prev().find( '.parent' ).html();
				if ( prevtermparent !== termparent) {
					prevtermid = undefined;
				}
			}

			if ( nexttermid !== undefined ) {
				nexttermparent = ui.item.next().find( '.parent' ).html();
				if ( nexttermparent !== termparent) {
					nexttermid = undefined;
				}
			}

			// If previous and next not at same tree level, or next not at same tree level and the previous is the parent of the next, or just moved item beneath its own children
			if ( ( prevtermid === undefined && nexttermid === undefined ) || ( nexttermid === undefined && nexttermparent === prevtermid ) || ( nexttermid !== undefined && prevtermparent === termid ) ) {
				$( 'table.widefat.wp-list-table' ).sortable( 'cancel' );
				return;
			}

			// Show Spinner
			ui.item.find( '.check-column input' ).hide().after( '<img alt="processing" src="images/wpspin_light.gif" class="waiting" style="margin-left: 6px;" />' );

			// Go do the sorting stuff via ajax
			$.post( ajaxurl, { action: 'woocommerce_term_ordering', id: termid, nextid: nexttermid, thetaxonomy: woocommerce_term_ordering_params.taxonomy }, function(response){
				if ( response === 'children' ) {
					window.location.reload();
				} else {
					ui.item.find( '.check-column input' ).show().siblings( 'img' ).remove();
				}
			});

			// Fix cell colors
			$( 'table.widefat tbody tr' ).each( function() {
				var i = jQuery( 'table.widefat tbody tr' ).index( this );
				if ( i%2 === 0 ) {
					jQuery( this ).addClass( 'alternate' );
				} else {
					jQuery( this ).removeClass( 'alternate' );
				}
			});
		}
	});

});
