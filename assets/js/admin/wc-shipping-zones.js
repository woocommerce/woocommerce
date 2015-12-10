/* global shippingZonesLocalizeScript, ajaxurl */
( function( $, data, wp, ajaxurl ) {
	$( function() {

        var $table             = $( '.wc_shipping_zones' ),
            $tbody             = $( '.wc-shipping-zone-rows' ),
            $save_button       = $( 'input[name="save"]' ),

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
				save: function() {
					if ( _.size( this.changes ) ) {
						$.post( ajaxurl + '?action=woocommerce_shipping_zones_save_changes', {
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
							alert( data.strings.save_failed );
						}
					}
				}
			} ),

            // Backbone view
			ShippingZoneView = Backbone.View.extend({
				rowTemplate: wp.template( 'wc-shipping-zone-row' ),
				initialize: function() {
					this.listenTo( this.model, 'change:zones', this.setUnloadConfirmation );
					this.listenTo( this.model, 'saved:zones', this.clearUnloadConfirmation );
					$tbody.on( 'change', { view: this }, this.updateModelOnChange );
					$tbody.on( 'sortupdate', { view: this }, this.updateModelOnSort );
					$( window ).on( 'beforeunload', { view: this }, this.unloadConfirmation );
					$save_button.on( 'click', { view: this }, this.onSubmit );
					$save_button.attr( 'disabled','disabled' );
					$table.find( '.wc-shipping-zone-add' ).on( 'click', { view: this }, this.onAddNewRow );
				},
				render: function() {
					var zones       = _.indexBy( this.model.get( 'zones' ), 'zone_id' ),
						view        = this;

					// Blank out the contents.
					this.$el.empty();

					if ( _.size( zones ) ) {
						// Populate $tbody with the current zones
						$.each( zones, function( id, rowData ) {
							view.$el.append( view.rowTemplate( rowData ) );
						} );

                        // Make the rows functiothis.$el.find( '.wc-shipping-zone-delete' ).on( 'click', { view: this }, this.onDeleteRow );
						this.$el.find('.view').show();
						this.$el.find('.edit').hide();
						this.$el.find( '.wc-shipping-zone-edit' ).on( 'click', { view: this }, this.onEditRow );
                        this.$el.find( '.wc-shipping-zone-delete' ).on( 'click', { view: this }, this.onDeleteRow );

                        // Stripe
                        if ( _.size(zones) % 2 == 0 ) {
                            $table.find( 'tbody.wc-shipping-zone-rows').next('tbody').find('tr').addClass('odd');
                        } else {
                            $table.find( 'tbody.wc-shipping-zone-rows').next('tbody').find('tr').removeClass('odd');
                        }
                    }
				},
				onSubmit: function( event ) {
					event.data.view.model.save();
					event.data.view.render()
					event.preventDefault();
				},
				onAddNewRow: function( event ) {
					var view    = event.data.view,
						model   = view.model,
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						changes = {},
						size    = _.size( zones ),
						newRow  = _.extend( {}, data.default_zone, {
							zone_id: 'new-' + size + '-' + Date.now(),
							newRow:  true
						} );

					newRow.zone_order = 1 + _.max(
						_.pluck( zones, 'zone_order' ),
						function ( val ) {
							// Cast them all to integers, because strings compare funky. Sighhh.
							return parseInt( val, 10 );
						}
					);

					zones[ newRow.zone_id ]   = newRow;
					changes[ newRow.zone_id ] = newRow;

					model.set( 'zones', zones );
					model.logChanges( changes );

					view.render();

                    return false;
				},
				onEditRow: function( event ) {
					event.preventDefault();
					$( this ).closest('tr').find('.view, .wc-shipping-zone-edit').hide();
					$( this ).closest('tr').find('.edit').show();
					event.data.view.model.trigger( 'change:zones' );
				},
				onDeleteRow: function( event ) {
					var view    = event.data.view,
						model   = view.model,
						zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						changes = {},
						zone_id = $( this ).closest('tr').data('id');

					event.preventDefault();

					delete zones[ zone_id ];
					changes[ zone_id ] = _.extend( changes[ zone_id ] || {}, { deleted : 'deleted' } );
					model.set( 'zones', zones );
					model.logChanges( changes );
					view.render();
				},
				setUnloadConfirmation: function() {
					this.needsUnloadConfirm = true;
					$save_button.removeAttr( 'disabled' );
				},
				clearUnloadConfirmation: function() {
					this.needsUnloadConfirm = false;
					$save_button.attr( 'disabled', 'disabled' );
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
						value     = $target.val();

					/*if ( 'city' === attribute || 'postcode' === attribute ) {
						val = val.split( ';' );
						val = $.map( val, function( thing ) {
							return thing.trim();
						});
					}*/

					var zones   = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						changes = {};

					if ( zones[ zone_id ][ attribute ] !== value ) {
						changes[ zone_id ] = {};
						changes[ zone_id ][ attribute ] = value;
						zones[ zone_id ][ attribute ]   = value;
					}

					model.logChanges( changes );
				},
				updateModelOnSort: function( event, ui ) {
					var view         = event.data.view,
						model        = view.model,
						$tr          = ui.item,
						zone_id  = $tr.data( 'id' ),
						zones        = _.indexBy( model.get( 'zones' ), 'zone_id' ),
						old_position = zones[ zone_id ].zone_order,
						new_position = $tr.index() + ( ( view.page - 1 ) * view.per_page ),
						which_way    = ( new_position > old_position ) ? 'higher' : 'lower',
						changes      = {},
						zones_to_reorder, reordered_zones;

					zones_to_reorder = _.filter( zones, function( rate ) {
						var order  = parseInt( rate.zone_order, 10 ),
							limits = [ old_position, new_position ];

						if ( parseInt( rate.zone_id, 10 ) === parseInt( zone_id, 10 ) ) {
							return true;
						} else if ( order > _.min( limits ) && order < _.max( limits ) ) {
							return true;
						} else if ( 'higher' === which_way && order === _.max( limits ) ) {
							return true;
						} else if ( 'lower' === which_way && order === _.min( limits ) ) {
							return true;
						}
						return false;
					} );

					reordered_zones = _.map( zones_to_reorder, function( rate ) {
						var order = parseInt( rate.zone_order, 10 );

						if ( parseInt( rate.zone_id, 10 ) === parseInt( zone_id, 10 ) ) {
							rate.zone_order = new_position;
						} else if ( 'higher' === which_way ) {
							rate.zone_order = order - 1;
						} else if ( 'lower' === which_way ) {
							rate.zone_order = order + 1;
						}

						changes[ rate.zone_id ] = _.extend( changes[ rate.zone_id ] || {}, { zone_order : rate.zone_order } );

						return rate;
					} );

					if ( reordered_zones.length ) {
						model.logChanges( changes );
						view.render(); // temporary, probably should get yanked.
					}
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
	});
})( jQuery, shippingZonesLocalizeScript, wp, ajaxurl );
