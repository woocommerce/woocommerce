/*global inlineEditPost, woocommerce_admin, woocommerce_quick_edit */
jQuery(
	function( $ ) {
		function attributeDataHasTaxonomy( attributes, taxonomy ) {
			return Object.prototype.hasOwnProperty.call( attributes, taxonomy );
		}

		function setProductAttributeFieldVisibility( $select, isVisible ) {
			var $label = $select.closest( 'label' );

			if ( ! isVisible ) {
				if ( $select.hasClass( 'select2-hidden-accessible' ) ) {
					$select.selectWoo( 'close' );
				}

				clearProductAttributeDropdownSpacing( $select );
			}

			$select.prop( 'disabled', ! isVisible );
			$label
				.find( 'input.wc-product-attribute-taxonomy' )
				.prop( 'disabled', ! isVisible );
			$label.css( 'display', isVisible ? 'block' : 'none' );
		}

		function getVisibleProductAttributeTaxonomies( $quick_edit_row ) {
			var taxonomies = [];

			$quick_edit_row.find( 'select.wc-product-attribute-values' ).each(
				function() {
					var $select  = $( this ),
						taxonomy = $select.data( 'taxonomy' );

					if ( taxonomy && ! $select.prop( 'disabled' ) ) {
						taxonomies.push( taxonomy );
					}
				}
			);

			return taxonomies;
		}

		function updateProductAttributeAddSearch( $quick_edit_row ) {
			var visibleTaxonomies  = getVisibleProductAttributeTaxonomies(
					$quick_edit_row
				),
				hasHiddenTaxonomies = false,
				$addField           = $quick_edit_row.find(
					'.wc-product-attribute-add-field'
				),
				$addSelect          = $quick_edit_row.find(
					'select.wc-product-attribute-add'
				),
				$addTitle           = $addField.find( 'span.title' ),
				$addMessage         = $addField.find(
					'.wc-product-attribute-add-message'
				);

			if ( ! $addField.length ) {
				return;
			}

			$quick_edit_row.find( 'select.wc-product-attribute-values' ).each(
				function() {
					if ( $( this ).prop( 'disabled' ) ) {
						hasHiddenTaxonomies = true;
						return false;
					}
				}
			);

			$addSelect.data( 'disabled-items', visibleTaxonomies );
			$addField.css( 'display', 'block' );
			$addSelect.prop( 'disabled', ! hasHiddenTaxonomies );
			$addSelect
				.next( '.select2-container' )
				.css( 'display', hasHiddenTaxonomies ? '' : 'none' );
			$addTitle.prop( 'hidden', ! hasHiddenTaxonomies );
			$addMessage.prop( 'hidden', hasHiddenTaxonomies );

			if (
				! hasHiddenTaxonomies &&
				$addSelect.hasClass( 'select2-hidden-accessible' )
			) {
				$addSelect.selectWoo( 'close' );
				$addSelect.val( null ).trigger( 'change' );
			}
		}

		function showProductAttributeField( $quick_edit_row, taxonomy ) {
			var $select = $quick_edit_row
				.find( 'select.wc-product-attribute-values' )
				.filter(
					function() {
						return $( this ).data( 'taxonomy' ) === taxonomy;
					}
				)
				.first();

			if ( ! $select.length ) {
				return;
			}

			setProductAttributeFieldVisibility( $select, true );
			$select.trigger( 'change' );
			updateProductAttributeAddSearch( $quick_edit_row );
		}

		function populateProductAttributes( $quick_edit_row, $wc_inline_data ) {
			var attributes = $wc_inline_data.find( '.product_attributes' ).data( 'attributes' ) || {};

			$quick_edit_row.find( 'select.wc-product-attribute-values' ).each(
				function() {
					var $select      = $( this ),
						taxonomy     = $select.data( 'taxonomy' ),
						hasAttribute = attributeDataHasTaxonomy( attributes, taxonomy ),
						terms        = hasAttribute && attributes[ taxonomy ].terms ? attributes[ taxonomy ].terms : [];

					$select.empty();

					$.each(
						terms,
						function( index, term ) {
							$select.append( new Option( term.name, term.id, true, true ) );
						}
					);

					setProductAttributeFieldVisibility( $select, hasAttribute );
					$select.trigger( 'change' );
				}
			);

			updateProductAttributeAddSearch( $quick_edit_row );
		}

		function updateProductAttributeDropdownSpacing( $select ) {
			var $label    = $select.closest( 'label' ),
				$dropdown = $( '.select2-container--open .select2-dropdown' ).last(),
				$selection = $label.find( '.select2-selection' ).first(),
				dropdownRect,
				labelRect,
				selectionRect,
				dropdownOverlap,
				openLabelHeight;

			if (
				! $label.hasClass( 'wc-product-attribute-values-open' ) ||
				! $dropdown.length ||
				$dropdown.hasClass( 'select2-dropdown--above' )
			) {
				$label.css( { 'margin-bottom': '', 'min-height': '' } );
				return;
			}

			dropdownRect = $dropdown[0].getBoundingClientRect();
			labelRect = $label[0].getBoundingClientRect();
			selectionRect = (
				$selection.length ? $selection[0] : $label[0]
			).getBoundingClientRect();

			dropdownOverlap = Math.ceil( dropdownRect.bottom - selectionRect.bottom );
			openLabelHeight = Math.ceil( dropdownRect.bottom - labelRect.top + 6 );
			$label.css( 'min-height', 0 < dropdownOverlap ? openLabelHeight + 'px' : '' );
		}

		function scheduleProductAttributeDropdownSpacing( $select ) {
			$.each(
				[ 0, 50, 100, 250, 500 ],
				function( index, delay ) {
					window.setTimeout(
						function() {
							updateProductAttributeDropdownSpacing( $select );
						},
						delay
					);
				}
			);
		}

		function observeProductAttributeDropdownSpacing( $select, attempts ) {
			var observer  = $select.data( 'wcQuickEditAttributeDropdownObserver' ),
				$dropdown = $( '.select2-container--open .select2-dropdown' ).last();

			attempts = attempts || 0;

			if ( observer ) {
				observer.disconnect();
			}

			if ( ! window.MutationObserver ) {
				return;
			}

			if ( ! $dropdown.length ) {
				if ( attempts < 10 ) {
					window.setTimeout(
						function() {
							observeProductAttributeDropdownSpacing( $select, attempts + 1 );
						},
						50
					);
				}

				return;
			}

			observer = new window.MutationObserver(
				function() {
					updateProductAttributeDropdownSpacing( $select );
				}
			);

			observer.observe(
				$dropdown[0],
				{
					attributes: true,
					childList: true,
					characterData: true,
					subtree: true,
				}
			);

			$select.data( 'wcQuickEditAttributeDropdownObserver', observer );
		}

		function clearProductAttributeDropdownSpacing( $select ) {
			var observer = $select.data( 'wcQuickEditAttributeDropdownObserver' );

			if ( observer ) {
				observer.disconnect();
				$select.removeData( 'wcQuickEditAttributeDropdownObserver' );
			}

			$select
				.closest( 'label' )
				.removeClass( 'wc-product-attribute-values-open' )
				.css( { 'margin-bottom': '', 'min-height': '' } );
		}

		function initProductAttributes( postId, $wc_inline_data, attempts ) {
			var $quick_edit_row = $( '#edit-' + postId ),
				$attribute_selects,
				$attribute_add_select;

			attempts = attempts || 0;

			if ( ! $quick_edit_row.length || ! $quick_edit_row.is( ':visible' ) ) {
				if ( attempts < 10 ) {
					window.setTimeout(
						function() {
							initProductAttributes( postId, $wc_inline_data, attempts + 1 );
						},
						50
					);
				}

				return;
			}

			$attribute_selects = $quick_edit_row.find( 'select.wc-product-attribute-values' );
			$attribute_selects
				.addClass( 'wc-taxonomy-term-search' )
				.off( '.wcQuickEditAttributes' )
				.on(
					'select2:open.wcQuickEditAttributes',
					function() {
						var $select = $( this );

						$select.closest( 'label' ).addClass( 'wc-product-attribute-values-open' );
						scheduleProductAttributeDropdownSpacing( $select );
						observeProductAttributeDropdownSpacing( $select );
					}
				)
				.on(
					'select2:close.wcQuickEditAttributes',
					function() {
						clearProductAttributeDropdownSpacing( $( this ) );
					}
				);

			$attribute_add_select = $quick_edit_row.find(
				'select.wc-product-attribute-add'
			);
			$attribute_add_select
				.addClass( 'wc-attribute-search' )
				.off( '.wcQuickEditAttributeAdd' )
				.on(
					'select2:select.wcQuickEditAttributeAdd',
					function( event ) {
						var taxonomy =
							event &&
							event.params &&
							event.params.data &&
							event.params.data.id;

						if ( taxonomy ) {
							showProductAttributeField( $quick_edit_row, taxonomy );
						}

						$( this ).val( null ).trigger( 'change' );

						return false;
					}
				);

			$( document.body ).trigger( 'wc-enhanced-select-init' );
			populateProductAttributes( $quick_edit_row, $wc_inline_data );
		}

		$( '#the-list' ).on(
			'click',
			'.editinline',
			function() {

				inlineEditPost.revert();

				var post_id = $( this ).closest( 'tr' ).attr( 'id' );

				post_id = post_id.replace( 'post-', '' );

				var $wc_inline_data = $( '#woocommerce_inline_' + post_id );

				var sku        = $wc_inline_data.find( '.sku' ).text(),
				regular_price  = $wc_inline_data.find( '.regular_price' ).text(),
				sale_price     = $wc_inline_data.find( '.sale_price ' ).text(),
				weight         = $wc_inline_data.find( '.weight' ).text(),
				length         = $wc_inline_data.find( '.length' ).text(),
				width          = $wc_inline_data.find( '.width' ).text(),
				height         = $wc_inline_data.find( '.height' ).text(),
				shipping_class = $wc_inline_data.find( '.shipping_class' ).text(),
				visibility     = $wc_inline_data.find( '.visibility' ).text(),
				stock_status   = $wc_inline_data.find( '.stock_status' ).text(),
				stock          = $wc_inline_data.find( '.stock' ).text(),
				featured       = $wc_inline_data.find( '.featured' ).text(),
				manage_stock   = $wc_inline_data.find( '.manage_stock' ).text(),
				menu_order     = $wc_inline_data.find( '.menu_order' ).text(),
				tax_status     = $wc_inline_data.find( '.tax_status' ).text(),
				tax_class      = $wc_inline_data.find( '.tax_class' ).text(),
				backorders     = $wc_inline_data.find( '.backorders' ).text(),
				product_type   = $wc_inline_data.find( '.product_type' ).text();

				var formatted_regular_price = regular_price.replace( '.', woocommerce_admin.mon_decimal_point ),
				formatted_sale_price        = sale_price.replace( '.', woocommerce_admin.mon_decimal_point );

				var cogs_data = $wc_inline_data.find( '.cogs_value ' );
				if( cogs_data.length > 0 ) {
					var formatted_cogs_value = cogs_data.text().replace( '.', woocommerce_admin.mon_decimal_point );
					$( 'input[name="_cogs_value"]', '.inline-edit-row' ).val( formatted_cogs_value );
				}

				$( 'input[name="_sku"]', '.inline-edit-row' ).val( sku );
				$( 'input[name="_regular_price"]', '.inline-edit-row' ).val( formatted_regular_price );
				$( 'input[name="_sale_price"]', '.inline-edit-row' ).val( formatted_sale_price );
				$( 'input[name="_weight"]', '.inline-edit-row' ).val( weight );
				$( 'input[name="_length"]', '.inline-edit-row' ).val( length );
				$( 'input[name="_width"]', '.inline-edit-row' ).val( width );
				$( 'input[name="_height"]', '.inline-edit-row' ).val( height );
				initProductAttributes( post_id, $wc_inline_data );

				$( 'select[name="_shipping_class"] option:selected', '.inline-edit-row' ).attr( 'selected', false ).trigger( 'change' );
				$( 'select[name="_shipping_class"] option[value="' + shipping_class + '"]' ).attr( 'selected', 'selected' )
					.trigger( 'change' );

				$( 'input[name="_stock"]', '.inline-edit-row' ).val( stock );
				$( 'input[name="menu_order"]', '.inline-edit-row' ).val( menu_order );

				$(
					'select[name="_tax_status"] option, ' +
					'select[name="_tax_class"] option, ' +
					'select[name="_visibility"] option, ' +
					'select[name="_stock_status"] option, ' +
					'select[name="_backorders"] option'
				).prop( 'selected', false ).removeAttr( 'selected' );

				var is_variable_product = 'variable' === product_type;
				$( 'select[name="_stock_status"] ~ .wc-quick-edit-warning', '.inline-edit-row' ).toggle( is_variable_product );
				$( 'select[name="_stock_status"] option[value="' + (is_variable_product ? '' : stock_status) + '"]', '.inline-edit-row' )
					.attr( 'selected', 'selected' );

				$( 'select[name="_tax_status"] option[value="' + tax_status + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
				$( 'select[name="_tax_class"] option[value="' + tax_class + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
				$( 'select[name="_visibility"] option[value="' + visibility + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );
				$( 'select[name="_backorders"] option[value="' + backorders + '"]', '.inline-edit-row' ).attr( 'selected', 'selected' );

				if ( 'yes' === featured ) {
					$( 'input[name="_featured"]', '.inline-edit-row' ).prop( 'checked', true );
				} else {
					$( 'input[name="_featured"]', '.inline-edit-row' ).prop( 'checked', false );
				}

				// Conditional display.
				var product_is_virtual = $wc_inline_data.find( '.product_is_virtual' ).text();

				var product_supports_stock_status = 'external' !== product_type;
				var product_supports_stock_fields = 'external' !== product_type && 'grouped' !== product_type;

				$( '.stock_fields, .manage_stock_field, .stock_status_field, .backorder_field' ).show();

				if ( product_supports_stock_fields ) {
					if ( 'yes' === manage_stock ) {
						$( '.stock_qty_field, .backorder_field', '.inline-edit-row' ).show().removeAttr( 'style' );
						$( '.stock_status_field' ).hide();
						$( '.manage_stock_field input' ).prop( 'checked', true );
					} else {
						$( '.stock_qty_field, .backorder_field', '.inline-edit-row' ).hide();
						$( '.stock_status_field' ).show().removeAttr( 'style' );
						$( '.manage_stock_field input' ).prop( 'checked', false );
					}
				} else if ( product_supports_stock_status ) {
					$( '.stock_fields, .manage_stock_field, .backorder_field' ).hide();
				} else {
					$( '.stock_fields, .manage_stock_field, .stock_status_field, .backorder_field' ).hide();
				}

				if ( 'simple' === product_type || 'external' === product_type ) {
					$( '.price_fields', '.inline-edit-row' ).show().removeAttr( 'style' );
				} else {
					$( '.price_fields', '.inline-edit-row' ).hide();
				}

				if ( 'yes' === product_is_virtual ) {
					$( '.dimension_fields', '.inline-edit-row' ).hide();
				} else {
					$( '.dimension_fields', '.inline-edit-row' ).show().removeAttr( 'style' );
				}

				// Rename core strings.
				$( 'input[name="comment_status"]' ).parent().find( '.checkbox-title' ).text( woocommerce_quick_edit.strings.allow_reviews );
			}
		);

		$( '#the-list' ).on(
			'change',
			'.inline-edit-row input[name="_manage_stock"]',
			function() {

				if ( $( this ).is( ':checked' ) ) {
					$( '.stock_qty_field, .backorder_field', '.inline-edit-row' ).show().removeAttr( 'style' );
					$( '.stock_status_field' ).hide();
				} else {
					$( '.stock_qty_field, .backorder_field', '.inline-edit-row' ).hide();
					$( '.stock_status_field' ).show().removeAttr( 'style' );
				}

			}
		);

		$( '#wpbody' ).on(
			'click',
			'#doaction, #doaction2',
			function() {
				$( 'input.text', '.inline-edit-row' ).val( '' );
				$( '#woocommerce-fields' ).find( 'select' ).prop( 'selectedIndex', 0 );
				$( '#woocommerce-fields' ).find( 'select.wc-product-attribute-values' ).val( [] ).trigger( 'change' );
				$( '#woocommerce-fields-bulk' ).find( '.inline-edit-group .change-input' ).hide();
			}
		);

		$( '#wpbody' ).on(
			'change',
			'#woocommerce-fields-bulk .inline-edit-group .change_to',
			function() {

				if ( 0 < $( this ).val() ) {
					$( this ).closest( 'div' ).find( '.change-input' ).show();
				} else {
					$( this ).closest( 'div' ).find( '.change-input' ).hide();
				}

			}
		);

		$( '#wpbody' ).on(
			'click',
			'.trash-product',
			function() {
				return window.confirm( woocommerce_admin.i18n_delete_product_notice );
			}
		);
	}
);
