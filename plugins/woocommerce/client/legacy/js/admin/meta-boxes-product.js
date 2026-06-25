/*global woocommerce_admin_meta_boxes, _ */
jQuery( function ( $ ) {
	let isPageUnloading = false;

	$( window ).on( 'beforeunload', function () {
		isPageUnloading = true;
	} );

	// Scroll to first checked category
	// https://github.com/scribu/wp-category-checklist-tree/blob/d1c3c1f449e1144542efa17dde84a9f52ade1739/category-checklist-tree.php
	$( function () {
		$( '[id$="-all"] > ul.categorychecklist' ).each( function () {
			var $list = $( this );
			var $firstChecked = $list.find( ':checked' ).first();

			if ( ! $firstChecked.length ) {
				return;
			}

			var pos_first = $list.find( 'input' ).position().top;
			var pos_checked = $firstChecked.position().top;

			$list
				.closest( '.tabs-panel' )
				.scrollTop( pos_checked - pos_first + 5 );
		} );
	} );

	// Prevent enter submitting post form.
	$( '#upsell_product_data' ).on( 'keypress', function ( e ) {
		if ( e.keyCode === 13 ) {
			return false;
		}
	} );

	// Type box.
	if ( $( 'body' ).hasClass( 'wc-wp-version-gte-55' ) ) {
		$( '.type_box' ).appendTo( '#woocommerce-product-data .hndle' );
	} else {
		$( '.type_box' ).appendTo( '#woocommerce-product-data .hndle span' );
	}

	$( function () {
		var woocommerce_product_data = $( '#woocommerce-product-data' );

		// Prevent inputs in meta box headings opening/closing contents.
		woocommerce_product_data.find( '.hndle' ).off( 'click.postboxes' );

		woocommerce_product_data.on( 'click', '.hndle', function ( event ) {
			// If the user clicks on some form input inside the h3 the box should not be toggled.
			if (
				$( event.target ).filter( 'input, option, label, select' )
					.length
			) {
				return;
			}

			if ( woocommerce_product_data.hasClass( 'closed' ) ) {
				woocommerce_product_data.removeClass( 'closed' );
			} else {
				woocommerce_product_data.addClass( 'closed' );
			}
		} );
	} );

	// Catalog Visibility.
	$( '#catalog-visibility' )
		.find( '.edit-catalog-visibility' )
		.on( 'click', function () {
			if ( $( '#catalog-visibility-select' ).is( ':hidden' ) ) {
				$( '#catalog-visibility-select' ).slideDown( 'fast' );
				$( this ).hide();
			}
			return false;
		} );
	$( '#catalog-visibility' )
		.find( '.save-post-visibility' )
		.on( 'click', function () {
			$( '#catalog-visibility-select' ).slideUp( 'fast' );
			$( '#catalog-visibility' )
				.find( '.edit-catalog-visibility' )
				.show();

			var label = $( 'input[name=_visibility]:checked' ).attr(
				'data-label'
			);

			if ( $( 'input[name=_featured]' ).is( ':checked' ) ) {
				label =
					label + ', ' + woocommerce_admin_meta_boxes.featured_label;
				$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
			}

			$( '#catalog-visibility-display' ).text( label );
			return false;
		} );
	$( '#catalog-visibility' )
		.find( '.cancel-post-visibility' )
		.on( 'click', function () {
			$( '#catalog-visibility-select' ).slideUp( 'fast' );
			$( '#catalog-visibility' )
				.find( '.edit-catalog-visibility' )
				.show();

			var current_visibility = $( '#current_visibility' ).val();
			var current_featured = $( '#current_featured' ).val();

			$( 'input[name=_visibility]' ).prop( 'checked', false );
			$(
				'input[name=_visibility][value=' + current_visibility + ']'
			).attr( 'checked', 'checked' );

			var label = $( 'input[name=_visibility]:checked' ).attr(
				'data-label'
			);

			if ( 'yes' === current_featured ) {
				label =
					label + ', ' + woocommerce_admin_meta_boxes.featured_label;
				$( 'input[name=_featured]' ).attr( 'checked', 'checked' );
			} else {
				$( 'input[name=_featured]' ).prop( 'checked', false );
			}

			$( '#catalog-visibility-display' ).text( label );
			return false;
		} );

	// Product type specific options.
	$( 'select#product-type' )
		.on( 'change', function () {
			// Get value.
			var select_val = $( this ).val();

			if ( 'variable' === select_val ) {
				$( 'input#_manage_stock' ).trigger( 'change' );
				$( 'input#_downloadable' ).prop( 'checked', false );
				$( 'input#_virtual' ).prop( 'checked', false );
			} else if ( 'grouped' === select_val ) {
				$( 'input#_downloadable' ).prop( 'checked', false );
				$( 'input#_virtual' ).prop( 'checked', false );
			} else if ( 'external' === select_val ) {
				$( 'input#_downloadable' ).prop( 'checked', false );
				$( 'input#_virtual' ).prop( 'checked', false );
			}

			const cogs_field_tip = $( '._cogs_value_field' ).find(
				'.woocommerce-help-tip'
			);
			const cogs_field_tip_text =
				'variable' === select_val
					? woocommerce_admin_meta_boxes.cogs_value_tooltip_variable_products
					: woocommerce_admin_meta_boxes.cogs_value_tooltip_simple_products;
			$( cogs_field_tip ).attr( 'aria-label', cogs_field_tip_text );
			$( cogs_field_tip ).tipTip( {
				attribute: 'aria-label',
				fadeIn: 50,
				fadeOut: 50,
				delay: 200,
				keepAlive: true,
			} );

			show_and_hide_panels();
			change_product_type_tip( get_product_tip_content( select_val ) );

			$( 'ul.wc-tabs li:visible' ).eq( 0 ).find( 'a' ).trigger( 'click' );

			$( document.body ).trigger(
				'woocommerce-product-type-change',
				select_val,
				$( this )
			);
		} )
		.trigger( 'change' );

	$( 'input#_downloadable' ).on( 'change', function () {
		show_and_hide_panels();
	} );

	$( 'input#_virtual' ).on( 'change', function () {
		show_and_hide_panels();

		// If user enables virtual while on shipping tab, switch to general tab.
		if (
			$( this ).is( ':checked' ) &&
			$( '.shipping_options.shipping_tab' ).hasClass( 'active' )
		) {
			$( '.general_options.general_tab > a' ).trigger( 'click' );
		}
	} );

	function change_product_type_tip( content ) {
		$( '#tiptip_holder' ).removeAttr( 'style' );
		$( '#tiptip_arrow' ).removeAttr( 'style' );
		$( '.woocommerce-product-type-tip' )
			.attr( 'tabindex', '0' )
			.attr( 'aria-label', $( '<div />' ).html( content ).text() ) // Remove HTML tags.
			.tipTip( {
				attribute: 'data-tip',
				content: content,
				fadeIn: 50,
				fadeOut: 50,
				delay: 200,
				keepAlive: true,
			} );
	}

	function get_product_tip_content( product_type ) {
		switch ( product_type ) {
			case 'simple':
				return woocommerce_admin_meta_boxes.i18n_product_simple_tip;
			case 'grouped':
				return woocommerce_admin_meta_boxes.i18n_product_grouped_tip;
			case 'external':
				return woocommerce_admin_meta_boxes.i18n_product_external_tip;
			case 'variable':
				return woocommerce_admin_meta_boxes.i18n_product_variable_tip;
			default:
				return woocommerce_admin_meta_boxes.i18n_product_other_tip;
		}
	}

	function show_and_hide_controls( context ) {
		var product_type = $( 'select#product-type' ).val();
		var is_virtual = $( 'input#_virtual:checked' ).length;
		var is_downloadable = $( 'input#_downloadable:checked' ).length;

		// Hide/Show all with rules.
		var hide_classes = '.hide_if_downloadable, .hide_if_virtual';
		var show_classes = '.show_if_downloadable, .show_if_virtual';

		$.each(
			woocommerce_admin_meta_boxes.product_types,
			function ( index, value ) {
				hide_classes = hide_classes + ', .hide_if_' + value;
				show_classes = show_classes + ', .show_if_' + value;
			}
		);

		$( hide_classes, context ).show();
		$( show_classes, context ).hide();

		// Shows rules.
		if ( is_downloadable ) {
			$( '.show_if_downloadable', context ).show();
		}
		if ( is_virtual ) {
			$( '.show_if_virtual', context ).show();
		}

		$( '.show_if_' + product_type, context ).show();

		// Hide rules.
		if ( is_downloadable ) {
			$( '.hide_if_downloadable', context ).hide();
		}
		if ( is_virtual ) {
			$( '.hide_if_virtual', context ).hide();
		}

		$( '.hide_if_' + product_type, context ).hide();

		// POS visibility - requires combination of type AND downloadable status.
		var is_pos_supported =
			( product_type === 'simple' || product_type === 'variable' ) &&
			! is_downloadable;
		if ( is_pos_supported ) {
			$( '#pos_visibility_supported', context ).show();
			$( '#pos_visibility_unsupported', context ).hide();
		} else {
			$( '#pos_visibility_supported', context ).hide();
			$( '#pos_visibility_unsupported', context ).show();
		}
	}

	function show_and_hide_panels() {
		show_and_hide_controls();

		$( 'input#_manage_stock' ).trigger( 'change' );

		// Hide empty panels/tabs after display.
		$( '.woocommerce_options_panel' ).each( function () {
			var $children = $( this ).children( '.options_group' );

			if ( 0 === $children.length ) {
				return;
			}

			var $invisible = $children.filter( function () {
				return 'none' === $( this ).css( 'display' );
			} );

			// Hide panel.
			if ( $invisible.length === $children.length ) {
				var $id = $( this ).prop( 'id' );
				$( '.product_data_tabs' )
					.find( 'li a[href="#' + $id + '"]' )
					.parent()
					.hide();
			}
		} );
	}

	// Sale price schedule.
	$( '.sale_price_dates_fields' ).each( function () {
		var $these_sale_dates = $( this );
		var sale_schedule_set = false;
		var $wrap = $these_sale_dates.closest( 'div, table' );

		$these_sale_dates.find( 'input' ).each( function () {
			if ( '' !== $( this ).val() ) {
				sale_schedule_set = true;
			}
		} );

		if ( sale_schedule_set ) {
			$wrap.find( '.sale_schedule' ).hide();
			$wrap.find( '.sale_price_dates_fields' ).show();
		} else {
			$wrap.find( '.sale_schedule' ).show();
			$wrap.find( '.sale_price_dates_fields' ).hide();
		}
	} );

	$( '#woocommerce-product-data' ).on(
		'click',
		'.sale_schedule',
		function () {
			var $wrap = $( this ).closest( 'div, table' );

			$( this ).hide();
			$wrap.find( '.cancel_sale_schedule' ).show();
			$wrap.find( '.sale_price_dates_fields' ).show();

			return false;
		}
	);
	$( '#woocommerce-product-data' ).on(
		'click',
		'.cancel_sale_schedule',
		function () {
			var $wrap = $( this ).closest( 'div, table' );

			$( this ).hide();
			$wrap.find( '.sale_schedule' ).show();
			$wrap.find( '.sale_price_dates_fields' ).hide();
			$wrap.find( '.sale_price_dates_fields' ).find( 'input' ).val( '' );

			return false;
		}
	);

	// File inputs.
	$( '#woocommerce-product-data' ).on(
		'click',
		'.downloadable_files a.insert',
		function () {
			$( this )
				.closest( '.downloadable_files' )
				.find( 'tbody' )
				.append( $( this ).data( 'row' ) );
			return false;
		}
	);
	$( '#woocommerce-product-data' ).on(
		'click',
		'.downloadable_files a.delete',
		function () {
			$( this ).closest( 'tr' ).remove();
			return false;
		}
	);

	// Stock options.
	function show_or_hide_stock_management_fields(
		isStockManagementEnabled,
		productType
	) {
		const $stockManagementFields = $( '.stock_fields' );
		const $stockStatusField = $( '.stock_status_field' );

		$stockManagementFields.toggle( isStockManagementEnabled );
		$stockStatusField.toggle(
			! isStockManagementEnabled &&
				// do not show stock status field if it should be hidden for the product type
				! $stockStatusField.is( '.hide_if_' + productType )
		);
	}

	$( 'input#_manage_stock' )
		.on( 'change', function () {
			const isStockManagementEnabled = $( this ).is( ':checked' );
			const productType = $( 'select#product-type' ).val();

			show_or_hide_stock_management_fields(
				isStockManagementEnabled,
				productType
			);

			$( 'input.variable_manage_stock' ).trigger( 'change' );
		} )
		.trigger( 'change' );

	// Date picker fields.
	function date_picker_select( datepicker ) {
		var option = $( datepicker ).next().is( '.hasDatepicker' )
				? 'minDate'
				: 'maxDate',
			otherDateField =
				'minDate' === option
					? $( datepicker ).next()
					: $( datepicker ).prev(),
			date = $( datepicker ).datepicker( 'getDate' );

		$( otherDateField ).datepicker( 'option', option, date );
		$( datepicker ).trigger( 'change' );
	}

	$( '.sale_price_dates_fields' ).each( function () {
		$( this )
			.find( 'input' )
			.datepicker( {
				defaultDate: '',
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showButtonPanel: true,
				onSelect: function () {
					date_picker_select( $( this ) );
				},
			} );
		$( this )
			.find( 'input' )
			.each( function () {
				date_picker_select( $( this ) );
			} );
	} );

	// Set up attributes, if current page has the attributes list.
	const $product_attributes = $( '.product_attributes' );
	if ( $product_attributes.length === 1 ) {
		// When the attributes tab is shown, add an empty attribute to be filled out by the user.
		$( '#product_attributes' ).on( 'woocommerce_tab_shown', function () {
			remove_blank_custom_attribute_if_no_other_attributes();

			const woocommerce_attribute_items = $product_attributes
				.find( '.woocommerce_attribute' )
				.get();

			// If the product has no attributes, add an empty attribute to be filled out by the user.
			if ( woocommerce_attribute_items.length === 0 ) {
				add_custom_attribute_to_list();
			}
		} );

		const woocommerce_attribute_items = $product_attributes
			.find( '.woocommerce_attribute' )
			.get();

		// Sort the attributes by their position.
		woocommerce_attribute_items.sort( function ( a, b ) {
			var compA = parseInt( $( a ).attr( 'rel' ), 10 );
			var compB = parseInt( $( b ).attr( 'rel' ), 10 );
			return compA < compB ? -1 : compA > compB ? 1 : 0;
		} );

		$( woocommerce_attribute_items ).each( function ( index, el ) {
			$product_attributes.append( el );
		} );
	}

	function update_attribute_row_indexes() {
		$( '.product_attributes .woocommerce_attribute' ).each( function (
			index,
			el
		) {
			$( '.attribute_position', el ).val(
				parseInt(
					$( el ).index(
						'.product_attributes .woocommerce_attribute'
					),
					10
				)
			);
		} );
	}

	var selectedAttributes = [];
	var currentAttributeTermCreationContext = null;
	$( '.product_attributes .woocommerce_attribute' ).each( function (
		index,
		el
	) {
		if (
			$( el ).css( 'display' ) !== 'none' &&
			$( el ).is( '.taxonomy' )
		) {
			selectedAttributes.push( $( el ).data( 'taxonomy' ) );
			$( 'select.attribute_taxonomy' )
				.find( 'option[value="' + $( el ).data( 'taxonomy' ) + '"]' )
				.attr( 'disabled', 'disabled' );
		}

		if (
			'undefined' === $( el ).attr( 'data-taxonomy' ) ||
			false === $( el ).attr( 'data-taxonomy' ) ||
			'' === $( el ).attr( 'data-taxonomy' )
		) {
			add_placeholder_to_attribute_values_field( $( el ) );

			$(
				'.woocommerce_attribute input.woocommerce_attribute_used_for_variations'
			).on( 'change', function () {
				add_placeholder_to_attribute_values_field( $( el ) );
			} );
		}
	} );
	$( 'select.wc-attribute-search' ).data(
		'disabled-items',
		selectedAttributes
	);

	function get_new_attribute_list_item_html(
		indexInList,
		globalAttributeId
	) {
		return new Promise( function ( resolve, reject ) {
			$.post( {
				url: woocommerce_admin_meta_boxes.ajax_url,
				data: {
					action: 'woocommerce_add_attribute',
					product_type: $( '#product-type' ).val(),
					taxonomy: globalAttributeId ? globalAttributeId : '',
					i: indexInList,
					security: woocommerce_admin_meta_boxes.add_attribute_nonce,
				},
				success: function ( newAttributeListItemHtml ) {
					resolve( newAttributeListItemHtml );
				},
				error: function ( jqXHR, textStatus, errorThrown ) {
					reject( { jqXHR, textStatus, errorThrown } );
				},
			} );
		} );
	}

	function block_attributes_tab_container() {
		const $attributesTabContainer = $( '#product_attributes' );

		$attributesTabContainer.block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		} );
	}

	function unblock_attributes_tab_container() {
		const $attributesTabContainer = $( '#product_attributes' );
		$attributesTabContainer.unblock();
	}

	function toggle_expansion_of_attribute_list_item( $attributeListItem ) {
		$attributeListItem.find( 'h3' ).trigger( 'click' );
	}

	function add_placeholder_to_attribute_values_field( $attributeListItem ) {
		var $used_for_variations_checkbox = $attributeListItem.find(
			'input.woocommerce_attribute_used_for_variations'
		);

		if (
			$used_for_variations_checkbox.length &&
			$used_for_variations_checkbox.is( ':checked' )
		) {
			$attributeListItem
				.find( 'textarea' )
				.attr(
					'placeholder',
					woocommerce_admin_meta_boxes.i18n_attributes_used_for_variations_placeholder
				);
		} else {
			$attributeListItem
				.find( 'textarea' )
				.attr(
					'placeholder',
					woocommerce_admin_meta_boxes.i18n_attributes_default_placeholder
				);
		}
	}

	function init_select_controls() {
		$( document.body ).trigger( 'wc-enhanced-select-init' );
	}

	async function add_attribute_to_list( globalAttributeId ) {
		try {
			block_attributes_tab_container();

			const numberOfAttributesInList = $(
				'.product_attributes .woocommerce_attribute'
			).length;
			const newAttributeListItemHtml =
				await get_new_attribute_list_item_html(
					numberOfAttributesInList,
					globalAttributeId
				);

			const $attributesListContainer = $(
				'#product_attributes .product_attributes'
			);

			const $attributeListItem = $( newAttributeListItemHtml ).appendTo(
				$attributesListContainer
			);

			show_and_hide_controls( $attributeListItem );

			init_select_controls(); // make sure any new select controls in the new list item are initialized

			update_attribute_row_indexes();

			toggle_expansion_of_attribute_list_item( $attributeListItem );

			// Conditionally change the placeholder of product-level Attributes depending on the value of the "Use for variations" checkbox.
			if ( 'undefined' === typeof globalAttributeId ) {
				add_placeholder_to_attribute_values_field( $attributeListItem );

				$(
					'.woocommerce_attribute input.woocommerce_attribute_used_for_variations'
				).on( 'change', function () {
					add_placeholder_to_attribute_values_field(
						$( this ).closest( '.woocommerce_attribute' )
					);
				} );
			}

			$( document.body ).trigger( 'woocommerce_added_attribute' );

			jQuery.maybe_disable_save_button();
		} catch ( error ) {
			if ( isPageUnloading ) {
				// If the page is unloading, the outstanding ajax fetch may fail in Firefox (and possible other browsers, too).
				// We don't want to show an error message in this case, because it was caused by the user leaving the page.
				return;
			}

			alert(
				woocommerce_admin_meta_boxes.i18n_add_attribute_error_notice
			);
			throw error;
		} finally {
			unblock_attributes_tab_container();
		}
	}

	function add_global_attribute_to_list( globalAttributeId ) {
		add_attribute_to_list( globalAttributeId );
	}

	function add_custom_attribute_to_list() {
		add_attribute_to_list();
	}

	function add_if_not_exists( arr, item ) {
		return arr.includes( item ) ? attr : [ ...arr, item ];
	}

	function disable_in_attribute_search( selectedAttributes ) {
		$( 'select.wc-attribute-search' ).data(
			'disabled-items',
			selectedAttributes
		);
	}

	function remove_blank_custom_attribute_if_no_other_attributes() {
		const $attributes = $( '.product_attributes .woocommerce_attribute' );

		if ( $attributes.length === 1 ) {
			const $attribute = $attributes.first();

			const $attributeName = $attribute.find(
				'input[name="attribute_names[0]"]'
			);
			const $attributeValue = $attribute.find(
				'input[name="attribute_values[0]"]'
			);

			if ( ! $attributeName.val() && ! $attributeValue.val() ) {
				$attribute.remove();
			}
		}
	}

	// Handle the Attributes onboarding dismissible notice.
	// If users dismiss the notice, never show it again.
	if ( localStorage.getItem( 'attributes-notice-dismissed' ) ) {
		$( '#product_attributes .notice' ).hide();
	}

	$( '#product_attributes .notice.woocommerce-message button' ).on(
		'click',
		function ( e ) {
			$( '#product_attributes .notice' ).hide();
			localStorage.setItem( 'attributes-notice-dismissed', 'true' );
		}
	);

	$( 'select.wc-attribute-search' ).on( 'select2:select', function ( e ) {
		const attributeId = e && e.params && e.params.data && e.params.data.id;

		if ( attributeId ) {
			remove_blank_custom_attribute_if_no_other_attributes();

			add_global_attribute_to_list( attributeId );

			selectedAttributes = add_if_not_exists(
				selectedAttributes,
				attributeId
			);
			disable_in_attribute_search( selectedAttributes );
		}

		$( this ).val( null );
		$( this ).trigger( 'change' );

		return false;
	} );

	// Add rows.

	$( 'button.add_custom_attribute' ).on( 'click', function () {
		add_custom_attribute_to_list();

		return false;
	} );

	$( '.product_attributes' ).on( 'blur', 'input.attribute_name', function () {
		var $inputElement = $( this );
		var text = $inputElement.val();
		var $attribute = $inputElement
			.closest( '.woocommerce_attribute' )
			.find( 'strong.attribute_name' );
		if ( text === '' ) {
			$attribute
				.addClass( 'placeholder' )
				.text(
					woocommerce_admin_meta_boxes.i18n_attribute_name_placeholder
				);
		} else {
			$attribute.removeClass( 'placeholder' ).text( text );
		}
	} );

	$( '.product_attributes' ).on(
		'click',
		'button.select_all_attributes',
		function () {
			$( '.product_attributes' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			var $wrapper = $( this ).closest( '.woocommerce_attribute' );
			var attribute = $wrapper.data( 'taxonomy' );

			var data = {
				action: 'woocommerce_json_search_taxonomy_terms',
				taxonomy: attribute,
				security: wc_enhanced_select_params.search_taxonomy_terms_nonce,
			};

			$.get(
				woocommerce_admin_meta_boxes.ajax_url,
				data,
				function ( response ) {
					if ( response.errors ) {
						// Error.
						window.alert( response.errors );
					} else if ( response && response.length > 0 ) {
						// Success.
						response.forEach( function ( term ) {
							const currentItem = $wrapper.find(
								'select.attribute_values option[value="' +
									term.term_id +
									'"]'
							);
							if ( currentItem && currentItem.length > 0 ) {
								currentItem.prop( 'selected', 'selected' );
							} else {
								$wrapper
									.find( 'select.attribute_values' )
									.append(
										'<option value="' +
											term.term_id +
											'" selected="selected">' +
											term.name +
											'</option>'
									);
							}
						} );
						$wrapper
							.find( 'select.attribute_values' )
							.trigger( 'change' );
					}

					$( '.product_attributes' ).unblock();
				}
			);
			return false;
		}
	);

	$( '.product_attributes' ).on(
		'click',
		'button.select_no_attributes',
		function () {
			$( this )
				.closest( 'td' )
				.find( 'select option' )
				.prop( 'selected', false );
			$( this ).closest( 'td' ).find( 'select' ).trigger( 'change' );
			return false;
		}
	);

	$( '#product_attributes' ).on(
		'click',
		'.product_attributes .remove_row',
		function () {
			var $parent = $( this ).parent().parent();
			var isUsedForVariations = $parent
				.find( 'input[name^="attribute_variation"]' )
				.is( ':visible:checked' );

			if (
				! isUsedForVariations ||
				window.confirm(
					woocommerce_admin_meta_boxes.i18n_remove_used_attribute_confirmation_message
				)
			) {
				if ( $parent.is( '.taxonomy' ) ) {
					$parent.find( 'select, input[type=text]' ).val( '' );
					$parent.hide();
					$( 'select.attribute_taxonomy' )
						.find(
							'option[value="' + $parent.data( 'taxonomy' ) + '"]'
						)
						.prop( 'disabled', false );
					selectedAttributes = selectedAttributes.filter(
						( attr ) => attr !== $parent.data( 'taxonomy' )
					);
					$( 'select.wc-attribute-search' ).data(
						'disabled-items',
						selectedAttributes
					);
				} else {
					$parent.find( 'select, input[type=text]' ).val( '' );
					$parent.hide();
					update_attribute_row_indexes();
				}

				$parent.remove();

				window.wcTracks.recordEvent( 'product_attributes_buttons', {
					action: 'remove_attribute',
				} );

				jQuery.maybe_disable_save_button();
			}
			return false;
		}
	);

	// Attribute ordering.
	$( '.product_attributes' ).sortable( {
		items: '.woocommerce_attribute',
		cursor: 'move',
		axis: 'y',
		handle: 'h3',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function ( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function ( event, ui ) {
			ui.item.removeAttr( 'style' );
			update_attribute_row_indexes();
		},
	} );

	$( document.body ).on(
		'wc_backbone_modal_loaded',
		function ( event, target ) {
			if ( 'wc-modal-add-attribute-term' !== target ) {
				return;
			}

			const modal = document.querySelector(
				'.wc-backbone-modal-add-attribute-term'
			);

			if ( ! modal ) {
				return;
			}

			const termInput = modal.querySelector(
				'#wc-modal-add-attribute-term-input'
			);
			if ( termInput ) {
				termInput.focus();
			}

			const form = modal.querySelector( '.wc-add-attribute-term-fields' );
			if ( form ) {
				form.addEventListener( 'submit', ( submitEvent ) => {
					submitEvent.preventDefault();

					const submitButton = modal.querySelector( '#btn-ok' );
					if ( submitButton && ! submitButton.disabled ) {
						submitButton.click();
					}
				} );
			}
		}
	);

	$( document.body ).on(
		'wc_backbone_modal_validation',
		function ( event, target, postedData ) {
			if ( 'wc-modal-add-attribute-term' !== target ) {
				return;
			}

			const modal = document.querySelector(
				'.wc-backbone-modal-add-attribute-term'
			);

			if ( ! modal ) {
				return;
			}

			const submitButton = modal.querySelector( '#btn-ok' );

			if ( ! submitButton ) {
				return;
			}

			const termName =
				postedData && postedData.term ? postedData.term.trim() : '';
			const hasValue = termName.length > 0;

			submitButton.disabled = ! hasValue;
		}
	);

	$( document.body ).on(
		'wc_backbone_modal_response',
		function ( event, target, postedData ) {
			if ( 'wc-modal-add-attribute-term' !== target ) {
				return;
			}

			if (
				! currentAttributeTermCreationContext ||
				! currentAttributeTermCreationContext.wrapper ||
				! currentAttributeTermCreationContext.attribute
			) {
				$( '.product_attributes' ).unblock();
				return;
			}

			const termName =
				postedData && postedData.term ? postedData.term.trim() : '';

			if ( ! termName ) {
				$( '.product_attributes' ).unblock();
				return;
			}

			const wrapper = currentAttributeTermCreationContext.wrapper;
			const data = {
				action: 'woocommerce_add_new_attribute',
				taxonomy: currentAttributeTermCreationContext.attribute,
				term: termName,
				security: woocommerce_admin_meta_boxes.add_attribute_nonce,
			};

			if (
				currentAttributeTermCreationContext.isVisualAttribute &&
				postedData
			) {
				if ( postedData.wc_visual_attribute_type ) {
					data.wc_visual_attribute_type =
						postedData.wc_visual_attribute_type;
				}

				if ( postedData.term_color ) {
					data.term_color = postedData.term_color;
				}

				if ( postedData.term_image ) {
					data.term_image = postedData.term_image;
				}
			}

			$.post(
				woocommerce_admin_meta_boxes.ajax_url,
				data,
				function ( response ) {
					if ( response.error ) {
						// Error.
						window.alert( response.error );
					} else if ( response.slug ) {
						// Success.
						const select = wrapper.querySelector(
							'select.attribute_values'
						);
						if ( select ) {
							const option = document.createElement( 'option' );
							option.value = String( response.term_id );
							option.selected = true;
							option.textContent = response.name;
							select.appendChild( option );

							// Trigger change event natively.
							const changeEvent = new Event( 'change', {
								bubbles: true,
							} );
							select.dispatchEvent( changeEvent );
						}
					}

					$( '.product_attributes' ).unblock();
					currentAttributeTermCreationContext = null;
				}
			);
		}
	);

	// Add a new attribute (via ajax).
	$( '.product_attributes' ).on(
		'click',
		'button.add_new_attribute',
		function ( event ) {
			// Prevent form submission but allow event propagation.
			event.preventDefault();

			$( '.product_attributes' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6,
				},
			} );

			const wrapper = this.closest( '.woocommerce_attribute' );
			const attribute = wrapper ? wrapper.dataset.taxonomy : '';
			const isVisualAttribute =
				this.dataset.isVisualAttribute === 'yes';

			currentAttributeTermCreationContext = {
				wrapper,
				attribute,
				isVisualAttribute,
			};

			$( this ).WCBackboneModal( {
				template: 'wc-modal-add-attribute-term',
				variable: {
					isVisualAttribute,
				},
			} );
		}
	);

	$( document.body ).on(
		'wc_backbone_modal_before_remove',
		function ( event, target, postedData, submitButtonCalled ) {
			if ( 'wc-modal-add-attribute-term' !== target ) {
				return;
			}

			if ( submitButtonCalled ) {
				return;
			}

			$( '.product_attributes' ).unblock();
			currentAttributeTermCreationContext = null;
		}
	);

	// Save attributes and update variations.
	$( '.save_attributes' ).on( 'click', function ( event ) {
		if ( $( this ).hasClass( 'disabled' ) ) {
			event.preventDefault();
			return;
		}
		$( '.product_attributes' ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		} );

		var original_data = $( '.product_attributes' ).find(
			'input, select, textarea'
		);
		var data = {
			post_id: woocommerce_admin_meta_boxes.post_id,
			product_type: $( '#product-type' ).val(),
			data: original_data.serialize(),
			action: 'woocommerce_save_attributes',
			security: woocommerce_admin_meta_boxes.save_attributes_nonce,
		};

		$.post(
			woocommerce_admin_meta_boxes.ajax_url,
			data,
			function ( response ) {
				if ( response.error ) {
					// Error.
					window.alert( response.error );
				} else if ( response.data ) {
					// Success.
					$( '.product_attributes' ).html( response.data.html );
					$( '.product_attributes' ).unblock();

					// Hide the 'Used for variations' checkbox if not viewing a variable product
					show_and_hide_panels();

					// Make sure the dropdown is not disabled for empty value attributes.
					$( 'select.attribute_taxonomy' )
						.find( 'option' )
						.prop( 'disabled', false );

					var newSelectedAttributes = [];
					$( '.product_attributes .woocommerce_attribute' ).each(
						function ( index, el ) {
							if (
								$( el ).css( 'display' ) !== 'none' &&
								$( el ).is( '.taxonomy' )
							) {
								newSelectedAttributes.push(
									$( el ).data( 'taxonomy' )
								);
								$( 'select.attribute_taxonomy' )
									.find(
										'option[value="' +
											$( el ).data( 'taxonomy' ) +
											'"]'
									)
									.prop( 'disabled', true );
							}
						}
					);
					selectedAttributes = newSelectedAttributes;
					$( 'select.wc-attribute-search' ).data(
						'disabled-items',
						newSelectedAttributes
					);

					// Reload variations panel.
					var this_page = window.location.toString();
					this_page = this_page.replace(
						'post-new.php?',
						'post.php?post=' +
							woocommerce_admin_meta_boxes.post_id +
							'&action=edit&'
					);

					$( '#variable_product_options' ).load(
						this_page + ' #variable_product_options_inner',
						function () {
							$( '#variable_product_options' ).trigger(
								'reload'
							);
						}
					);

					$( document.body ).trigger(
						'woocommerce_attributes_saved'
					);
				}
			}
		);
	} );

	// Go to attributes tab when clicking on link in variations message
	$( document.body ).on(
		'click',
		'#variable_product_options .add-attributes-message a[href="#product_attributes"]',
		function () {
			$(
				'#woocommerce-product-data .attribute_tab a[href="#product_attributes"]'
			).trigger( 'click' );
			return false;
		}
	);

	// Uploading files.
	var downloadable_file_frame;
	var file_path_field;

	$( document.body ).on( 'click', '.upload_file_button', function ( event ) {
		var $el = $( this );

		file_path_field = $el.closest( 'tr' ).find( 'td.file_url input' );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( downloadable_file_frame ) {
			downloadable_file_frame.open();
			return;
		}

		var downloadable_file_states = [
			// Main states.
			new wp.media.controller.Library( {
				library: wp.media.query(),
				multiple: true,
				title: $el.data( 'choose' ),
				priority: 20,
				filterable: 'uploaded',
			} ),
		];

		// Create the media frame.
		downloadable_file_frame = wp.media.frames.downloadable_file = wp.media(
			{
				// Set the title of the modal.
				title: $el.data( 'choose' ),
				library: {
					type: '',
				},
				button: {
					text: $el.data( 'update' ),
				},
				multiple: true,
				states: downloadable_file_states,
			}
		);

		// When an image is selected, run a callback.
		downloadable_file_frame.on( 'select', function () {
			var file_path = '';
			var selection = downloadable_file_frame.state().get( 'selection' );

			selection.map( function ( attachment ) {
				attachment = attachment.toJSON();
				if ( attachment.url ) {
					file_path = attachment.url;
				}
			} );

			file_path_field.val( file_path ).trigger( 'change' );
		} );

		// Set post to 0 and set our custom type.
		downloadable_file_frame.on( 'ready', function () {
			downloadable_file_frame.uploader.options.uploader.params = {
				type: 'downloadable_product',
			};
		} );

		// Finally, open the modal.
		downloadable_file_frame.open();
	} );

	// Download ordering.
	$( '.downloadable_files tbody' ).sortable( {
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.sort',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
	} );

	// Unified product images manager.
	( function () {
		const $list = $( '#wc-product-images__list' );
		if ( ! $list.length ) {
			return;
		}

		const $input = $( '#wc_product_image_ids' );
		const $legacyFeaturedInput = $( '#_thumbnail_id' );
		const $legacyGalleryInput = $( '#product_image_gallery' );
		const $legacyGallery = $( '#product_images_container' ).find(
			'ul.product_images'
		);
		const $addSlot = $( '#wc-product-images__add-slot' );
		const $liveRegion = $( '#wc-product-images__live-region' );
		const $productImagesBox = $( '#woocommerce-product-images' );
		const tileTemplate = wp.template( 'wc-product-image-tile' );
		let mediaFrame;
		let syncedFeaturedId = $legacyFeaturedInput.val() || '';

		$productImagesBox
			.find( '> .postbox-header > .hndle' )
			.append(
				$productImagesBox.find( '> .inside > .woocommerce-help-tip' )
			);

		function announce( message ) {
			$liveRegion.text( '' );
			window.setTimeout( function () {
				$liveRegion.text( message );
			}, 10 );
		}

		function syncLegacyGallery( galleryIds ) {
			if ( ! $legacyGallery.length ) {
				return;
			}

			const $tiles = $list.children( '.wc-product-images__image' );
			$legacyGallery.empty();

			galleryIds.forEach( function ( attachmentId ) {
				const $tile = $tiles.filter( function () {
					return (
						String( $( this ).data( 'attachment-id' ) ) ===
						String( attachmentId )
					);
				} );
				const $img = $tile.find( 'img' ).first().clone();
				const $item = $( '<li />', {
					class: 'image',
					'data-attachment_id': attachmentId,
				} );

				if ( $img.length ) {
					$item.append( $img );
				}

				$item.append(
					$( '<ul />', { class: 'actions' } ).append(
						$( '<li />' ).append(
							$( '<a />', {
								href: '#',
								class: 'delete tips',
								'data-tip':
									woocommerce_admin_meta_boxes.i18n_remove_product_image,
								text: woocommerce_admin_meta_boxes.i18n_remove_product_image,
							} )
						)
					)
				);

				$legacyGallery.append( $item );
			} );
		}

		function syncLegacyFeatured( featuredId ) {
			if ( ! $legacyFeaturedInput.length ) {
				return;
			}

			const value = featuredId ? String( featuredId ) : '-1';

			$legacyFeaturedInput.val( value ).trigger( 'change' );

			if ( value === syncedFeaturedId ) {
				return;
			}

			syncedFeaturedId = value;

			if (
				wp.media.featuredImage &&
				typeof wp.media.featuredImage.set === 'function'
			) {
				wp.media.featuredImage.set( Number( value ) );
			}
		}

		function syncIds() {
			const ids = $list
				.children( '.wc-product-images__image' )
				.map( function () {
					return $( this ).data( 'attachment-id' );
				} )
				.get();
			const hasImages = ids.length > 0;
			const galleryIds = ids.slice( 1 );

			$input.val( ids.join( ',' ) );
			$legacyGalleryInput.val( galleryIds.join( ',' ) ).trigger( 'change' );
			syncLegacyFeatured( ids[ 0 ] );
			syncLegacyGallery( galleryIds );
			$list.toggleClass(
				'wc-product-images__list--has-images',
				hasImages
			);
			$addSlot
				.toggleClass(
					'wc-product-images__add-slot--featured',
					! hasImages
				)
				.toggleClass(
					'wc-product-images__add-slot--gallery',
					hasImages
				);
			refreshFeaturedState();
		}

		function refreshFeaturedState() {
			$list.children( '.wc-product-images__image' ).each( function ( i ) {
				const $item = $( this );
				const isFeatured = i === 0;

				$item
					.toggleClass(
						'wc-product-images__image--featured',
						isFeatured
					)
					.toggleClass(
						'wc-product-images__image--gallery',
						! isFeatured
					);

				if ( isFeatured ) {
					maybeUpgradeImage( $item );
				} else {
					maybeDowngradeImage( $item );
				}
			} );
		}

		function maybeUpgradeImage( $item ) {
			const $img = $item.find( 'img' );
			const attachmentId = $item.data( 'attachment-id' );
			const currentSrc = $img.attr( 'src' ) || '';

			const attachment = wp.media.attachment( attachmentId );
			attachment.fetch().then( function () {
				const sizes = attachment.get( 'sizes' );
				const medium = sizes && sizes.medium;
				const full = sizes && sizes.full;
				const newSrc =
					( medium && medium.url ) ||
					( full && full.url ) ||
					currentSrc;
				if ( newSrc !== currentSrc ) {
					$img.attr( 'src', newSrc );
					$img.removeAttr( 'width' ).removeAttr( 'height' );
				}
			} );
		}

		function maybeDowngradeImage( $item ) {
			const $img = $item.find( 'img' );
			const attachmentId = $item.data( 'attachment-id' );
			const currentSrc = $img.attr( 'src' ) || '';

			const attachment = wp.media.attachment( attachmentId );
			attachment.fetch().then( function () {
				const sizes = attachment.get( 'sizes' );
				const thumb = sizes && sizes.thumbnail;
				if ( thumb && thumb.url && thumb.url !== currentSrc ) {
					$img.attr( 'src', thumb.url );
					$img.removeAttr( 'width' ).removeAttr( 'height' );
				}
			} );
		}

		function buildImageHtml( attachmentId, imgUrl, modifier ) {
			return tileTemplate( {
				attachmentId,
				imgUrl,
				modifier,
				removeLabel:
					woocommerce_admin_meta_boxes.i18n_remove_product_image,
			} );
		}

		function getExistingImageIds() {
			return $input.val()
				? $input.val().split( ',' ).map( Number ).filter( Boolean )
				: [];
		}

		function areImageIdsEqual( firstIds, secondIds ) {
			return (
				firstIds.length === secondIds.length &&
				firstIds.every( function ( id, index ) {
					return id === secondIds[ index ];
				} )
			);
		}

		function getCurrentImageSrc( attachmentId ) {
			return $list
				.children( '.wc-product-images__image' )
				.filter( function () {
					return (
						Number( $( this ).data( 'attachment-id' ) ) ===
						Number( attachmentId )
					);
				} )
				.find( 'img' )
				.first()
				.attr( 'src' );
		}

		function seedMediaFrameSelection() {
			const selection = mediaFrame.state().get( 'selection' );

			selection.reset();

			getExistingImageIds().forEach( function ( attachmentId ) {
				const attachment = wp.media.attachment( attachmentId );

				attachment.fetch();
				selection.add( attachment );
			} );
		}

		function pickImageUrl( attachment, isFeatured ) {
			if ( isFeatured ) {
				return (
					( attachment.sizes &&
						attachment.sizes.medium &&
						attachment.sizes.medium.url ) ||
					attachment.url
				);
			}

			return (
				( attachment.sizes &&
					attachment.sizes.thumbnail &&
					attachment.sizes.thumbnail.url ) ||
				attachment.url
			);
		}

		function appendNewTile( attachment, isFeatured ) {
			const modifier = isFeatured ? 'featured' : 'gallery';
			const imgUrl =
				pickImageUrl( attachment, isFeatured ) ||
				getCurrentImageSrc( attachment.id );

			$addSlot.before(
				buildImageHtml( attachment.id, imgUrl, modifier )
			);
		}

		function getSelectedAttachments( selection ) {
			const selectedAttachments = [];
			const selectedIds = [];

			selection.each( function ( attachment ) {
				const attachmentData = attachment.toJSON();
				const attachmentId = Number( attachmentData.id );

				if (
					! attachmentId ||
					selectedIds.indexOf( attachmentId ) !== -1
				) {
					return;
				}

				attachmentData.id = attachmentId;
				attachmentData.url =
					attachmentData.url || getCurrentImageSrc( attachmentId );
				selectedIds.push( attachmentId );
				selectedAttachments.push( attachmentData );
			} );

			return {
				selectedAttachments,
				selectedIds,
			};
		}

		function rebuildTiles( selectedAttachments ) {
			$list.children( '.wc-product-images__image' ).remove();

			selectedAttachments.forEach( function ( attachment, index ) {
				appendNewTile( attachment, index === 0 );
			} );
		}

		function announceAdded( addedCount ) {
			announce(
				addedCount === 1
					? woocommerce_admin_meta_boxes.i18n_product_image_added
					: woocommerce_admin_meta_boxes.i18n_product_images_added
			);
		}

		// Sortable drag-and-drop.
		$list.sortable( {
			items: '> .wc-product-images__image',
			cursor: 'move',
			scrollSensitivity: 40,
			forcePlaceholderSize: false,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-product-images__placeholder',
			tolerance: 'pointer',
			start: function ( event, ui ) {
				ui.item.addClass( 'wc-product-images__image--dragging' );
				updateDragVisuals( ui.placeholder );
			},
			change: function ( event, ui ) {
				updateDragVisuals( ui.placeholder );
			},
			stop: function ( event, ui ) {
				ui.item.removeClass( 'wc-product-images__image--dragging' );
				ui.item.trigger( 'focus' );
			},
			update: function () {
				syncIds();
				refreshFeaturedState();
				announce(
					woocommerce_admin_meta_boxes.i18n_product_images_reordered
				);
			},
		} );

		function updateDragVisuals( $ph ) {
			const $children = $list
				.children()
				.not(
					'.wc-product-images__image--dragging, #wc-product-images__add-slot'
				);

			$children.each( function ( i ) {
				const $el = $( this );

				if ( $el.hasClass( 'wc-product-images__placeholder' ) ) {
					if ( i === 0 ) {
						$el.addClass(
							'wc-product-images__placeholder--featured'
						).removeClass(
							'wc-product-images__placeholder--gallery'
						);
					} else {
						$el.addClass(
							'wc-product-images__placeholder--gallery'
						).removeClass(
							'wc-product-images__placeholder--featured'
						);
					}
					return;
				}

				if ( i === 0 ) {
					$el
						.toggleClass(
							'wc-product-images__image--featured',
							true
						)
						.toggleClass(
							'wc-product-images__image--gallery',
							false
						);
				} else {
					$el
						.toggleClass(
							'wc-product-images__image--featured',
							false
						)
						.toggleClass(
							'wc-product-images__image--gallery',
							true
						);
				}
			} );
		}

		// Click or drag-start on image to focus its wrapper.
		$list.on( 'mousedown', '.wc-product-images__image', function () {
			$( this ).trigger( 'focus' );
		} );

		// Remove image.
		$list.on( 'click', '.wc-product-images__remove', function ( e ) {
			e.preventDefault();
			const $item = $( this ).closest( '.wc-product-images__image' );
			const $next = getNextFocusAfterRemoval( $item );
			$item.remove();
			syncIds();
			$next.trigger( 'focus' );
			announce(
				woocommerce_admin_meta_boxes.i18n_product_image_removed
			);
		} );

		// Open media library.
		function openMediaFrame() {
			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media.frames.product_gallery = wp.media( {
				title: woocommerce_admin_meta_boxes.i18n_add_product_images,
				button: {
					text: woocommerce_admin_meta_boxes.i18n_add_to_product,
				},
				states: [
					new wp.media.controller.Library( {
						title: woocommerce_admin_meta_boxes.i18n_add_product_images,
						filterable: 'all',
						library: wp.media.query( { type: 'image' } ),
						multiple: 'add',
					} ),
				],
			} );

			mediaFrame.on( 'open', seedMediaFrameSelection );

			mediaFrame.on( 'select', function () {
				const selection = mediaFrame.state().get( 'selection' );
				const previousIds = getExistingImageIds();
				const selected = getSelectedAttachments( selection );
				const addedCount = selected.selectedIds.filter( function ( id ) {
					return previousIds.indexOf( id ) === -1;
				} ).length;

				if ( areImageIdsEqual( previousIds, selected.selectedIds ) ) {
					return;
				}

				rebuildTiles( selected.selectedAttachments );
				syncIds();
				$addSlot.prev( '.wc-product-images__image' ).trigger( 'focus' );

				if ( addedCount > 0 ) {
					announceAdded( addedCount );
				}
			} );

			mediaFrame.open();
		}

		// Add-slot click opens media library.
		$addSlot.on( 'click', function () {
			openMediaFrame();
		} );

		// Add-slot keyboard support.
		$addSlot.on( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				openMediaFrame();
			}
		} );

		function getImages() {
			return $list.children( '.wc-product-images__image' );
		}

		function getPosition( $item ) {
			return getImages().index( $item ) + 1;
		}

		function announcePosition( $item ) {
			const position = getPosition( $item );

			announce(
				position === 1
					? woocommerce_admin_meta_boxes.i18n_product_image_now_featured
					: woocommerce_admin_meta_boxes.i18n_product_image_moved_to_position.replace(
							'%d',
							position
					  )
			);
		}

		function moveItemEarlier( $item ) {
			$item.prev( '.wc-product-images__image' ).before( $item );
			syncIds();
			$item.trigger( 'focus' );
			announcePosition( $item );
		}

		function moveItemLater( $item ) {
			$item.next( '.wc-product-images__image' ).after( $item );
			syncIds();
			$item.trigger( 'focus' );
			announcePosition( $item );
		}

		function getNextFocusAfterRemoval( $item ) {
			if ( $item.next( '.wc-product-images__image' ).length > 0 ) {
				return $item.next( '.wc-product-images__image' );
			}

			if ( $item.prev( '.wc-product-images__image' ).length > 0 ) {
				return $item.prev( '.wc-product-images__image' );
			}

			return $addSlot;
		}

		function removeFocusedItem( $item ) {
			const $next = getNextFocusAfterRemoval( $item );

			$item.remove();
			syncIds();
			$next.trigger( 'focus' );
			announce(
				woocommerce_admin_meta_boxes.i18n_product_image_removed
			);
		}

		$list.on( 'keydown', '.wc-product-images__image', function ( e ) {
			const $item = $( this );
			const $images = getImages();
			const index = $images.index( $item );

			if (
				( e.key === 'ArrowLeft' || e.key === 'ArrowUp' ) &&
				index > 0
			) {
				e.preventDefault();
				moveItemEarlier( $item );
				return;
			}

			if (
				( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) &&
				index < $images.length - 1
			) {
				e.preventDefault();
				moveItemLater( $item );
				return;
			}

			if ( e.key === 'Backspace' || e.key === 'Delete' ) {
				e.preventDefault();
				removeFocusedItem( $item );
			}
		} );

		// Initialize tooltip on the help tip in the product images meta box.
		$( '#woocommerce-product-images' )
			.find( '.woocommerce-help-tip' )
			.tipTip( {
				attribute: 'data-tip',
				fadeIn: 50,
				fadeOut: 50,
				delay: 200,
				keepAlive: true,
			} );
	} )();

	// Add a descriptive tooltip to the product description editor
	$( '#wp-content-media-buttons' )
		.append( '<span class="woocommerce-help-tip" tabindex="0"></span>' )
		.find( '.woocommerce-help-tip' )
		.attr( 'tabindex', '0' )
		.attr( 'for', 'content' )
		.attr(
			'aria-label',
			woocommerce_admin_meta_boxes.i18n_product_description_tip
		)
		.tipTip( {
			attribute: 'data-tip',
			content: woocommerce_admin_meta_boxes.i18n_product_description_tip,
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
			keepAlive: true,
		} );

	// Add a descriptive tooltip to the product short description meta box title
	$( '#postexcerpt > .postbox-header > .hndle' )
		.append( '<span class="woocommerce-help-tip"></span>' )
		.find( '.woocommerce-help-tip' )
		.attr( 'tabindex', '0' )
		.attr(
			'aria-label',
			woocommerce_admin_meta_boxes.i18n_product_short_description_tip
		)
		.tipTip( {
			attribute: 'data-tip',
			content:
				woocommerce_admin_meta_boxes.i18n_product_short_description_tip,
			fadeIn: 50,
			fadeOut: 50,
			delay: 200,
			keepAlive: true,
		} );
} );
