<?php
/**
 * Admin View: Product Export
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap woocommerce">
	<h1><?php esc_html_e( 'Import / Export Data', 'woocommerce' ); ?></h1>

	<form class="woocommerce-exporter">
		<span class="spinner is-active"></span>
		<h2><?php esc_html_e( 'Export products', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Generate and download a CSV file containing a list of all products.', 'woocommerce' ); ?></p>

		<table class="form-table woocommerce-exporter-options">
			<tbody>
				<tr>
					<th scope="row">
						<label for="woocommerce-exporter-types"><?php esc_html_e( 'Which product types should be exported?', 'woocommerce' ); ?></label>
					</th>
					<td>
						<select id="woocommerce-exporter-types" class="woocommerce-exporter-types wc-enhanced-select" style="width:100%;" multiple data-placeholder="<?php esc_attr_e( 'Export all', 'woocommerce' ); ?>">
							<?php
								foreach ( wc_get_product_types() as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
								}
							?>
							<option value="variation"><?php esc_html_e( 'Product variations', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="woocommerce-exporter-columns"><?php esc_html_e( 'Which columns should be exported?', 'woocommerce' ); ?></label>
					</th>
					<td>
						<select id="woocommerce-exporter-columns" class="woocommerce-exporter-columns wc-enhanced-select" style="width:100%;" multiple data-placeholder="<?php esc_attr_e( 'Export all data', 'woocommerce' ); ?>">
							<?php
								foreach ( $exporter->get_default_column_names() as $column_id => $column_name ) {
									echo '<option value="' . esc_attr( $column_id ) . '">' . esc_html( $column_name ) . '</option>';
								}
							?>
							<option value="downloads"><?php esc_html_e( 'Downloads', 'woocommerce' ); ?></option>
							<option value="attributes"><?php esc_html_e( 'Attributes', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="woocommerce-exporter-meta"><?php esc_html_e( 'Export meta data?', 'woocommerce' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="woocommerce-exporter-meta" value="1" />
						<label for="woocommerce-exporter-meta"><?php esc_html_e( 'Yes, export all meta data', 'woocommerce' ); ?></label>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="form-row form-row-submit">
			<input type="submit" class="woocommerce-exporter-button button button-primary" value="<?php esc_attr_e( 'Generate CSV', 'woocommerce' ); ?>" />
		</div>
		<div>
			<progress class="woocommerce-exporter-progress" max="100" value="0"></progress>
		</div>
	</form>
	<script type="text/javascript">
		;(function ( $, window, document ) {
			/**
			 * productExportForm handles the export process.
			 */
			var productExportForm = function( $form ) {
				this.$form = $form;
				this.xhr   = false;

				// Initial state.
				this.$form.find('.woocommerce-exporter-progress').val( 0 );

				// Methods.
				this.processStep = this.processStep.bind( this );

				// Events.
				$form.on( 'submit', { productExportForm: this }, this.onSubmit );
			};

			/**
			 * Handle export form submission.
			 */
			productExportForm.prototype.onSubmit = function( event ) {
				event.preventDefault();
				event.data.productExportForm.$form.addClass( 'woocommerce-exporter__exporting' );
				event.data.productExportForm.$form.find('.woocommerce-exporter-progress').val( 0 );
				event.data.productExportForm.$form.find('.woocommerce-exporter-button').prop( 'disabled', true );
				event.data.productExportForm.processStep( 1, $( this ).serialize(), '' );
			}

			/**
			 * Process the current export step.
			 */
			productExportForm.prototype.processStep = function( step, data, columns ) {
				var $this = this,
					selected_columns = $( '.woocommerce-exporter-columns' ).val(),
					export_meta      = $( '#woocommerce-exporter-meta:checked' ).length ? 1 : 0,
					export_types     = $( '.woocommerce-exporter-types' ).val();

				$.ajax( {
					type: 'POST',
					url: ajaxurl,
					data: {
						form             : data,
						action           : 'woocommerce_do_ajax_product_export',
						step             : step,
						columns          : columns,
						selected_columns : selected_columns,
						export_meta      : export_meta,
						export_types     : export_types
					},
					dataType: "json",
					success: function( response ) {
						if ( response.success ) {
							if ( 'done' === response.data.step ) {
								$this.$form.find('.woocommerce-exporter-progress').val( response.data.percentage );
								$this.$form.removeClass( 'woocommerce-exporter__exporting' );
								$this.$form.find('.woocommerce-exporter-button').prop( 'disabled', false );
								window.location = response.data.url;
							} else {
								$this.$form.find('.woocommerce-exporter-progress').val( response.data.percentage );
								$this.processStep( parseInt( response.data.step ), data, response.data.columns );
							}
						}


					}
				} ).fail( function( response ) {
					window.console.log( response );
				} );
			}

			/**
			 * Function to call productExportForm on jquery selector.
			 */
			$.fn.wc_product_export_form = function() {
				new productExportForm( this );
				return this;
			};

			$( '.woocommerce-exporter' ).wc_product_export_form();

		})( jQuery, window, document );
	</script>
</div>
