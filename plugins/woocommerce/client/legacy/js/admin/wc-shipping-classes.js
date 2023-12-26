/* global shippingClassesLocalizeScript, ajaxurl */
( function( $, data, wp, ajaxurl ) {
	$( function() {
		var $tbody          = $( '.wc-shipping-class-rows' ),
			$save_button    = $( '.wc-shipping-class-save' ),
			$row_template   = wp.template( 'wc-shipping-class-row' ),
			$blank_template = wp.template( 'wc-shipping-class-row-blank' ),

			// Backbone model
			ShippingClass       = Backbone.Model.extend({
				save: function( changes ) {
					$.post( ajaxurl + ( ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=woocommerce_shipping_classes_save_changes', {
						wc_shipping_classes_nonce : data.wc_shipping_classes_nonce,
						changes,
					}, this.onSaveResponse, 'json' );
				},
				onSaveResponse: function( response, textStatus ) {
					if ( 'success' === textStatus ) {
						if ( response.success ) {
							shippingClass.set( 'classes', response.data.shipping_classes );
							shippingClass.trigger( 'saved:classes' );
						} else if ( response.data ) {
							window.alert( response.data );
						} else {
							window.alert( data.strings.save_failed );
						}
					}
					shippingClassView.unblock();
				}
			} ),

			// Backbone view
			ShippingClassView = Backbone.View.extend({
				rowTemplate: $row_template,
				initialize: function() {
					this.listenTo( this.model, 'saved:classes', this.render );
					$( document.body ).on( 'click', '.wc-shipping-class-add-new', { view: this }, this.configureNewShippingClass );
					$( document.body ).on( 'wc_backbone_modal_response', { view: this }, this.onConfigureShippingClassSubmitted );
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
					var classes = _.indexBy( this.model.get( 'classes' ), 'term_id' ),
						view    = this;

					this.$el.empty();
					this.unblock();

					if ( _.size( classes ) ) {
						// Sort classes
						classes = _.sortBy( classes, function( shipping_class ) {
							return shipping_class.name;
						} );

						// Populate $tbody with the current classes
						$.each( classes, function( id, rowData ) {
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

					// Support select boxes
					$tr.find( 'select' ).each( function() {
						var attribute = $( this ).data( 'attribute' );
						$( this ).find( 'option[value="' + rowData[ attribute ] + '"]' ).prop( 'selected', true );
					} );

					// Make the rows function
					$tr.find( '.view' ).show();
					$tr.find( '.edit' ).hide();
					$tr.find( '.wc-shipping-class-edit' ).on( 'click', { view: this }, this.onEditRow );
					$tr.find( '.wc-shipping-class-delete' ).on( 'click', { view: this }, this.onDeleteRow );
				},
				configureNewShippingClass: function( event ) {
					event.preventDefault();
					const term_id = 'new-1-' + Date.now();

					$( this ).WCBackboneModal({
						template : 'wc-shipping-class-configure',
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
				onConfigureShippingClassSubmitted: function( event, target, posted_data ) {
					if ( target === 'wc-shipping-class-configure' ) {
						const view = event.data.view;
						const model = view.model;
						const isNewRow = posted_data.term_id.includes( 'new-1-' );
						const rowData = {
							...posted_data,
						};

						if ( isNewRow ) {
							rowData.newRow = true;
						}
						
						view.block();

						model.save( {
							[ posted_data.term_id ]: rowData
						} );
					}
				},
				validateFormArguments: function( element, target, data ) {
					const requiredFields = [ 'name', 'description' ];
					const formIsComplete = Object.keys( data ).every( key => {
						if ( ! requiredFields.includes( key ) ) {
							return true;
						}
						if ( Array.isArray( data[ key ] ) ) {
							return data[ key ].length && !!data[ key ][ 0 ];
						}
						return !!data[ key ];
					} );
					const createButton = document.getElementById( 'btn-ok' );
					createButton.disabled = ! formIsComplete;
					createButton.classList.toggle( 'disabled', ! formIsComplete );
				},
				onEditRow: function( event ) {
					const term_id = $( this ).closest('tr').data('id');
					const model =  event.data.view.model;
					const classes = _.indexBy( model.get( 'classes' ), 'term_id' );
					const rowData = classes[ term_id ];
					
					event.preventDefault();
					$( this ).WCBackboneModal({
						template : 'wc-shipping-class-configure',
						variable : {
							action: 'edit',
							...rowData
						},
						data : {
							action: 'edit',
							...rowData
						}
					});
				},
				onLoadBackboneModal: function( event, target ) {
					if ( 'wc-shipping-class-configure' === target ) {
						const modalContent = $('.wc-backbone-modal-content');
						const term_id = modalContent.data('id');
						const model =  event.data.view.model;
						const classes = _.indexBy( model.get( 'classes' ), 'term_id' );
						const rowData = classes[ term_id ];

						if ( rowData ) {
							// Support select boxes
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

					view.block();

					model.save( {
						[ term_id ]: {
							term_id,
							deleted: 'deleted',
						}
					} );
				},
			} ),
			shippingClass = new ShippingClass({
				classes: data.classes
			} ),
			shippingClassView = new ShippingClassView({
				model:    shippingClass,
				el:       $tbody
			} );

		shippingClassView.render();
	});
})( jQuery, shippingClassesLocalizeScript, wp, ajaxurl );
