<?php
/**
 * WooCommerce Tax Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Tax' ) ) :

/**
 * WC_Settings_Tax
 */
class WC_Settings_Tax extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'tax';
		$this->label = __( 'Tax', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Tax Options', 'woocommerce' ),
			'standard' => __( 'Standard Rates', 'woocommerce' )
		);

		// Get tax classes and display as links
		$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );

		if ( $tax_classes ) {
			foreach ( $tax_classes as $class ) {
				$sections[ sanitize_title( $class ) ] = sprintf( __( '%s Rates', 'woocommerce' ), $class );
			}
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$tax_classes     = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
		$classes_options = array();
		if ( $tax_classes ) {
			foreach ( $tax_classes as $class ) {
				$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
			}
		}

		$settings = apply_filters('woocommerce_tax_settings', array(

			array( 'title' => __( 'Tax Options', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'tax_options' ),

			array(
				'title'   => __( 'Enable Taxes', 'woocommerce' ),
				'desc'    => __( 'Enable taxes and tax calculations', 'woocommerce' ),
				'id'      => 'woocommerce_calc_taxes',
				'default' => 'no',
				'type'    => 'checkbox'
			),

			array(
				'title'    => __( 'Prices Entered With Tax', 'woocommerce' ),
				'id'       => 'woocommerce_prices_include_tax',
				'default'  => 'no',
				'type'     => 'radio',
				'desc_tip' =>  __( 'This option is important as it will affect how you input prices. Changing it will not update existing products.', 'woocommerce' ),
				'options'  => array(
					'yes' => __( 'Yes, I will enter prices inclusive of tax', 'woocommerce' ),
					'no'  => __( 'No, I will enter prices exclusive of tax', 'woocommerce' )
				),
			),

			array(
				'title'    => __( 'Calculate Tax Based On:', 'woocommerce' ),
				'id'       => 'woocommerce_tax_based_on',
				'desc_tip' =>  __( 'This option determines which address is used to calculate tax.', 'woocommerce' ),
				'default'  => 'shipping',
				'type'     => 'select',
				'options'  => array(
					'shipping' => __( 'Customer shipping address', 'woocommerce' ),
					'billing'  => __( 'Customer billing address', 'woocommerce' ),
					'base'     => __( 'Shop base address', 'woocommerce' )
				),
			),

			array(
				'title'    => __( 'Default Customer Address:', 'woocommerce' ),
				'id'       => 'woocommerce_default_customer_address',
				'desc_tip' =>  __( 'This option determines the customers default address (before they input their own).', 'woocommerce' ),
				'default'  => 'base',
				'type'     => 'select',
				'options'  => array(
					''     => __( 'No address', 'woocommerce' ),
					'base' => __( 'Shop base address', 'woocommerce' ),
				),
			),

			array(
				'title'    => __( 'Shipping Tax Class:', 'woocommerce' ),
				'desc'     => __( 'Optionally control which tax class shipping gets, or leave it so shipping tax is based on the cart items themselves.', 'woocommerce' ),
				'id'       => 'woocommerce_shipping_tax_class',
				'css'      => 'min-width:150px;',
				'default'  => 'title',
				'type'     => 'select',
				'options'  => array( '' => __( 'Shipping tax class based on cart items', 'woocommerce' ), 'standard' => __( 'Standard', 'woocommerce' ) ) + $classes_options,
				'desc_tip' =>  true,
			),

			array(
				'title'   => __( 'Rounding', 'woocommerce' ),
				'desc'    => __( 'Round tax at subtotal level, instead of rounding per line', 'woocommerce' ),
				'id'      => 'woocommerce_tax_round_at_subtotal',
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'title'   => __( 'Additional Tax Classes', 'woocommerce' ),
				'desc'    => __( 'List additional tax classes below (1 per line). This is in addition to the default <code>Standard Rate</code>. Tax classes can be assigned to products.', 'woocommerce' ),
				'id'      => 'woocommerce_tax_classes',
				'css'     => 'width:100%; height: 65px;',
				'type'    => 'textarea',
				'default' => sprintf( __( 'Reduced Rate%sZero Rate', 'woocommerce' ), PHP_EOL )
			),

			array(
				'title'   => __( 'Display prices in the shop:', 'woocommerce' ),
				'id'      => 'woocommerce_tax_display_shop',
				'default' => 'excl',
				'type'    => 'select',
				'options' => array(
					'incl'   => __( 'Including tax', 'woocommerce' ),
					'excl'   => __( 'Excluding tax', 'woocommerce' ),
				)
			),

			array(
				'title'   => __( 'Price display suffix:', 'woocommerce' ),
				'id'      => 'woocommerce_price_display_suffix',
				'default' => '',
				'type'    => 'text',
				'desc'    => __( 'Define text to show after your product prices. This could be, for example, "inc. Vat" to explain your pricing. You can also have prices substituted here using one of the following: <code>{price_including_tax}, {price_excluding_tax}</code>.', 'woocommerce' ),
			),

			array(
				'title'   => __( 'Display prices during cart/checkout:', 'woocommerce' ),
				'id'      => 'woocommerce_tax_display_cart',
				'default' => 'excl',
				'type'    => 'select',
				'options' => array(
					'incl'   => __( 'Including tax', 'woocommerce' ),
					'excl'   => __( 'Excluding tax', 'woocommerce' ),
				),
				'autoload'      => false
			),

			array(
				'title'   => __( 'Display tax totals:', 'woocommerce' ),
				'id'      => 'woocommerce_tax_total_display',
				'default' => 'itemized',
				'type'    => 'select',
				'options' => array(
					'single'     => __( 'As a single total', 'woocommerce' ),
					'itemized'   => __( 'Itemized', 'woocommerce' ),
				),
				'autoload' => false
			),

			array( 'type' => 'sectionend', 'id' => 'tax_options' ),

		) );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );

		if ( $current_section == 'standard' || in_array( $current_section, array_map( 'sanitize_title', $tax_classes ) ) ) {
			$this->output_tax_rates();
		} else {
			$settings = $this->get_settings();

			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section, $wpdb;

		if ( ! $current_section ) {

			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );

		} else {

			$this->save_tax_rates();

		}

		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_tax_rates_%') OR `option_name` LIKE ('_transient_timeout_wc_tax_rates_%')" );
	}

	/**
	 * Output tax rate tables
	 */
	public function output_tax_rates() {
		global $wpdb;

		$page          = ! empty( $_GET['p'] ) ? absint( $_GET['p'] ) : 1;
		$limit         = 100;
		$current_class = $this->get_current_tax_class();
		?>
		<h3><?php printf( __( 'Tax Rates for the "%s" Class', 'woocommerce' ), $current_class ? esc_html( $current_class ) : __( 'Standard', 'woocommerce' ) ); ?></h3>
		<p><?php printf( __( 'Define tax rates for countries and states below. <a href="%s">See here</a> for available alpha-2 country codes.', 'woocommerce' ), 'http://en.wikipedia.org/wiki/ISO_3166-1#Current_codes' ); ?></p>
		<table class="wc_tax_rates wc_input_table sortable widefat">
			<thead>
				<tr>
					<th class="sort">&nbsp;</th>

					<th width="8%"><?php _e( 'Country&nbsp;Code', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('A 2 digit country code, e.g. US. Leave blank to apply to all.', 'woocommerce'); ?>">[?]</span></th>

					<th width="8%"><?php _e( 'State&nbsp;Code', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('A 2 digit state code, e.g. AL. Leave blank to apply to all.', 'woocommerce'); ?>">[?]</span></th>

					<th><?php _e( 'ZIP/Postcode', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Postcode for this rule. Semi-colon (;) separate multiple values. Leave blank to apply to all areas. Wildcards (*) can be used. Ranges for numeric postcodes (e.g. 12345-12350) will be expanded into individual postcodes.', 'woocommerce'); ?>">[?]</span></th>

					<th><?php _e( 'City', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Cities for this rule. Semi-colon (;) separate multiple values. Leave blank to apply to all cities.', 'woocommerce'); ?>">[?]</span></th>

					<th width="8%"><?php _e( 'Rate&nbsp;%', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e( 'Enter a tax rate (percentage) to 4 decimal places.', 'woocommerce' ); ?>">[?]</span></th>

					<th width="8%"><?php _e( 'Tax&nbsp;Name', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Enter a name for this tax rate.', 'woocommerce'); ?>">[?]</span></th>

					<th width="8%"><?php _e( 'Priority', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Choose a priority for this tax rate. Only 1 matching rate per priority will be used. To define multiple tax rates for a single area you need to specify a different priority per rate.', 'woocommerce'); ?>">[?]</span></th>

					<th width="8%"><?php _e( 'Compound', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Choose whether or not this is a compound rate. Compound tax rates are applied on top of other tax rates.', 'woocommerce'); ?>">[?]</span></th>

					<th width="8%"><?php _e( 'Shipping', 'woocommerce' ); ?>&nbsp;<span class="tips" data-tip="<?php _e('Choose whether or not this tax rate also gets applied to shipping.', 'woocommerce'); ?>">[?]</span></th>

				</tr>
			</thead>
			<tbody id="rates">
				<?php
					$rates = $wpdb->get_results( $wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
						WHERE tax_rate_class = %s
						ORDER BY tax_rate_order
						LIMIT %d, %d
						" ,
						sanitize_title( $current_class ),
						( $page - 1 ) * $limit,
						$limit
					) );

					foreach ( $rates as $rate ) {
						?>
						<tr class="tips" data-tip="<?php echo __( 'Tax rate ID', 'woocommerce' ) . ': ' . $rate->tax_rate_id; ?>">
							<td class="sort"><input type="hidden" class="remove_tax_rate" name="remove_tax_rate[<?php echo $rate->tax_rate_id ?>]" value="0" /></td>

							<td class="country" width="8%">
								<input type="text" value="<?php echo esc_attr( $rate->tax_rate_country ) ?>" placeholder="*" name="tax_rate_country[<?php echo $rate->tax_rate_id ?>]" class="wc_input_country_iso" />
							</td>

							<td class="state" width="8%">
								<input type="text" value="<?php echo esc_attr( $rate->tax_rate_state ) ?>" placeholder="*" name="tax_rate_state[<?php echo $rate->tax_rate_id ?>]" />
							</td>

							<td class="postcode">
								<input type="text" value="<?php
									$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='postcode' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );

									echo esc_attr( implode( '; ', $locations ) );
								?>" placeholder="*" data-name="tax_rate_postcode[<?php echo $rate->tax_rate_id ?>]" />
							</td>

							<td class="city">
								<input type="text" value="<?php
									$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='city' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );
									echo esc_attr( implode( '; ', $locations ) );
								?>" placeholder="*" data-name="tax_rate_city[<?php echo $rate->tax_rate_id ?>]" />
							</td>

							<td class="rate" width="8%">
								<input type="number" step="any" min="0" value="<?php echo esc_attr( $rate->tax_rate ) ?>" placeholder="0" name="tax_rate[<?php echo $rate->tax_rate_id ?>]" />
							</td>

							<td class="name" width="8%">
								<input type="text" value="<?php echo esc_attr( $rate->tax_rate_name ) ?>" name="tax_rate_name[<?php echo $rate->tax_rate_id ?>]" />
							</td>

							<td class="priority" width="8%">
								<input type="number" step="1" min="1" value="<?php echo esc_attr( $rate->tax_rate_priority ) ?>" name="tax_rate_priority[<?php echo $rate->tax_rate_id ?>]" />
							</td>

							<td class="compound" width="8%">
								<input type="checkbox" class="checkbox" name="tax_rate_compound[<?php echo $rate->tax_rate_id ?>]" <?php checked( $rate->tax_rate_compound, '1' ); ?> />
							</td>

							<td class="apply_to_shipping" width="8%">
								<input type="checkbox" class="checkbox" name="tax_rate_shipping[<?php echo $rate->tax_rate_id ?>]" <?php checked($rate->tax_rate_shipping, '1' ); ?> />
							</td>
						</tr>
						<?php
					}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="10">
						<a href="#" class="button plus insert"><?php _e( 'Insert row', 'woocommerce' ); ?></a>
						<a href="#" class="button minus remove_tax_rates"><?php _e( 'Remove selected row(s)', 'woocommerce' ); ?></a>

						<div class="pagination">
							<?php
								echo str_replace( 'page-numbers', 'page-numbers button', paginate_links( array(
									'base'      => add_query_arg( 'p', '%#%' ),
									'type'      => 'plain',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => ceil( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(tax_rate_id) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = %s;", sanitize_title( $current_class ) ) ) ) / $limit ),
									'current'   => $page
								) ) );
							?>
						</div>

						<a href="#" download="tax_rates.csv" class="button export"><?php _e( 'Export CSV', 'woocommerce' ); ?></a>
						<a href="<?php echo admin_url( 'admin.php?import=woocommerce_tax_rate_csv' ); ?>" class="button import"><?php _e( 'Import CSV', 'woocommerce' ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			jQuery( function() {
				jQuery('.wc_tax_rates .remove_tax_rates').click(function() {
					var $tbody = jQuery('.wc_tax_rates').find('tbody');
					if ( $tbody.find('tr.current').size() > 0 ) {
						$current = $tbody.find('tr.current');
						$current.find('input').val('');
						$current.find('input.remove_tax_rate').val('1');

						$current.each(function(){
							if ( jQuery(this).is('.new') )
								jQuery(this).remove();
							else
								jQuery(this).hide();
						});
					} else {
						alert('<?php echo esc_js( __( 'No row(s) selected', 'woocommerce' ) ); ?>');
					}
					return false;
				});

				jQuery('.wc_tax_rates .export').click(function() {

					var csv_data = "data:application/csv;charset=utf-8,<?php _e( 'Country Code', 'woocommerce' ); ?>,<?php _e( 'State Code', 'woocommerce' ); ?>,<?php _e( 'ZIP/Postcode', 'woocommerce' ); ?>,<?php _e( 'City', 'woocommerce' ); ?>,<?php _e( 'Rate %', 'woocommerce' ); ?>,<?php _e( 'Tax Name', 'woocommerce' ); ?>,<?php _e( 'Priority', 'woocommerce' ); ?>,<?php _e( 'Compound', 'woocommerce' ); ?>,<?php _e( 'Shipping', 'woocommerce' ); ?>,<?php _e( 'Tax Class', 'woocommerce' ); ?>\n";

					jQuery('#rates tr:visible').each(function() {
						var row = '';
						jQuery(this).find('td:not(.sort) input').each(function() {

							if ( jQuery(this).is('.checkbox') ) {

								if ( jQuery(this).is(':checked') ) {
									val = 1;
								} else {
									val = 0;
								}

							} else {

								var val = jQuery(this).val();

								if ( ! val )
									val = jQuery(this).attr('placeholder');
							}

							row = row + val + ',';
						});
						row = row + '<?php echo $current_class; ?>';
						//row.substring( 0, row.length - 1 );
						csv_data = csv_data + row + "\n";
					});

					jQuery(this).attr( 'href', encodeURI( csv_data ) );

					return true;
				});

				jQuery('.wc_tax_rates .insert').click(function() {
					var $tbody = jQuery('.wc_tax_rates').find('tbody');
					var size = $tbody.find('tr').size();
					var code = '<tr class="new">\
							<td class="sort">&nbsp;</td>\
							<td class="country" width="8%">\
								<input type="text" placeholder="*" name="tax_rate_country[new-' + size + ']" class="wc_input_country_iso" />\
							</td>\
							<td class="state" width="8%">\
								<input type="text" placeholder="*" name="tax_rate_state[new-' + size + ']" />\
							</td>\
							<td class="postcode">\
								<input type="text" placeholder="*" name="tax_rate_postcode[new-' + size + ']" />\
							</td>\
							<td class="city">\
								<input type="text" placeholder="*" name="tax_rate_city[new-' + size + ']" />\
							</td>\
							<td class="rate" width="8%">\
								<input type="number" step="any" min="0" placeholder="0" name="tax_rate[new-' + size + ']" />\
							</td>\
							<td class="name" width="8%">\
								<input type="text" name="tax_rate_name[new-' + size + ']" />\
							</td>\
							<td class="priority" width="8%">\
								<input type="number" step="1" min="1" value="1" name="tax_rate_priority[new-' + size + ']" />\
							</td>\
							<td class="compound" width="8%">\
								<input type="checkbox" class="checkbox" name="tax_rate_compound[new-' + size + ']" />\
							</td>\
							<td class="apply_to_shipping" width="8%">\
								<input type="checkbox" class="checkbox" name="tax_rate_shipping[new-' + size + ']" checked="checked" />\
							</td>\
						</tr>';

					if ( $tbody.find('tr.current').size() > 0 ) {
						$tbody.find('tr.current').after( code );
					} else {
						$tbody.append( code );
					}

					jQuery( "td.country input" ).autocomplete({
						source: availableCountries,
						minLength: 3
					});

					jQuery( "td.state input" ).autocomplete({
						source: availableStates,
						minLength: 3
					});

					return false;
				});

				jQuery('.wc_tax_rates td.postcode, .wc_tax_rates td.city').find('input').change(function() {
					jQuery(this).attr( 'name', jQuery(this).attr( 'data-name' ) );
				});

				var availableCountries = [<?php
					$countries = array();
					foreach ( WC()->countries->get_allowed_countries() as $value => $label )
						$countries[] = '{ label: "' . esc_attr( $label ) . '", value: "' . $value . '" }';
					echo implode( ', ', $countries );
				?>];

				var availableStates = [<?php
					$countries = array();
					foreach ( WC()->countries->get_allowed_country_states() as $value => $label )
						foreach ( $label as $code => $state )
							$countries[] = '{ label: "' . esc_attr( $state ) . '", value: "' . $code . '" }';
					echo implode( ', ', $countries );
				?>];

				jQuery( "td.country input" ).autocomplete({
					source: availableCountries,
					minLength: 3
				});

				jQuery( "td.state input" ).autocomplete({
					source: availableStates,
					minLength: 3
				});
			});
		</script>
		<?php
	}

	/**
	 * Get tax class being edited
	 * @return string
	 */
	private function get_current_tax_class() {
		global $current_section;

		$tax_classes   = array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );
		$current_class = '';

		foreach( $tax_classes as $class ) {
			if ( sanitize_title( $class ) == $current_section ) {
				$current_class = $class;
			}
		}

		return $current_class;
	}

	/**
	 * Insert a tax rate
	 * @param  array $_tax_rate
	 * @return  int tax rate id
	 */
	private function insert_tax_rate( $_tax_rate ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		$tax_rate_id = $wpdb->insert_id;

		do_action( 'woocommerce_tax_rate_added', $tax_rate_id, $_tax_rate );

		return $tax_rate_id;
	}

	/**
	 * Update a tax rate
	 * @param  int $tax_rate_id
	 * @param  array $_tax_rate
	 * @return  int tax rate id
	 */
	private function update_tax_rate( $tax_rate_id, $_tax_rate ) {
		global $wpdb;

		$tax_rate_id = absint( $tax_rate_id );

		$wpdb->update(
			$wpdb->prefix . "woocommerce_tax_rates",
			$_tax_rate,
			array(
				'tax_rate_id' => $tax_rate_id
			)
		);

		do_action( 'woocommerce_tax_rate_updated', $tax_rate_id, $_tax_rate );

		return $tax_rate_id;
	}

	/**
	 * Delete a tax rate from the database
	 * @param  int $tax_rate_id
	 */
	private function delete_tax_rate( $tax_rate_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d;", $tax_rate_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d;", $tax_rate_id ) );

		do_action( 'woocommerce_tax_rate_deleted', $tax_rate_id );
	}

	/**
	 * format the state
	 * @param  string $state
	 * @return string
	 */
	private function tax_rate_state( $state ) {
		$state = strtoupper( $state );
		return $state === '*' ? '' : $state;
	}

	/**
	 * format the country
	 * @param  string $state
	 * @return string
	 */
	private function tax_rate_country( $country ) {
		$country = strtoupper( $country );
		return $country === '*' ? '' : $country;
	}

	/**
	 * format the tax rate name
	 * @param  string $state
	 * @return string
	 */
	private function tax_rate_name( $name ) {
		return $name ? $name : __( 'Tax', 'woocommerce' );
	}

	/**
	 * format the rate
	 * @param  string $state
	 * @return float
	 */
	private function format_tax_rate( $rate ) {
		return number_format( (double) $rate, 4, '.', '' );
	}

	/**
	 * format the city
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_city( $city ) {
		return strtoupper( $city );
	}

	/**
	 * format the postcodes
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_postcode( $postcode ) {
		return strtoupper( $postcode );
	}

	/**
	 * Update postcodes for a tax rate in the DB
	 * @param  int $tax_rate_id
	 * @param  string $postcodes
	 * @return string
	 */
	private function update_tax_rate_postcodes( $tax_rate_id, $postcodes ) {
		global $wpdb;

		// Delete old
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d AND location_type = 'postcode';", $tax_rate_id ) );

		// Add changed
		$postcodes      = array_filter( explode( ';', $postcodes ) );
		$postcode_query = array();

		foreach( $postcodes as $postcode ) {
			if ( strstr( $postcode, '-' ) ) {
				$postcode_parts = explode( '-', $postcode );

				if ( is_numeric( $postcode_parts[0] ) && is_numeric( $postcode_parts[1] ) && $postcode_parts[1] > $postcode_parts[0] ) {
					for ( $i = $postcode_parts[0]; $i <= $postcode_parts[1]; $i ++ ) {
						if ( ! $i ) {
							continue;
						}

						if ( strlen( $i ) < strlen( $postcode_parts[0] ) ) {
							$i = str_pad( $i, strlen( $postcode_parts[0] ), "0", STR_PAD_LEFT );
						}

						$postcode_query[] = "( '" . esc_sql( $i ) . "', $tax_rate_id, 'postcode' )";
					}
				}
			} elseif ( $postcode ) {
				$postcode_query[] = "( '" . esc_sql( $postcode ) . "', $tax_rate_id, 'postcode' )";
			}
		}

		if ( ! empty( $postcode_query ) ) {
			$wpdb->query( "INSERT INTO {$wpdb->prefix}woocommerce_tax_rate_locations ( location_code, tax_rate_id, location_type ) VALUES " . implode( ',', $postcode_query ) );
		}
	}

	/**
	 * Update cities for a tax rate in the DB
	 * @param  int $tax_rate_id
	 * @param  string $cities
	 * @return string
	 */
	private function update_tax_rate_cities( $tax_rate_id, $cities ) {
		global $wpdb;

		// Delete old
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d AND location_type = 'city';", $tax_rate_id ) );

		// Add changed
		$cities     = array_filter( explode( ';', $cities ) );
		$city_query = array();

		foreach( $cities as $city ) {
			$city_query[] = "( '" . esc_sql( $city ) . "', $tax_rate_id, 'city' )";
		}

		if ( ! empty( $city_query ) ) {
			$wpdb->query( "INSERT INTO {$wpdb->prefix}woocommerce_tax_rate_locations ( location_code, tax_rate_id, location_type ) VALUES " . implode( ',', $city_query ) );
		}
	}

	/**
	 * Get a posted tax rate
	 * @param  string $key   Key of tax rate in the post data array
	 * @param  int $order Position/order of rate
	 * @param  string $class Tax class for rate
	 * @return array
	 */
	private function get_posted_tax_rate( $key, $order, $class ) {
		$_tax_rate     = array();
		$tax_rate_keys = array(
			'tax_rate_country',
			'tax_rate_state',
			'tax_rate',
			'tax_rate_name',
			'tax_rate_priority'
		);

		foreach ( $tax_rate_keys as $tax_rate_key ) {
			if ( isset( $_POST[ $tax_rate_key ] ) && isset( $_POST[ $tax_rate_key ][ $key ] ) ) {
				$_tax_rate[ $tax_rate_key ] = wc_clean( $_POST[ $tax_rate_key ][ $key ] );

				if ( method_exists( $this, 'format_' . $tax_rate_key ) ) {
					$_tax_rate[ $tax_rate_key ] = call_user_func( array( $this, 'format_' . $tax_rate_key ), $_tax_rate[ $tax_rate_key ] );
				}
			}
		}

		$_tax_rate['tax_rate_compound'] = isset( $_POST['tax_rate_compound'][ $key ] ) ? 1 : 0;
		$_tax_rate['tax_rate_shipping'] = isset( $_POST['tax_rate_shipping'][ $key ] ) ? 1 : 0;
		$_tax_rate['tax_rate_order']    = $order;
		$_tax_rate['tax_rate_class']    = $class;

		return $_tax_rate;
	}

	/**
	 * Save tax rates
	 */
	public function save_tax_rates() {
		if ( empty( $_POST['tax_rate_country'] ) ) {
			return;
		}

		$current_class = sanitize_title( $this->get_current_tax_class() );
		$index         = 0;

		// Loop posted fields
		foreach ( $_POST['tax_rate_country'] as $key => $value ) {
			$mode          = 0 === strpos( $key, 'new-' ) ? 'insert' : 'update';
			$_tax_rate     = $this->get_posted_tax_rate( $key, $index ++, $current_class );

			if ( 'insert' === $mode ) {
				$tax_rate_id = $this->insert_tax_rate( $_tax_rate );
			} else {
				// Remove rates
				if ( 1 == $_POST['remove_tax_rate'][ $key ] ) {
					$this->delete_tax_rate( $tax_rate_id );
					continue;
				}

				$tax_rate_id = $this->update_tax_rate( $key, $_tax_rate );
			}

			if ( isset( $_POST['tax_rate_postcode'][ $key ] ) ) {
				$this->update_tax_rate_postcodes( $tax_rate_id, $this->format_tax_rate_postcode( $_POST['tax_rate_postcode'][ $key ] ) );
			}
			if ( isset( $_POST['tax_rate_city'][ $key ] ) ) {
				$this->update_tax_rate_cities( $tax_rate_id, $this->format_tax_rate_city( $_POST['tax_rate_city'][ $key ] ) );
			}
		}
	}
}

endif;

return new WC_Settings_Tax();
