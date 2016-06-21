/* global wc_enhanced_select_params, shippingZonesLocalizeScript, ajaxurl */
( function( $, data, wp, ajaxurl ) {
	$( function() {
		var $table          = $( '.wc-shipping-zones' ),
			$tbody          = $( '.wc-shipping-zone-rows' ),
			$save_button    = $( '.wc-shipping-zone-save' ),
			$row_template   = wp.template( 'wc-shipping-zone-row' ),
			$blank_template = wp.template( 'wc-shipping-zone-row-blank' ),
			select2_args    = $.extend({
				minimumResultsForSearch: 10,
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				matcher: function( term, text, opt ) {
					return text.toUpperCase().indexOf( term.toUpperCase() ) >= 0 || opt.attr( 'alt' ).toUpperCase().indexOf( term.toUpperCase() ) >= 0;
				}
			}, getEnhancedSelectFormatString() ),

			// Backbone model
			ShippingZone       = Backbone.Model.extend({
				changes: {},
				logChanges: function( changedRows ) {
					var changes = this.changes || {};

					_.each( changedRows, function( row, id ) {
						changes[ id ] = _.extend( changes[ id ] || { zone_id : id }, row );
					} );

					this.changes = changes;
					this.trigger( 'change:zones' );
				},
				discardChanges: function( id ) {
					var changes      = this.changes || {},
						set_position = null,
						zones        = _.indexBy( this.get( 'zones' ), 'zone_id' );

					// Find current set position if it has moved since last save
					if ( changes[ id ] && changes[ id ].zone_order !== undefined ) {
						set_position = changes[ id ].zone_order;
					}

					// Delete all changes
					delete changes[ id ];

					// If the position was set, and this zone does exist in DB, set the position again so the changes are not lost.
					if ( set_position !== null && zones[ id ] && zones[ id ].zone_order !== set_position ) {
						changes[ id ] = _.extend( changes[ id ] || {}, { zone_id : id, zone_order : set_position } );
					}

					this.changes = changes;

					// No changes? Disable save button.
					if ( 0 === _.size( this.changes ) ) {
						shippingZoneView.clearUnloadConfirmation();
					}
				},
				save: function() {
					if ( _.size( this.changes ) ) {
						$.post( ajaxurl + ( ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=woocommerce_shipping_zones_save_changes', {
							wc_shipping_zones_nonce : data.wc_shipping_zones_nonce,
							changes                 : this.changes
						}, this.onSaveResponse, 'json' );
					} else {
						shippingZone.trigger( 'saved:zones' );
					}
				},
				onSaveResponse: function( response, textStatus ) {
					if ( 'success' === textStatus ) {
						if ( response.success ) {
							shippingZone.set( 'zones', response.data.zones );
							shippingZone.trigger( 'change:zones' );
							shippingZone.changes = {};
							shippingZone.trigger( 'saved:zones' );
						} else {
							window.alert( data.strings.save_failed );
						}
					}
				}
			} ),

			// Backbone view
			ShippingZoneView = Backbone.View.extend({
				rowTemplate: $row_template,
				initialize: function() {
					this.listenTo( this.model, 'change:zones', this.setUnloadConfirmation );
					this.listenTo( this.model, 'saved:zones', this.clearUnloadConfirmation );
					this.listenTo( this.model, 'saved:zones', this.render );
					$tbody.on( 'change', { view: this }, this.updateModelOnChange );
					$tbody.on( 'sortupdate', { view: this }, this.updateModelOnSort );
					$( window ).on( 'beforeunload', { view: this }, this.unloadConfirmation );
					$save_button.on( 'click', { view: this }, this.onSubmit );
					$( document.body ).on( 'click', '.add_shipping_method:not(.disabled)', { view: this }, this.onAddShippingMethod );
					$( document.body ).on( 'click', '.wc-shipping-zone-add', { view: this }, this.onAddNewRow );
					$( document.body ).on( 'wc_backbone_modal_response', this.onAddShippingMethodSubmitted );
					$( document.body ).on( 'change', '.wc-shipping-zone-method-selector select', this.onChangeShippingMethodSelector );
				},
				block: function() {
					$( this.el ).block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});
				},
				unblock: function() {
					$( this.el ).unblock();
				},
				render: function() {
					var zones = _.indexBy( this.model.get( 'zones' ), 'zone_id' ),
						view  = this;

					view.$el.empty();
					view.unblock();

					if ( _.size( zones ) ) {
						// Sort zones
						zones = _.sortBy( zones, function( zone ) {
							return parseInt( zone.zone_order, 10 );
						} );

						// Populate $tbody with the current zones
						$.each( zones, function( id, rowData ) {
							view.renderRow( rowData );
						} );
					} else {
						view.$el.append( $blank_template );
					}

					view.initRows();
				},
				renderRow: function( rowData ) {
					var view = this;
					view.$el.append( view.rowTemplate( rowData ) );
					view.initRow( rowData );
				},
				initRow: function( rowData ) {
					var view = this;
					var $tr = view.$el.find( 'tr[data-id="' + rowData.zone_id + '"]');

					// Select values in region select
					_.each( rowData.zone_locations, function( location ) {
						if ( 'string' === jQuery.type( location ) ) {
							$tr.find( 'option[value="' + location + '"]' ).prop( 'selected', true );
						} else {
							if ( 'postcode' === location.type ) {
								var postcode_field = $tr.find( '.wc-shipping-zone-postcodes :input' );

								if ( postcode_field.val() ) {
									postcode_field.val( postcode_field.val() + '\n' + location.code );
								} else {
									postcode_field.val( location.code );
								}
								$tr.find( '.wc-shipping-zone-postcodes' ).show();
								$tr.find( '.wc-shipping-zone-postcodes-toggle' ).hide();
							} else {
								$tr.find( 'option[value="' + location.type + ':' + location.code + '"]' ).prop( 'selected', true );
							}
						}
					} );

					if ( rowData.zone_postcodes ) {
						_.each( rowData.zone_postcodes, function( location ) {
							var postcode_field = $tr.find( '.wc-shipping-zone-postcodes :input' );

							if ( postcode_field.val() ) {
								postcode_field.val( postcode_field.val() + '\n' + location.code );
							} else {
								postcode_field.val( location.code );
							}
							$tr.find( '.wc-shipping-zone-postcodes' ).show();
							$tr.find( '.wc-shipping-zone-postcodes-toggle' ).hide();
						} );
					}

					// List shipping methods
					view.renderShippingMethods( rowData.zone_id, rowData.shipping_methods );

					// Make the row function
					$tr.find( '.view' ).show();
					$tr.find( '.edit' ).hide();
					$tr.find( '.wc-shipping-zone-edit' ).on( 'click', { view: this }, this.onEditRow );
					$tr.find( '.wc-shipping-zone-cancel-edit' ).on( 'click', { view: this }, this.onCancelEditRow );
					$tr.find( '.wc-shipping-zone-delete' ).on( 'click', { view: this }, this.onDeleteRow );
					$tr.find( '.wc-shipping-zone-postcodes-toggle' ).on( 'click', { view: this }, this.onTogglePostcodes );

					// Editing?
					if ( true === rowData.editing ) {
						$tr.addClass( 'editing' );
						$tr.find( '.wc-shipping-zone-edit' ).trigger( 'click' );
					}
				},
				initRows: function() {
					// Stripe
					if ( 0 === ( $( 'tbody.wc-shipping-zone-rows tr' ).length % 2 ) ) {
						$table.find( 'tbody.wc-shipping-zone-rows' ).next( 'tbody' ).find( 'tr' ).addClass( 'odd' );
					} else {
						$table.find( 'tbody.wc-shipping-zone-rows' ).next( 'tbody' ).find( 'tr' ).removeClass( 'odd' );
					}

					// Tooltips
					$( '#tiptip_holder' ).removeAttr( 'style' );
					$( '#tiptip_arrow' ).removeAttr( 'style' );
					$( '.tips' ).tipTip({ 'attribute': 'data-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50 });
				},
				renderShippingMethods: function( zone_id, shipping_methods ) {
					var $tr          = $( '.wc-shipping-zones tr[data-id="' + zone_id + '"]');
					var $method_list = $tr.find('.wc-shipping-zone-methods ul');

					$method_list.find( '.wc-shipping-zone-method' ).remove();

					if ( _.size( shipping_methods ) ) {
						_.each( shipping_methods, function( shipping_method, instance_id ) {
							var class_name = 'method_disabled';

							if ( 'yes' === shipping_method.enabled ) {
								class_name = 'method_enabled';
							}

							$method_list.prepend( '<li class="wc-shipping-zone-method"><a href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id=' + instance_id + '" class="' + class_name + '">' + shipping_method.title + '</a></li>' );
						} );
					} else {
						$method_list.prepend( '<li class="wc-shipping-zone-method">' + data.strings.no_shipping_methods_offered + '</li>' );
					}
				},
				onSubmit: function( event ) {
					event.data.view.block();
					event.data.view.model.save();
					event.preventDefault();
				},
				onAddNewRow: function( event ) {
					event.preventDefault();

					var view    = event.data.view,
						model   = view.model,
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						changes = {},
						size    = _.size( zones ),
						newRow  = _.extend( {}, data.default_zone, {
							zone_id  : 'new-' + size + '-' + Date.now(),
							editing  : true
						} );

					$( '.wc-shipping-zones-blank-state' ).closest( 'tr' ).remove();

					newRow.zone_order = 1 + _.max(
						_.pluck( zones, 'zone_order' ),
						function ( val ) {
							// Cast them all to integers, because strings compare funky. Sighhh.
							return parseInt( val, 10 );
						}
					);

					changes[ newRow.zone_id ] = newRow;

					model.logChanges( changes );
					view.renderRow( newRow );
					view.initRows();
				},
				onTogglePostcodes: function( event ) {
					event.preventDefault();
					var $tr = $( this ).closest( 'tr');
					$tr.find( '.wc-shipping-zone-postcodes' ).show();
					$tr.find( '.wc-shipping-zone-postcodes-toggle' ).hide();
				},
				onEditRow: function( event ) {
					event.preventDefault();
					event.data.view.model.trigger( 'change:zones' );
					$( this ).closest('tr').addClass( 'editing' );
					$( this ).closest('tr').find('.view').hide();
					$( this ).closest('tr').find('.edit').show();
					$( '.wc-shipping-zone-region-select:not(.enhanced)' ).select2( select2_args );
					$( '.wc-shipping-zone-region-select:not(.enhanced)' ).addClass('enhanced');

					var addShippingMethod = $( this ).closest('tr').find('.add_shipping_method');
					addShippingMethod.addClass( 'disabled' );
					addShippingMethod.tipTip({ 'attribute': 'data-disabled-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50 });
				},
				onCancelEditRow: function( event ) {
					var view    = event.data.view,
						model   = view.model,
						row     = $( this ).closest('tr'),
						zone_id = row.data('id'),
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' );

					event.preventDefault();
					model.discardChanges( zone_id );

					if ( zones[ zone_id ] ) {
						zones[ zone_id ].editing = false;
						row.after( view.rowTemplate( zones[ zone_id ] ) );
						view.initRow( zones[ zone_id ] );
					}

					row.remove();
					view.initRows();
				},
				onDeleteRow: function( event ) {
					var view    = event.data.view,
						model   = view.model,
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						changes = {},
						row     = $( this ).closest('tr'),
						zone_id = $( this ).closest('tr').data('id');

					event.preventDefault();

					if ( zones[ zone_id ] ) {
						delete zones[ zone_id ];
						changes[ zone_id ] = _.extend( changes[ zone_id ] || {}, { deleted : 'deleted' } );
						model.set( 'zones', zones );
						model.logChanges( changes );
					}

					row.remove();
					view.initRows();
				},
				setUnloadConfirmation: function() {
					this.needsUnloadConfirm = true;
					$save_button.prop( 'disabled', false );
				},
				clearUnloadConfirmation: function() {
					this.needsUnloadConfirm = false;
					$save_button.prop( 'disabled', true );
				},
				unloadConfirmation: function( event ) {
					if ( event.data.view.needsUnloadConfirm ) {
						event.returnValue = data.strings.unload_confirmation_msg;
						window.event.returnValue = data.strings.unload_confirmation_msg;
						return data.strings.unload_confirmation_msg;
					}
				},
				updateModelOnChange: function( event ) {
					var model     = event.data.view.model,
						$target   = $( event.target ),
						zone_id   = $target.closest( 'tr' ).data( 'id' ),
						attribute = $target.data( 'attribute' ),
						value     = $target.val(),
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						changes = {};

					if ( ! zones[ zone_id ] || zones[ zone_id ][ attribute ] !== value ) {
						changes[ zone_id ] = {};
						changes[ zone_id ][ attribute ] = value;
					}

					model.logChanges( changes );
				},
				updateModelOnSort: function( event ) {
					var view    = event.data.view,
						model   = view.model,
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						rows    = $( 'tbody.wc-shipping-zone-rows tr' ),
						changes = {};

					// Update sorted row position
					_.each( rows, function( row ) {
						var zone_id = $( row ).data( 'id' ),
							old_position = null,
							new_position = parseInt( $( row ).index(), 10 );

						if ( zones[ zone_id ] ) {
							old_position = parseInt( zones[ zone_id ].zone_order, 10 );
						}

						if ( old_position !== new_position ) {
							changes[ zone_id ] = _.extend( changes[ zone_id ] || {}, { zone_order : new_position } );
						}
					} );

					if ( _.size( changes ) ) {
						model.logChanges( changes );
					}
				},
				onAddShippingMethod: function( event ) {
					var zone_id = $( this ).closest('tr').data('id');

					event.preventDefault();

					$( this ).WCBackboneModal({
						template : 'wc-modal-add-shipping-method',
						variable : {
							zone_id : zone_id
						}
					});

					$( '.wc-shipping-zone-method-selector select' ).change();
				},
				onAddShippingMethodSubmitted: function( event, target, posted_data ) {
					if ( 'wc-modal-add-shipping-method' === target ) {
						shippingZoneView.block();

						// Add method to zone via ajax call
						$.post( ajaxurl + ( ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=woocommerce_shipping_zone_add_method', {
							wc_shipping_zones_nonce : data.wc_shipping_zones_nonce,
							method_id               : posted_data.add_method_id,
							zone_id                 : posted_data.zone_id
						}, function( response, textStatus ) {
							if ( 'success' === textStatus && response.success ) {
								// Method was added. Render methods.
								shippingZoneView.renderShippingMethods( posted_data.zone_id, response.data.methods );
							}
							shippingZoneView.unblock();
						}, 'json' );
					}
				},
				onChangeShippingMethodSelector: function() {
					var description = $( this ).find( 'option:selected' ).data( 'description' );
					$( this ).parent().find( '.wc-shipping-zone-method-description' ).remove();
					$( this ).after( '<p class="wc-shipping-zone-method-description">' + description + '</p>' );
					$( this ).closest( 'article' ).height( $( this ).parent().height() );
				}
			} ),
			shippingZone = new ShippingZone({
				zones: data.zones
			} ),
			shippingZoneView = new ShippingZoneView({
				model:    shippingZone,
				el:       $tbody
			} );

		shippingZoneView.render();

		$tbody.sortable({
			items: 'tr',
			cursor: 'move',
			axis: 'y',
			handle: 'td.wc-shipping-zone-sort',
			scrollSensitivity: 40
		});

		function getEnhancedSelectFormatString() {
			var formatString = {
				formatMatches: function( matches ) {
					if ( 1 === matches ) {
						return wc_enhanced_select_params.i18n_matches_1;
					}
					return wc_enhanced_select_params.i18n_matches_n.replace( '%qty%', matches );
				},
				formatNoMatches: function() {
					return wc_enhanced_select_params.i18n_no_matches;
				},
				formatAjaxError: function() {
					return wc_enhanced_select_params.i18n_ajax_error;
				},
				formatInputTooShort: function( input, min ) {
					var number = min - input.length;

					if ( 1 === number ) {
						return wc_enhanced_select_params.i18n_input_too_short_1;
					}

					return wc_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', number );
				},
				formatInputTooLong: function( input, max ) {
					var number = input.length - max;

					if ( 1 === number ) {
						return wc_enhanced_select_params.i18n_input_too_long_1;
					}

					return wc_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', number );
				},
				formatSelectionTooBig: function( limit ) {
					if ( 1 === limit ) {
						return wc_enhanced_select_params.i18n_selection_too_long_1;
					}

					return wc_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', limit );
				},
				formatLoadMore: function() {
					return wc_enhanced_select_params.i18n_load_more;
				},
				formatSearching: function() {
					return wc_enhanced_select_params.i18n_searching;
				}
			};

			return formatString;
		}
	});
})( jQuery, shippingZonesLocalizeScript, wp, ajaxurl );
