/*global ajaxurl, wc_product_import_params */
;(function ( $, window ) {

	/**
	 * prodeuctImportForm handles the import process.
	 */
	var productImportForm = function( $form ) {
		this.$form = $form;
		this.xhr   = false;
		this.mapping = wc_product_import_params.mapping;
		this.position = 0;
		this.file = wc_product_import_params.file;
		this.security = wc_product_import_params.import_nonce;

		// Number of import successes/failures.
		this.imported = 0;
		this.failed = 0;

		// Number of lines to import in one batch.
		this.lines = 10;

		// Initial state.
		this.$form.find('.woocommerce-importer-progress').val( 0 );

		this.run_import = this.run_import.bind( this );

		//Start importing.
		this.run_import();
	};

	/**
	 * Run the import in batches until finished.
	 */
	productImportForm.prototype.run_import = function() {
		var $this = this;

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'woocommerce_do_ajax_product_import',
				position: $this.position,
				lines: $this.lines,
				mapping: $this.mapping,
				file: $this.file,
				security : $this.security
			},
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
					$this.position = response.data.position;
					$this.imported += response.data.imported;
					$this.failed += response.data.failed;
					$this.$form.find('.woocommerce-importer-progress').val( response.data.percentage );

					if ( 'done' === response.data.position ) {
						window.location = response.data.url + '&imported=' + parseInt( $this.imported, 10 ) + '&failed=' + parseInt( $this.failed, 10 );
					} else {
						$this.run_import();
					}
				}
			}
		} ).fail( function( response ) {
			window.console.log( response );
		} );
	};

	/**
	 * Function to call productImportForm on jQuery selector.
	 */
	$.fn.wc_product_importer = function() {
		new productImportForm( this );
		return this;
	};

	$( '.woocommerce-importer' ).wc_product_importer();

})( jQuery, window );
