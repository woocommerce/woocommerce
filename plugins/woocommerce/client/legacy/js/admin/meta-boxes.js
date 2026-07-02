jQuery( function ( $ ) {
	/**
	 * Function to check if the attribute and variation fields are empty.
	 */
	jQuery.is_attribute_or_variation_empty = function (
		$attributes_and_variations_data
	) {
		let has_empty_fields = false;
		$attributes_and_variations_data.each( function () {
			const $this = $( this );
			// Check if the field is optional, a checkbox or a search field.
			if (
				$this.hasClass( 'optional_attribute_or_variation_data' ) ||
				$this.hasClass( 'checkbox' ) ||
				$this.filter( '[class*=search__field]' ).length
			) {
				return;
			}

			const is_empty = $this.is( 'select' )
				? $this.find( ':selected' ).length === 0
				: ! $this.val();
			if ( is_empty ) {
				has_empty_fields = true;
			}
		} );
		return has_empty_fields;
	};

	/**
	 * Function to maybe disable the save button.
	 */
	jQuery.maybe_disable_save_button = function () {
		let $tab;
		let $save_button;
		if (
			$( '.woocommerce_variation_new_attribute_data' ).is( ':visible' )
		) {
			$tab = $( '.woocommerce_variation_new_attribute_data' );
			$save_button = $( 'button.create-variations' );
		} else {
			$tab = $( '.product_attributes' );
			$save_button = $( 'button.save_attributes' );
		}

		const $attributes_and_variations_data = $tab.find(
			'input, select, textarea'
		);
		if (
			jQuery.is_attribute_or_variation_empty(
				$attributes_and_variations_data
			)
		) {
			if ( ! $save_button.hasClass( 'disabled' ) ) {
				$save_button.addClass( 'disabled' );
				$save_button.attr( 'aria-disabled', true );
			}
		} else {
			$save_button.removeClass( 'disabled' );
			$save_button.removeAttr( 'aria-disabled' );
		}
	};

	// Run tipTip
	function runTipTip() {
		// Remove any lingering tooltips
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );
		$( '.tips' ).tipTip( {
			attribute: 'data-tip',
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
			keepAlive: true,
		} );
	}

	runTipTip();

	$( '.save_attributes' ).tipTip( {
		content: function () {
			return $( '.save_attributes' ).hasClass( 'disabled' )
				? woocommerce_admin_meta_boxes.i18n_save_attribute_variation_tip
				: '';
		},
		fadeIn: 50,
		fadeOut: 50,
		delay: 200,
		keepAlive: true,
	} );

	$( '.create-variations' ).tipTip( {
		content: function () {
			return $( '.create-variations' ).hasClass( 'disabled' )
				? woocommerce_admin_meta_boxes.i18n_save_attribute_variation_tip
				: '';
		},
		fadeIn: 50,
		fadeOut: 50,
		delay: 200,
		keepAlive: true,
	} );

	$( '.wc-metaboxes-wrapper' ).on( 'click', '.wc-metabox > h3', function () {
		const $metabox = $( this ).parent( '.wc-metabox' );

		if ( $metabox.hasClass( 'closed' ) ) {
			$metabox.removeClass( 'closed' );
		} else {
			$metabox.addClass( 'closed' );
		}

		if ( $metabox.hasClass( 'open' ) ) {
			$metabox.removeClass( 'open' );
		} else {
			$metabox.addClass( 'open' );
		}
	} );

	// Tabbed Panels
	$( document.body )
		.on( 'wc-init-tabbed-panels', function () {
			$( 'ul.wc-tabs' ).show();

			const focusable_elements = [
				'a[href]',
				'area[href]',
				'button:not([disabled])',
				'input:not([disabled]):not([type="hidden"])',
				'select:not([disabled])',
				'textarea:not([disabled])',
				'iframe',
				'object',
				'embed',
				'[tabindex]:not([tabindex="-1"])',
				'[contenteditable="true"]',
			].join( ', ' );

			const get_tab_target_id = function ( $tab ) {
				return ( $tab.attr( 'href' ) || '' ).replace( /^#/, '' );
			};

			const get_tab_panel = function ( $tab, $panel_wrap ) {
				const target_id = get_tab_target_id( $tab );
				if ( ! target_id ) {
					return $();
				}

				const $panel = $( document.getElementById( target_id ) );
				if (
					$panel.length &&
					$.contains( $panel_wrap.get( 0 ), $panel.get( 0 ) )
				) {
					return $panel;
				}

				return $();
			};

			const maybe_update_panel_tabindex = function ( $panel ) {
				if ( $panel.find( focusable_elements ).length ) {
					if ( $panel.data( 'wcTabpanelTabindex' ) ) {
						$panel
							.removeAttr( 'tabindex' )
							.removeData( 'wcTabpanelTabindex' );
					}
					return;
				}

				$panel.attr( 'tabindex', '0' );
				$panel.data( 'wcTabpanelTabindex', true );
			};

			// Wire each tab and panel up so extension tabs get the same semantics.
			$( 'div.panel-wrap' ).each( function () {
				const $panel_wrap = $( this );
				$panel_wrap
					.find( 'ul.wc-tabs' )
					.attr( 'role', 'tablist' );
				$panel_wrap
					.find( 'ul.wc-tabs > li > a[href^="#"]' )
					.each( function () {
						const $tab = $( this );
						const target_id = get_tab_target_id( $tab );
						if ( ! target_id ) {
							return;
						}

						if ( ! $tab.attr( 'id' ) ) {
							$tab.attr( 'id', 'wc-tab-' + target_id );
						}

						$tab.parent().attr( 'role', 'presentation' );
						$tab
							.attr( 'role', 'tab' )
							.attr( 'aria-controls', target_id )
							.attr( 'aria-selected', 'false' )
							.attr( 'tabindex', '-1' );

						const $panel = get_tab_panel( $tab, $panel_wrap );
						if ( $panel.length ) {
							$panel
								.attr( 'role', 'tabpanel' )
								.attr( 'aria-labelledby', $tab.attr( 'id' ) );
							maybe_update_panel_tabindex( $panel );
						}
					} );
			} );

			const activate_tab = function ( $tab ) {
				if ( ! $tab || ! $tab.length ) {
					return;
				}
				const $panel_wrap = $tab.closest( 'div.panel-wrap' );
				const $panel = get_tab_panel( $tab, $panel_wrap );
				if ( ! $panel.length ) {
					return;
				}

				$panel_wrap.find( 'ul.wc-tabs li' ).removeClass( 'active' );
				$panel_wrap.find( 'ul.wc-tabs a[role="tab"]' )
					.attr( 'aria-selected', 'false' )
					.attr( 'tabindex', '-1' );

				$tab.parent().addClass( 'active' );
				$tab.attr( 'aria-selected', 'true' ).attr( 'tabindex', '0' );

				$panel_wrap.find( 'div.panel' ).hide();
				$panel.show( 0, function () {
					$( this ).trigger( 'woocommerce_tab_shown' );
				} );
			};

			$( 'ul.wc-tabs' )
				.off( 'click.wc-tabbed-panels', 'a[href^="#"]' )
				.on(
					'click.wc-tabbed-panels',
					'a[href^="#"]',
					function ( e ) {
						e.preventDefault();
						const $tab = $( this );
						activate_tab( $tab );
					}
				);

			// Arrow-key navigation per WAI-ARIA APG tabs pattern. WC tabs are stacked
			// vertically, so Up/Down move focus and activate; Home/End jump to ends.
			$( 'ul.wc-tabs' )
				.off( 'keydown.wc-tabbed-panels', 'a[role="tab"]' )
				.on(
					'keydown.wc-tabbed-panels',
					'a[role="tab"]',
					function ( e ) {
						const $visible_tabs = $( this )
							.closest( 'ul.wc-tabs' )
							.find( 'li:visible > a[role="tab"]' );
						if ( ! $visible_tabs.length ) {
							return;
						}
						const current_index = $visible_tabs.index( this );
						let target_index = null;

						switch ( e.key ) {
							case 'ArrowDown':
							case 'Down':
								target_index =
									( current_index + 1 ) %
									$visible_tabs.length;
								break;
							case 'ArrowUp':
							case 'Up':
								target_index =
									( current_index -
										1 +
										$visible_tabs.length ) %
									$visible_tabs.length;
								break;
							case 'Home':
								target_index = 0;
								break;
							case 'End':
								target_index = $visible_tabs.length - 1;
								break;
							default:
								return;
						}

						e.preventDefault();
						const $target = $visible_tabs.eq( target_index );
						activate_tab( $target );
						$target.trigger( 'focus' );
					}
				);

			$( 'div.panel-wrap' ).each( function () {
				let $first_tab = $( this )
					.find( 'ul.wc-tabs li:visible' )
					.eq( 0 )
					.find( 'a' );

				if ( ! $first_tab.length ) {
					$first_tab = $( this )
						.find( 'ul.wc-tabs li' )
						.eq( 0 )
						.find( 'a' );
				}

				if ( ! $first_tab.length ) {
					return;
				}

				$first_tab.trigger( 'click' );
			} );
		} )
		.trigger( 'wc-init-tabbed-panels' );

	// Date Picker
	$( document.body )
		.on( 'wc-init-datepickers', function () {
			$( '.date-picker-field, .date-picker' ).datepicker( {
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true,
			} );
		} )
		.trigger( 'wc-init-datepickers' );

	// Meta-Boxes - Open/close
	$( '.wc-metaboxes-wrapper' )
		.on( 'click', '.wc-metabox h3', function ( event ) {
			// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
			if ( $( event.target ).filter( ':input, option, .sort' ).length ) {
				return;
			}

			$( this ).next( '.wc-metabox-content' ).stop().slideToggle();
		} )
		.on( 'click', '.expand_all', function () {
			$( this )
				.closest( '.wc-metaboxes-wrapper' )
				.find( '.wc-metabox > .wc-metabox-content' )
				.show();
			return false;
		} )
		.on( 'click', '.close_all', function () {
			$( this )
				.closest( '.wc-metaboxes-wrapper' )
				.find( '.wc-metabox > .wc-metabox-content' )
				.hide();
			return false;
		} );
	$( '.wc-metabox.closed' ).each( function () {
		$( this ).find( '.wc-metabox-content' ).hide();
	} );

	$( '#product_attributes' ).on(
		'change',
		'select.attribute_values',
		jQuery.maybe_disable_save_button
	);
	$( '#product_attributes, #variable_product_options' ).on(
		'keyup',
		'input, textarea',
		jQuery.maybe_disable_save_button
	);

	// Maybe disable save buttons when editing products.
	jQuery.maybe_disable_save_button();
} );
