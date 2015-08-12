/* global htmlSettingsTaxLocalizeScript */
/**
 * Used by woocommerce/includes/admin/settings/views/html-settings-tax.php
 */

(function($, data, wp){
	$(function() {

		if ( ! String.prototype.trim ) {
			String.prototype.trim = function () {
				return this.replace( /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '' );
			};
		}

		var rowTemplate        = wp.template( 'wc-tax-table-row' ),
			rowTemplateEmpty   = wp.template( 'wc-tax-table-row-empty' ),
			paginationTemplate = wp.template( 'wc-tax-table-pagination' ),
			$table             = $( '.wc_tax_rates' ),
			$tbody             = $( '#rates' ),
			$p_unsaved_msg     = $( '#unsaved-changes' ),
			$pagination        = $( '#rates-pagination' ),
			$search_field      = $( '#rates-search .wc-tax-rates-search-field' ),
			WCTaxTableModelConstructor = Backbone.Model.extend({
				changes : {},
				setRateAttribute : function( rateID, attribute, value ) {
					var rates = this.get( 'rates' );

					if ( rates[ rateID ][ attribute ] !== value ) {
						rates[ rateID ][ attribute ] = value;
						this.set( 'rates', rates );
						this.trigger( 'change:rates' ); // Why is this necessary?  Shouldn't the previous line trigger it?

						// Store it in a changes array to potentially simplify saving?
						this.changes[ rateID ] = this.changes[ rateID ] || {};
						this.changes[ rateID ][ attribute ] = value;
					}
				},
				getFilteredRates : function() {
					var rates  = this.get( 'rates' ),
						search = $search_field.val().toLowerCase();

					if ( search.length ) {
						rates = _.filter( rates, function( rate ) {
							var search_text = _.toArray( rate ).join( ' ' ).toLowerCase();
							return ( -1 !== search_text.indexOf( search ) );
						} );
					}

					return rates;
				}
			} ),
			WCTaxTableViewConstructor  = Backbone.View.extend({
				rowTemplate : rowTemplate,
				per_page    : data.limit,
				page        : data.page,
				render      : function() {
					var rates       = this.model.getFilteredRates(),
						qty_rates   = _.size( rates ),
						qty_pages   = Math.ceil( qty_rates / this.per_page ),
						first_index = this.per_page * ( this.page - 1),
						last_index  = this.per_page * this.page,
						paged_rates = _.toArray( rates ).slice( first_index, last_index ),
						view        = this;

					// Blank out the contents.
					this.$el.empty();

					if ( paged_rates.length ) {
						// Populate $tbody with the current page of results.
						$.each( paged_rates, function ( id, rowData ) {
							view.$el.append( view.rowTemplate( rowData ) );
						} );
					} else {
						view.$el.append( rowTemplateEmpty() );
					}

					// Initialize autocomplete for countries.
					this.$el.find( 'td.country input' ).autocomplete({
						source: data.countries,
						minLength: 2
					});

					// Initialize autocomplete for states.
					this.$el.find( 'td.state input' ).autocomplete({
						source: data.states,
						minLength: 3
					});

					// Postcode and city don't have `name` values by default. They're only created if the contents changes, to save on database queries (I think)
					this.$el.find( 'td.postcode input, td.city input' ).change(function() {
						$(this).attr( 'name', $(this).data( 'name' ) );
					});

					if ( qty_pages > 1 ) {
						// We've now displayed our initial page, time to render the pagination box.
						$pagination.html( paginationTemplate( {
							qty_rates    : qty_rates,
							current_page : this.page,
							qty_pages    : qty_pages
						} ) );
					}
				},
				updateUrl : function() {
					if ( ! window.history.replaceState ) {
						return;
					}

					var url    = data.base_url,
						search = $search_field.val();

					if ( 1 < this.page ) {
						url += '&p=' + encodeURIComponent( this.page );
					}

					if ( search.length ) {
						url += '&s=' + encodeURIComponent( search );
					}

					window.history.replaceState( {}, '', url );
				},
				initialize : function() {
					this.qty_pages = Math.ceil( _.toArray( this.model.get( 'rates' ) ).length / this.per_page );
					this.page = this.sanitizePage( data.page );

					this.listenTo( this.model, 'change:rates', this.setUnloadConfirmation );
				//	this.listenTo( this.model, 'saved:rates', this.clearUnloadConfirmation );
					$tbody.on( 'change', { view : this }, this.updateModelOnChange );
					$search_field.on( 'keyup search', { view : this }, this.onSearchField );
					$pagination.on( 'click', 'a', { view : this }, this.onPageChange );
					$pagination.on( 'change', 'input', { view : this }, this.onPageChange );
					$(window).on( 'beforeunload', { view : this }, this.unloadConfirmation );
				},
				onSearchField : function( event ){
					event.data.view.updateUrl();
					event.data.view.render();
				},
				onPageChange : function( event ) {
					var $target  = $( event.currentTarget );

					event.preventDefault();
					event.data.view.page = $target.data('goto') ? $target.data('goto') : $target.val();
					event.data.view.render();
					event.data.view.updateUrl();
				},
				setUnloadConfirmation : function() {
					this.needsUnloadConfirm = true;
					$p_unsaved_msg.show();
				},
				clearUnloadConfirmation : function() {
					this.needsUnloadConfirm = false;
					$p_unsaved_msg.hide();
				},
				unloadConfirmation : function(event) {
					if ( event.data.view.needsUnloadConfirm ) {
						event.returnValue = data.strings.unload_confirmation_msg;
						window.event.returnValue = data.strings.unload_confirmation_msg;
						return data.strings.unload_confirmation_msg;
					}
				},
				updateModelOnChange : function( event ) {
					var model     = event.data.view.model,
						$target   = $( event.target ),
						id        = $target.closest('tr').data('id'),
						attribute = $target.data('attribute'),
						val       = $target.val();

					if ( 'city' === attribute || 'postcode' === attribute ) {
						val = val.split(';');
						val = $.map( val, function( thing ) {
							return thing.trim();
						});
					}

					if ( 'tax_rate_compound' === attribute || 'tax_rate_shipping' === attribute ) {
						if ( $target.is(':checked') ) {
							val = 1;
						} else {
							val = 0;
						}
					}

					model.setRateAttribute( id, attribute, val );
				},
				sanitizePage : function( page_num ) {
					page_num = parseInt( page_num, 10 );
					if ( page_num < 1 ) {
						page_num = 1;
					} else if ( page_num > this.qty_pages ) {
						page_num = this.qty_pages;
					}
					return page_num;
				}
			} ),
			WCTaxTableModelInstance = new WCTaxTableModelConstructor({
				rates : data.rates
			} ),
			WCTaxTableInstance = new WCTaxTableViewConstructor({
				model    : WCTaxTableModelInstance,
			//	page     : data.page,  // I'd prefer to have these two specified down here in the instance,
			//	per_page : data.limit, // but it doesn't seem to recognize them in render if I do. :\
				el       : '#rates'
			} );

		WCTaxTableInstance.render();

		/**
		 * Handle the exporting of tax rates, and build it off the global `data.rates` object.
		 *
		 * @todo: Have the `export` button save the current form and generate this from php, so there's no chance the current page is out of date.
		 */
		$table.find('.export').click(function() {
			var csv_data = 'data:application/csv;charset=utf-8,' + data.strings.csv_data_cols.join(',') + '\n';

			$.each( data.rates, function( id, rowData ) {
				var row = '';

				row += rowData.tax_rate_country  + ',';
				row += rowData.tax_rate_state    + ',';
				row += rowData.tax_rate_postcode ? rowData.tax_rate_postcode.join( '; ' ) : '' + ',';
				row += rowData.tax_rate_city     ? rowData.tax_rate_city.join( '; ' )     : '' + ',';
				row += rowData.tax_rate          + ',';
				row += rowData.tax_rate_name     + ',';
				row += rowData.tax_rate_priority + ',';
				row += rowData.tax_rate_compound + ',';
				row += rowData.tax_rate_shipping + ',';
				row += data.current_class;

				csv_data += row + '\n';
			});

			$(this).attr( 'href', encodeURI( csv_data ) );

			return true;
		});

		/**
		 * Add a new blank row to the table for the user to fill out and save.
		 */
		$table.find('.insert').click(function() {
			var size = Object.keys( WCTaxTableModelInstance.get( 'rates' ) ).length;
			var code = wp.template( 'wc-tax-table-row' )( {
				tax_rate_id       : 'new-' + size,
				tax_rate_priority : 1,
				tax_rate_shipping : 1,
				newRow            : true
			} );

			if ( $tbody.find('tr.current').length > 0 ) {
				$tbody.find('tr.current').after( code );
			} else {
				$tbody.append( code );
			}

			$( 'td.country input' ).autocomplete({
				source: data.countries,
				minLength: 3
			});

			$( 'td.state input' ).autocomplete({
				source: data.states,
				minLength: 3
			});

			return false;
		});

		/**
		 * Removals.
		 */
		$table.find('.remove_tax_rates').click(function() {
			if ( $tbody.find('tr.current').length > 0 ) {
				var $current = $tbody.find('tr.current');
				$current.find('input').val('');
				$current.find('input.remove_tax_rate').val('1');

				$current.each(function(){
					if ( $(this).is('.new') ) {
						$( this ).remove();
					} else {
						$( this ).hide();
					}
				});
			} else {
				window.alert( data.strings.no_rows_selected );
			}
			return false;
		});

	});
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
