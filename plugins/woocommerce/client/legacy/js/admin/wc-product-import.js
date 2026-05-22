/*global ajaxurl, wc_product_import_params */
;(function ( $, window ) {

	/**
	 * productImportForm handles the import process.
	 */
	var productImportForm = function( $form ) {
		this.$form              = $form;
		this.xhr                = false;
		this.mapping            = wc_product_import_params.mapping;
		this.position           = 0;
		this.file               = wc_product_import_params.file;
		this.update_existing    = wc_product_import_params.update_existing;
		this.delimiter          = wc_product_import_params.delimiter;
		this.security           = wc_product_import_params.import_nonce;
		this.character_encoding = wc_product_import_params.character_encoding;

		// Number of import successes/failures.
		this.imported = 0;
		this.imported_variations = 0;
		this.failed   = 0;
		this.updated  = 0;
		this.skipped  = 0;
		this.retries = 0;

		// Initial state.
		this.$form.find('.woocommerce-importer-progress').val( 0 );

		var $this = this;
		this.$form.find('.woocommerce-importer__retry').click(function(){
			// Let's give this another try
			$this.$form.find('.spinner').addClass('is-active');
			$this.$form.find('woocommerce-importer__error').attr("aria-hidden", "true").addClass("hidden");
			$this.run_import();
		});

		this.run_import = this.run_import.bind( this );
		this.failed_import = this.failed_import.bind( this );

		// Start importing.
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
				action            : 'woocommerce_do_ajax_product_import',
				position          : $this.position,
				mapping           : $this.mapping,
				file              : $this.file,
				update_existing   : $this.update_existing,
				delimiter         : $this.delimiter,
				security          : $this.security,
				character_encoding: $this.character_encoding
			},
			dataType: 'json',
			success: function( response ) {
				if ( response.success ) {
					$this.position  = response.data.position;
					$this.imported += response.data.imported;
					$this.imported_variations += response.data.imported_variations;
					$this.failed   += response.data.failed;
					$this.updated  += response.data.updated;
					$this.skipped  += response.data.skipped;
					$this.retries = 0;
					$this.$form.find('.woocommerce-importer-progress').val( response.data.percentage );

					if ( 'done' === response.data.position ) {
						var file_name = wc_product_import_params.file.split( '/' ).pop();
						window.location = response.data.url +
							'&products-imported=' +
							parseInt( $this.imported, 10 ) +
							'&products-imported-variations=' +
							parseInt( $this.imported_variations, 10 ) +
							'&products-failed=' +
							parseInt( $this.failed, 10 ) +
							'&products-updated=' +
							parseInt( $this.updated, 10 ) +
							'&products-skipped=' +
							parseInt( $this.skipped, 10 ) +
							'&file-name=' +
							file_name;
					} else {
						$this.run_import();
					}
				} else {
					$this.failed_import();
				}
			}
		} ).fail( function( response ) {
			$this.failed_import();
		} );
	};

	/**
	 * Function to handle a failure response from the server
	 */
	$.fn.failed_import = function() {
		this.retries += 1;
		if(this.retries > 3) {
			// After 3 tries, there is likely a further problem beyond a temporary issue (i.e cloudflare)
			this.$form.find('.spinner').removeClass('is-active');
			this.$form.find('woocommerce-importer__error').removeAttr("aria-hidden").removeClass("hidden");
		} else {
			// Hold off for a couple seconds while the server may recover
			setTimeout(function(){
				this.run_import();
			}, 2500);
		}
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
