/* global htmlSettingsTaxLocalizeScript */
/**
 * Used by woocommerce/includes/admin/settings/views/html-settings-tax.php
 */

(function($, data, wp){
	$(function() {

		var rowTemplate        = wp.template( 'wc-tax-table-row' ),
			paginationTemplate = wp.template( 'wc-tax-table-pagination' ),
			$table             = $( '.wc_tax_rates' ),
			$tbody             = $( '#rates' ),
			$pagination        = $( '#rates-pagination' ),
			WCTaxTableModelConstructor = Backbone.Model.extend( {} ),
			WCTaxTableViewConstructor  = Backbone.View.extend({
				rowTemplate : rowTemplate,
				per_page    : data.limit,
				page        : data.page,
				render      : function() {
					var rates       = this.model.get( 'rates' ),
						qty_rates   = rates.length,
						qty_pages   = Math.ceil( qty_rates / this.per_page ),
						first_index = this.per_page * ( this.page - 1),
						last_index  = this.per_page * this.page,
						paged_rates = rates.slice( first_index, last_index ),
						view        = this;

					// Blank out the contents.
					this.$el.empty();

					// Populate $tbody with the current page of results.
					$.each( paged_rates, function ( id, rowData ) {
						view.$el.append( view.rowTemplate( rowData ) );
					});

					// Initialize autocomplete for countries.
					this.$el.find( 'td.country input' ).autocomplete({
						source: data.countries,
						minLength: 3
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
				initialize : function() {
					this.qty_pages = Math.ceil( this.model.get( 'rates' ).length / this.per_page );
					this.listenTo( this.model, 'change', this.setUnloadConfirmation );
					this.listenTo( this.model, 'saved', this.clearUnloadConfirmation );
					window.addEventListener( 'beforeunload', this.unloadConfirmation );
				},
				setUnloadConfirmation : function() {
					this.needsUnloadConfirm = true;
				},
				clearUnloadConfirmation : function() {
					this.needsUnloadConfirm = false;
				},
				unloadConfirmation : function(e) {
					if ( this.needsUnloadConfirm ) {
						e.returnValue = data.strings.unload_confirmation_msg;
						window.event.returnValue = data.strings.unload_confirmation_msg;
						return data.strings.unload_confirmation_msg;
					}
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
		 * Handle clicks on the pagination links.
		 *
		 * Abstracting it out here instead of re-running it after each render.
		 */
		$pagination.on( 'click', 'a', function(event){
			event.preventDefault();
			WCTaxTableInstance.page = WCTaxTableInstance.sanitizePage( $( event.currentTarget ).data('goto') );
			WCTaxTableInstance.render();
		} );
		$pagination.on( 'change', 'input', function(event) {
			WCTaxTableInstance.page = WCTaxTableInstance.sanitizePage( $( event.currentTarget ).val() );
			WCTaxTableInstance.render();
		} );

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
			var size = $tbody.find('tr').length;
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

	});
})(jQuery, htmlSettingsTaxLocalizeScript, wp);
