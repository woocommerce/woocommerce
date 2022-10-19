<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

use WC_Shipping_Method;
use WC_Order;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Local Pickup Shipping Method.
 */
class PickupLocation extends WC_Shipping_Method {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'pickup_location';
		$this->method_title       = __( 'Pickup Locations', 'woo-gutenberg-products-block' );
		$this->method_description = __( 'Allow customers to choose a local pickup location during checkout.', 'woo-gutenberg-products-block' );
		$this->init();
	}

	/**
	 * Init function.
	 */
	public function init() {
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled          = $this->get_option( 'enabled' );
		$this->title            = $this->get_option( 'title' );
		$this->tax_status       = $this->get_option( 'tax_status' );
		$this->cost             = $this->get_option( 'cost' );
		$this->pickup_locations = get_option( $this->id . '_pickup_locations', [] );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_attribute_label', array( $this, 'translate_meta_data' ), 10, 3 );
	}

	/**
	 * Calculate shipping.
	 *
	 * @param array $package Package information.
	 */
	public function calculate_shipping( $package = array() ) {
		if ( $this->pickup_locations ) {
			foreach ( $this->pickup_locations as $index => $location ) {
				if ( ! $location['enabled'] ) {
					continue;
				}
				$this->add_rate(
					array(
						'id'          => $this->id . ':' . $index,
						// This is the label shown in shipping rate/method context e.g. London (Local Pickup).
						'label'       => wp_kses_post( $location['name'] . ' (' . $this->title . ')' ),
						'package'     => $package,
						'cost'        => $this->cost,
						'description' => $location['details'],
						'meta_data'   => array(
							'pickup_location' => wp_kses_post( $location['name'] ),
							'pickup_address'  => wc()->countries->get_formatted_address( $location['address'], ', ' ),
						),
					)
				);
			}
		}
	}

	/**
	 * Initialize form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable', 'woo-gutenberg-products-block' ),
				'type'    => 'checkbox',
				'label'   => __( 'If enabled, this method will appear on the block based checkout.', 'woo-gutenberg-products-block' ),
				'default' => 'no',
			),
			'title'      => array(
				'title'       => __( 'Title', 'woo-gutenberg-products-block' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woo-gutenberg-products-block' ),
				'default'     => __( 'Local pickup', 'woo-gutenberg-products-block' ),
				'desc_tip'    => true,
			),
			'tax_status' => array(
				'title'   => __( 'Tax status', 'woo-gutenberg-products-block' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'woo-gutenberg-products-block' ),
					'none'    => _x( 'None', 'Tax status', 'woo-gutenberg-products-block' ),
				),
			),
			'cost'       => array(
				'title'       => __( 'Cost', 'woo-gutenberg-products-block' ),
				'type'        => 'text',
				'placeholder' => '0',
				'description' => __( 'Optional cost for local pickup.', 'woo-gutenberg-products-block' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * See if the method is available.
	 *
	 * @param array $package Package information.
	 * @return bool
	 */
	public function is_available( $package ) {
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', 'yes' === $this->enabled, $package, $this );
	}

	/**
	 * Process options in admin.
	 */
	public function process_admin_options() {
		parent::process_admin_options();

		$locations      = [];
		$location_names = array_map( 'sanitize_text_field', wp_unslash( $_POST['locationName'] ?? [] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		foreach ( $location_names as $index => $location_name ) {
			$locations[] = [
				'name'    => $location_name,
				'address' => [
					'address_1' => wc_clean( wp_unslash( $_POST['address_1'][ $index ] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					'city'      => wc_clean( wp_unslash( $_POST['city'][ $index ] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					'state'     => wc_clean( wp_unslash( $_POST['state'][ $index ] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					'postcode'  => wc_clean( wp_unslash( $_POST['postcode'][ $index ] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					'country'   => wc_clean( wp_unslash( $_POST['country'][ $index ] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
				],
				'details' => wc_clean( wp_unslash( $_POST['details'][ $index ] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
				'enabled' => wc_string_to_bool( wc_clean( wp_unslash( $_POST['locationEnabled'][ $index ] ?? 1 ) ) ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			];
		}

		update_option( $this->id . '_pickup_locations', $locations );
		$this->pickup_locations = $locations;
	}

	/**
	 * Translates meta data for the shipping method.
	 *
	 * @param string $label Meta label.
	 * @param string $name Meta key.
	 * @param mixed  $product Product if applicable.
	 * @return string
	 */
	public function translate_meta_data( $label, $name, $product ) {
		if ( $product ) {
			return $label;
		}
		switch ( $name ) {
			case 'pickup_location':
				return __( 'Pickup Location', 'woo-gutenberg-products-block' );
			case 'pickup_address':
				return __( 'Pickup Address', 'woo-gutenberg-products-block' );
		}
		return $label;
	}

	/**
	 * Admin options screen.
	 *
	 * See also WC_Shipping_Method::admin_options().
	 */
	public function admin_options() {
		parent::admin_options();
		?>
<table class="form-table" id="pickup_locations">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Pickup Locations', 'woo-gutenberg-products-block' ); ?>
				</label>
			</th>
			<td class="">
				<table class="wc-local-pickup-locations wc_shipping widefat sortable">
					<thead>
						<tr>
							<th class="wc-local-pickup-location-sort"><?php echo wc_help_tip( __( 'Drag and drop to re-order your pickup locations.', 'woo-gutenberg-products-block' ) ); ?></th>
							<th class="wc-local-pickup-location-name"><?php esc_html_e( 'Location Name', 'woo-gutenberg-products-block' ); ?></th>
							<th class="wc-local-pickup-location-enabled"><?php esc_html_e( 'Enabled', 'woo-gutenberg-products-block' ); ?></th>
							<th class="wc-local-pickup-location-address"><?php esc_html_e( 'Pickup Address', 'woo-gutenberg-products-block' ); ?></th>
							<th class="wc-local-pickup-location-details"><?php esc_html_e( 'Pickup Details', 'woo-gutenberg-products-block' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="5">
								<button type="button" class="add button"><?php esc_html_e( 'Add pickup location', 'woo-gutenberg-products-block' ); ?></button>
							</th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach ( $this->pickup_locations as $location ) {
							echo '<tr>';
							echo $this->pickup_location_row( $location ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>
<style>
	.wc-local-pickup-locations thead th {
		vertical-align: middle;
	}
	.wc-local-pickup-locations tbody td {
		border-top: 2px solid #f9f9f9;
	}
	.wc-local-pickup-locations tbody tr:nth-child( odd ) td {
		background: #f9f9f9;
	}
	td.wc-local-pickup-location-sort {
		cursor: move;
		font-size: 15px;
		text-align: center;
	}
	td.wc-local-pickup-location-sort::before {
		content: '\f333';
		font-family: 'Dashicons';
		text-align: center;
		line-height: 1;
		color: #999;
		display: block;
		width: 17px;
		float: left;
		height: 100%;
		line-height: 24px;
	}
	td.wc-local-pickup-location-sort:hover::before {
		color: #333;
	}
	.wc-local-pickup-location-enabled {
		text-align: center;
	}
	.wc-local-pickup-locations .wc-local-pickup-location-name,
	.wc-local-pickup-locations .wc-local-pickup-location-address,
	.wc-local-pickup-locations .wc-local-pickup-location-details {
		width: 25%;
		padding-top: 10px !important;
		padding-bottom: 10px !important;
	}

	#pickup_locations .wc-local-pickup-locations .editing .view {
		display: none;
	}
	#pickup_locations .wc-local-pickup-locations .editing .edit {
		display: block;
	}
	#pickup_locations .wc-local-pickup-locations .editable input,
	#pickup_locations .wc-local-pickup-locations .editable textarea,
	#pickup_locations .wc-local-pickup-locations .editable select {
		vertical-align: middle;
		text-overflow: ellipsis;
		width: 100%;
		margin: 2px 0;
	}
	#pickup_locations .wc-local-pickup-locations .editable textarea {
		padding: 5px;
	}

	#pickup_locations .wc-local-pickup-locations tr .row-actions {
		position: relative;
	}
	#pickup_locations .wc-local-pickup-locations tr:hover .row-actions {
		position: static;
	}
</style>
<script type="text/javascript">
	function insertRow() {
		var tbodyRef = document.querySelectorAll('#pickup_locations table tbody')[0];
		let size = tbodyRef.getElementsByTagName('tr').length;
		let newRow = tbodyRef.insertRow( size );
		var txt = document.createElement('textarea');
		txt.innerHTML = '<?php echo esc_js( $this->pickup_location_row() ); ?>';
		newRow.innerHTML = txt.value;
	}

	var locationsTable = document.querySelectorAll("table.wc-local-pickup-locations")[0];
	var addButton = document.querySelectorAll("#pickup_locations button.add")[0];

	addButton.addEventListener( "click", function(e) {
		insertRow();
		return false;
	}, false );

	locationsTable.addEventListener( "click", function(event) {
		const editButton = event.target.closest('button.button-link-edit');
		const deleteButton = event.target.closest('button.button-link-delete');
		const enabledToggleButton = event.target.closest('button.enabled-toggle-button');

		if (editButton !== null) {
			const toggleText = editButton.dataset.toggle;
			const innerText = editButton.innerText;
			const tr = editButton.parentElement.parentElement.parentElement;

			if ( tr.classList.contains('editing') ) {
				tr.querySelectorAll('.edit').forEach( function( element ) {
					const newValues = [];
					element.querySelectorAll('.edit input[name], .edit textarea[name], .edit select[name]').forEach( function( input ) {
						const newValue = input.value;
						if ( newValue ) {
							newValues.push( input.value );
						}
					});
					element.parentElement.querySelector('.view').innerText = newValues.join(', ') || '—';
				});
			}

			editButton.innerText = toggleText;
			editButton.dataset.toggle = innerText;
			editButton.parentElement.parentElement.parentElement.classList.toggle('editing');
			return false;
		}

		if (deleteButton !== null) {
			deleteButton.parentElement.parentElement.parentElement.remove();
			return false;
		}

		if (enabledToggleButton !== null) {
			var toggleDisplay = enabledToggleButton.parentElement.querySelectorAll(".woocommerce-input-toggle")[0];
			var toggleInput = enabledToggleButton.parentElement.querySelectorAll("input")[0];

			if ( toggleDisplay.classList.contains("woocommerce-input-toggle--enabled") ) {
				toggleInput.value = 0;
				toggleDisplay.classList.add("woocommerce-input-toggle--disabled");
				toggleDisplay.classList.remove("woocommerce-input-toggle--enabled");
				toggleDisplay.textContent = "<?php echo esc_js( 'No', 'woo-gutenberg-products-block' ); ?>";
			} else {
				toggleInput.value = 1;
				toggleDisplay.classList.remove("woocommerce-input-toggle--disabled");
				toggleDisplay.classList.add("woocommerce-input-toggle--enabled");
				toggleDisplay.textContent = "<?php echo esc_js( 'Yes', 'woo-gutenberg-products-block' ); ?>";
			}
			return false;
		}

		return true;
	}, false );

	locationsTable.addEventListener( "focus", function(event) {
		const input = event.target.closest('input, select');
		if (input !== null) {
			input.parentElement.classList.add("is-active");
		}
	}, true );

	locationsTable.addEventListener( "blur", function(event) {
		const input = event.target.closest('input, select');
		if (input !== null) {
			const nextInput = event.relatedTarget ? event.relatedTarget.closest('input, select') : null;
			if(nextInput === null || nextInput.parentElement !== input.parentElement) {
				input.parentElement.classList.remove("is-active");
			}
		}
	}, true );

	var states = JSON.parse( decodeURIComponent( '<?php echo rawurlencode( wp_json_encode( WC()->countries->get_states() ) ); ?>' ) );

	locationsTable.addEventListener( "change", function(event) {
		const countrySelect = event.target.closest('select.country-select');

		if (countrySelect === null) {
			return;
		}

		const stateInput = countrySelect.parentElement.querySelectorAll('input.state-input')[0];
		const stateSelect = countrySelect.parentElement.querySelectorAll('select.state-select')[0];
		const selectedCountry = countrySelect.value;
		const selectedState = stateInput.value;

		if ( selectedCountry === "" ) {
			countrySelect.classList.add("placeholder");
		} else {
			countrySelect.classList.remove("placeholder");
		}

		if (states[selectedCountry] === undefined || states[selectedCountry].length === 0) {
			stateSelect.hidden = true;
			stateInput.type = 'text';
			return;
		}

		stateSelect.innerHTML = '';
		for (const [key, value] of Object.entries(states[selectedCountry])) {
			const option = document.createElement("option");
			option.value = key;
			option.text = value;
			option.selected = selectedState === key;
			stateSelect.add( option );
		};
		stateSelect.hidden = false;
		stateInput.type = 'hidden';
	}, true );

	locationsTable.addEventListener( "change", function(event) {
		const stateSelect = event.target.closest('select.state-select');

		if (stateSelect === null) {
			return;
		}

		const stateInput = stateSelect.parentElement.querySelectorAll('input.state-input')[0];
		stateInput.value = stateSelect.value;
	}, true );

	var event = new Event('change');
	locationsTable.querySelectorAll('select.country-select').forEach(function(countrySelect) {
		countrySelect.dispatchEvent(event);
	});
</script>
		<?php
	}

	/**
	 * Row for the settings table.
	 *
	 * @param array $location Location data.
	 * @return string
	 */
	protected function pickup_location_row( $location = [] ) {
		ob_start();
		$location            = wp_parse_args(
			$location,
			[
				'name'    => '',
				'enabled' => false,
				'details' => '',
			]
		);
		$location['address'] = wp_parse_args(
			$location['address'] ?? [],
			[
				'address_1' => '',
				'city'      => '',
				'state'     => '',
				'postcode'  => '',
				'country'   => '',
			]
		);
		?>
		<td width="1%" class="wc-local-pickup-location-sort sort"></td>
		<td class="wc-local-pickup-location-name editable">
			<div class="view"><?php echo esc_html( $location['name'] ?? '' ); ?></div>
			<div class="edit" hidden><input type="text" name="locationName[]" value="<?php echo esc_attr( $location['name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'New location', 'woo-gutenberg-products-block' ); ?>" /></div>
			<div class="row-actions">
				<button type="button" class="button-link-edit button-link" data-toggle="<?php esc_attr_e( 'Done', 'woo-gutenberg-products-block' ); ?>"><?php esc_html_e( 'Edit', 'woo-gutenberg-products-block' ); ?></button> | <button type="button" class="button-link button-link-delete"><?php esc_html_e( 'Delete', 'woo-gutenberg-products-block' ); ?></button>
			</div>
		</td>
		<td width="1%" class="wc-local-pickup-location-enabled">
			<button type="button" class="enabled-toggle-button button-link">
				<?php
				echo ! empty( $location['enabled'] )
					? '<span class="woocommerce-input-toggle woocommerce-input-toggle--enabled">' . esc_html__( 'Yes', 'woo-gutenberg-products-block' ) . '</span><input type="hidden" name="locationEnabled[]" value="1" />'
					: '<span class="woocommerce-input-toggle woocommerce-input-toggle--disabled">' . esc_html__( 'No', 'woo-gutenberg-products-block' ) . '</span><input type="hidden" name="locationEnabled[]" value="0" />';
				?>
			</button>
		</td>
		<td class="wc-local-pickup-location-address editable">
			<div class="view"><?php echo esc_html( implode( ', ', array_filter( $location['address'] ) ) ); ?></div>
			<div class="edit" hidden>
				<input type="text" name="address_1[]" value="<?php echo esc_attr( $location['address']['address_1'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Address', 'woo-gutenberg-products-block' ); ?>" />
				<input type="text" name="city[]" value="<?php echo esc_attr( $location['address']['city'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'City', 'woo-gutenberg-products-block' ); ?>" />
				<select class="state-select" hidden></select>
				<input type="text" class="state-input" name="state[]" value="<?php echo esc_attr( $location['address']['state'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'State', 'woo-gutenberg-products-block' ); ?>" />
				<input type="text" name="postcode[]" value="<?php echo esc_attr( $location['address']['postcode'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Postcode / ZIP', 'woo-gutenberg-products-block' ); ?>" />
				<select class="country-select" name="country[]">
					<option value="" disabled selected><?php esc_html_e( 'Country', 'woo-gutenberg-products-block' ); ?></option>
					<?php foreach ( WC()->countries->get_countries() as $code => $label ) : ?>
						<option <?php selected( $code, $location['address']['country'] ); ?> value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</td>
		<td class="wc-local-pickup-location-details editable">
			<div class="view"><?php echo wp_kses_post( wpautop( $location['details'] ?: '&mdash;' ) ); ?></div>
			<div class="edit" hidden><textarea name="details[]" rows="3"><?php echo esc_attr( $location['details'] ?? '' ); ?></textarea></div>
		</td>
		<?php
		return ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
