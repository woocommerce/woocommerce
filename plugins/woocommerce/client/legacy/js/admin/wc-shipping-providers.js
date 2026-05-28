/* global shippingProvidersLocalizeScript, ajaxurl */
( function( $, data, wp, ajaxurl ) {
	$( function() {
		if (
			! document.getElementById( 'tmpl-wc-shipping-provider-row' ) ||
			! document.getElementById( 'tmpl-wc-shipping-provider-row-blank' )
		) {
			return;
		}

		var $tbody          = $( '.wc-shipping-provider-rows' ),
			$row_template   = wp.template( 'wc-shipping-provider-row' ),
			$blank_template = wp.template( 'wc-shipping-provider-row-blank' ),

			// Backbone model
			ShippingProvider       = Backbone.Model.extend({
				save: function( changes ) {
					var self = this;
					$.ajax({
						url: ajaxurl + ( ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=woocommerce_shipping_providers_save_changes',
						type: 'POST',
						data: {
							wc_shipping_providers_nonce : data.wc_shipping_providers_nonce,
							changes: changes,
						},
						dataType: 'json'
					}).done( function( response ) {
						if ( response.success ) {
							if ( response.data.error ) {
								window.alert( response.data.error );
							}
							shippingProvider.set( 'providers', response.data.shipping_providers );
							shippingProvider.trigger( 'saved:providers' );
						} else if ( response.data ) {
							window.alert( response.data );
						} else {
							window.alert( data.strings.save_failed );
						}
					}).fail( function() {
						window.alert( data.strings.save_failed );
					}).always( function() {
						shippingProviderView.unblock();
					});
				}
			} ),

			// Backbone view
			ShippingProviderView = Backbone.View.extend({
				rowTemplate: $row_template,
				initialize: function() {
					this.listenTo( this.model, 'saved:providers', this.render );
					$( document.body ).on( 'click', '.wc-shipping-provider-add-new', { view: this }, this.configureNewShippingProvider );
					$( document.body ).on( 'wc_backbone_modal_response', { view: this }, this.onConfigureShippingProviderSubmitted );
					$( document.body ).on( 'wc_backbone_modal_loaded', { view: this }, this.onLoadBackboneModal );
					$( document.body ).on( 'wc_backbone_modal_validation', this.validateFormArguments );
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
					var providers = _.indexBy( this.model.get( 'providers' ), 'term_id' ),
						view      = this;

					this.$el.empty();
					this.unblock();

					if ( _.size( providers ) ) {
						providers = _.sortBy( providers, function( provider ) {
							return provider.name;
						} );

						$.each( providers, function( id, rowData ) {
							view.renderRow( rowData );
						} );
					} else {
						view.$el.append( $blank_template );
					}
				},
				renderRow: function( rowData ) {
					var view = this;
					view.$el.append( view.rowTemplate( rowData ) );
					view.initRow( rowData );
				},
				initRow: function( rowData ) {
					var view = this;
					var $tr = view.$el.find( 'tr[data-id="' + rowData.term_id + '"]');

					$tr.find( 'select' ).each( function() {
						var attribute = $( this ).data( 'attribute' );
						$( this ).find( 'option[value="' + rowData[ attribute ] + '"]' ).prop( 'selected', true );
					} );

					$tr.find( '.view' ).show();
					$tr.find( '.edit' ).hide();
					$tr.find( '.wc-shipping-provider-edit' ).on( 'click', { view: this }, this.onEditRow );
					$tr.find( '.wc-shipping-provider-delete' ).on( 'click', { view: this }, this.onDeleteRow );
				},
				configureNewShippingProvider: function( event ) {
					event.preventDefault();
					const term_id = 'new-1-' + Date.now();

					$( this ).WCBackboneModal({
						template : 'wc-shipping-provider-configure',
						variable : {
							term_id,
							action: 'create',
						},
						data : {
							term_id,
							action: 'create',
						}
					});
				},
				onConfigureShippingProviderSubmitted: function( event, target, posted_data ) {
					if ( target === 'wc-shipping-provider-configure' ) {
						const view = event.data.view;
						const model = view.model;
						const isNewRow = posted_data.term_id.includes( 'new-1-' );
						const rowData = Object.assign( {}, posted_data );

						if ( isNewRow ) {
							rowData.newRow = true;
						}

						view.block();

						model.save( {
							[ posted_data.term_id ]: rowData
						} );
					}
				},
				validateFormArguments: function( element, target, formData ) {
					const requiredFields = [ 'name' ];
					const formIsComplete = Object.keys( formData ).every( function( key ) {
						if ( requiredFields.indexOf( key ) === -1 ) {
							return true;
						}
						if ( Array.isArray( formData[ key ] ) ) {
							return formData[ key ].length && !!formData[ key ][ 0 ];
						}
						return !!formData[ key ];
					} );
					const createButton = document.getElementById( 'btn-ok' );
					createButton.disabled = ! formIsComplete;
					createButton.classList.toggle( 'disabled', ! formIsComplete );
				},
				onEditRow: function( event ) {
					const term_id = $( this ).closest('tr').data('id');
					const model =  event.data.view.model;
					const providers = _.indexBy( model.get( 'providers' ), 'term_id' );
					const rowData = providers[ term_id ];

					event.preventDefault();
					$( this ).WCBackboneModal({
						template : 'wc-shipping-provider-configure',
						variable: Object.assign( { action: 'edit' }, rowData ),
						data : Object.assign( { action: 'edit' }, rowData )
					});
				},
				onLoadBackboneModal: function( event, target ) {
					if ( 'wc-shipping-provider-configure' === target ) {
						const modalContent = $('.wc-backbone-modal-content');
						const term_id = modalContent.data('id');
						const model =  event.data.view.model;
						const providers = _.indexBy( model.get( 'providers' ), 'term_id' );
						const rowData = providers[ term_id ];

						// Make slug read-only when editing an existing provider.
						if ( rowData ) {
							$('.wc-backbone-modal-content').find( 'input[name="slug"]' ).prop( 'readonly', true );
						}

						if ( rowData ) {
							$('.wc-backbone-modal-content').find( 'select' ).each( function() {
								var attribute = $( this ).data( 'attribute' );
								$( this ).find( 'option[value="' + rowData[ attribute ] + '"]' ).prop( 'selected', true );
							} );
						}
					}
				},
				onDeleteRow: function( event ) {
					var view    = event.data.view,
						model   = view.model,
						term_id = $( this ).closest('tr').data('id');

					event.preventDefault();

					var confirmMsg = data.strings.delete_confirmation ||
						'Are you sure you want to delete this shipping provider?';
					if ( ! window.confirm( confirmMsg ) ) {
						return;
					}

					view.block();

					model.save( {
						[ term_id ]: {
							term_id,
							deleted: 'deleted',
						}
					} );
				},
			} ),
			shippingProvider = new ShippingProvider({
				providers: data.providers
			} ),
			shippingProviderView = new ShippingProviderView({
				model:    shippingProvider,
				el:       $tbody
			} );

		shippingProviderView.render();
	});
})( jQuery, shippingProvidersLocalizeScript, wp, ajaxurl );
