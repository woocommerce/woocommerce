/* global woocommerce_settings_params */
( function( $ ) {

	// Sell Countries
	$( 'select#woocommerce_allowed_countries' ).change( function() {
		if ( 'specific' === $( this ).val() ) {
			$( this ).closest('tr').next( 'tr' ).hide();
			$( this ).closest('tr').next().next( 'tr' ).show();
		} else if ( 'all_except' === $( this ).val() ) {
			$( this ).closest('tr').next( 'tr' ).show();
			$( this ).closest('tr').next().next( 'tr' ).hide();
		} else {
			$( this ).closest('tr').next( 'tr' ).hide();
			$( this ).closest('tr').next().next( 'tr' ).hide();
		}
	}).change();

	// Ship Countries
	$( 'select#woocommerce_ship_to_countries' ).change( function() {
		if ( 'specific' === $( this ).val() ) {
			$( this ).closest('tr').next( 'tr' ).show();
		} else {
			$( this ).closest('tr').next( 'tr' ).hide();
		}
	}).change();

	// Color picker
	$( '.colorpick' ).iris({
		change: function( event, ui ) {
			$( this ).parent().find( '.colorpickpreview' ).css({ backgroundColor: ui.color.toString() });
		},
		hide: true,
		border: true
	}).click( function() {
		$( '.iris-picker' ).hide();
		$( this ).closest( 'td' ).find( '.iris-picker' ).show();
	});

	$( 'body' ).click( function() {
		$( '.iris-picker' ).hide();
	});

	$( '.colorpick' ).click( function( event ) {
		event.stopPropagation();
	});

	// Edit prompt
	$( function() {
		var changed = false;

		$( 'input, textarea, select, checkbox' ).change( function() {
			changed = true;
		});

		$( '.woo-nav-tab-wrapper a' ).click( function() {
			if ( changed ) {
				window.onbeforeunload = function() {
				    return woocommerce_settings_params.i18n_nav_warning;
				};
			} else {
				window.onbeforeunload = '';
			}
		});

		$( '.submit input' ).click( function() {
			window.onbeforeunload = '';
		});
	});

	// Sorting
	$( 'table.wc_gateways tbody, table.wc_shipping tbody' ).sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.sort',
		scrollSensitivity: 40,
		helper: function( event, ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			});
			ui.css( 'left', '0' );
			return ui;
		},
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function( event, ui ) {
			ui.item.removeAttr( 'style' );
		}
	});

	// Select all/none
	$( '.woocommerce' ).on( 'click', '.select_all', function() {
		$( this ).closest( 'td' ).find( 'select option' ).attr( 'selected', 'selected' );
		$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
		return false;
	});

	$( '.woocommerce' ).on( 'click', '.select_none', function() {
		$( this ).closest( 'td' ).find( 'select option' ).removeAttr( 'selected' );
		$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
		return false;
	});

	// upload media
	var file_frame;

	$( '.wc-settings-media-upload' ).on( 'click', function( event ){

		event.preventDefault();

		var $el = $(this);

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
		  file_frame.open();
			return;
	    }

	    // Create the media frame.
	    file_frame = wp.media.frames.file_frame = wp.media({
			title: $el.data('media_frame_title'),
			button: {
				text: $el.data('media_button_text'),
			},
			multiple: false  // Set to true to allow multiple files to be selected
	    });


	    // When an image is selected, run a callback.
	    file_frame.on( 'select', function() {
			// We set multiple to false so only get one image from the uploader
			attachment = file_frame.state().get('selection').first().toJSON();

			// Do something with attachment.id and/or attachment.url here
			$el.next( '.wc-settings-media-id' ).val( attachment.id );

            var attachment, $preview = $el.prev( '.wc-settings-media-preview-wrapper' );
			
			// remove any existing 
            $preview.children().remove();

            if ( attachment.id > 0) {
                attachment = new wp.media.model.Attachment.get( attachment.id );

                attachment.fetch({
                    success: function(att) {
                    	console.log(att);
                        if (_.contains(['png', 'jpg', 'gif', 'jpeg'], att.get('subtype'))) {
                            $("<img/>").attr('src', att.attributes.sizes.thumbnail.url).appendTo($preview);
                        } else {
                        	$("<img/>").attr('src', att.attributes.icon).appendTo($preview);
                        }
                        $("<p/>").addClass('description').html( att.attributes.filename ).appendTo($preview);
                        $preview.siblings('.wc-settings-media-remove').show();
                    }
                });
            }
	    });

	    // Finally, open the modal
		file_frame.open();
 	});

	// the remove button
 	$( '.wc-settings-media-remove' ).on( 'click', function( event ){

		event.preventDefault();

		var $el = $(this);

		$el.siblings( '.wc-settings-media-preview-wrapper' ).children().remove();
		$el.prev('.wc-settings-media-id' ).val( '' );
		$el.hide();

	});

})( jQuery );
