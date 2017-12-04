<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Bank Transfer Payment Gateway.
 *
 * Provides a Bank Transfer Payment Gateway. Based on code by Mike Pepper.
 *
 * @class       WC_Gateway_BACS
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce/Classes/Payment
 * @author      WooThemes
 */
class WC_Gateway_BACS extends WC_Payment_Gateway {

	/** @var array Array of locales */
	public $locale;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {

		$this->id                 = 'bacs';
		$this->icon               = apply_filters( 'woocommerce_bacs_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'BACS', 'woocommerce' );
		$this->method_description = __( 'Allows payments by BACS, more commonly known as direct bank/wire transfer.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// BACS account fields shown on the thanks page and in emails
		$this->account_details = get_option( 'woocommerce_bacs_accounts',
			array(
				array(
					'account_name'   => $this->get_option( 'account_name' ),
					'account_number' => $this->get_option( 'account_number' ),
					'sort_code'      => $this->get_option( 'sort_code' ),
					'bank_name'      => $this->get_option( 'bank_name' ),
					'iban'           => $this->get_option( 'iban' ),
					'bic'            => $this->get_option( 'bic' ),
				),
			)
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
		add_action( 'woocommerce_thankyou_bacs', array( $this, 'thankyou_page' ) );

		// Customer Emails
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable bank transfer', 'woocommerce' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Direct bank transfer', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be shipped until the funds have cleared in our account.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'account_details' => array(
				'type'        => 'account_details',
			),
		);

	}

	/**
	 * Generate account details html.
	 *
	 * @return string
	 */
	public function generate_account_details_html() {

		ob_start();

		$country 	= WC()->countries->get_base_country();
		$locale		= $this->get_country_locale();

		// Get sortcode label in the $locale array and use appropriate one
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'woocommerce' );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'Account details', 'woocommerce' ); ?>:</th>
			<td class="forminp" id="bacs_accounts">
				<table class="widefat wc_input_table sortable" cellspacing="0">
					<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th><?php _e( 'Account name', 'woocommerce' ); ?></th>
							<th><?php _e( 'Account number', 'woocommerce' ); ?></th>
							<th><?php _e( 'Bank name', 'woocommerce' ); ?></th>
							<th><?php echo $sortcode; ?></th>
							<th><?php _e( 'IBAN', 'woocommerce' ); ?></th>
							<th><?php _e( 'BIC / Swift', 'woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody class="accounts">
						<?php
						$i = -1;
						if ( $this->account_details ) {
							foreach ( $this->account_details as $account ) {
								$i++;

								echo '<tr class="account">
									<td class="sort"></td>
									<td><input type="text" value="' . esc_attr( wp_unslash( $account['account_name'] ) ) . '" name="bacs_account_name[' . $i . ']" /></td>
									<td><input type="text" value="' . esc_attr( $account['account_number'] ) . '" name="bacs_account_number[' . $i . ']" /></td>
									<td><input type="text" value="' . esc_attr( wp_unslash( $account['bank_name'] ) ) . '" name="bacs_bank_name[' . $i . ']" /></td>
									<td><input type="text" value="' . esc_attr( $account['sort_code'] ) . '" name="bacs_sort_code[' . $i . ']" /></td>
									<td><input type="text" value="' . esc_attr( $account['iban'] ) . '" name="bacs_iban[' . $i . ']" /></td>
									<td><input type="text" value="' . esc_attr( $account['bic'] ) . '" name="bacs_bic[' . $i . ']" /></td>
								</tr>';
							}
						}
						?>
					</tbody>
					<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php _e( '+ Add account', 'woocommerce' ); ?></a> <a href="#" class="remove_rows button"><?php _e( 'Remove selected account(s)', 'woocommerce' ); ?></a></th>
						</tr>
					</tfoot>
				</table>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#bacs_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#bacs_accounts').find('tbody .account').length;

							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="bacs_account_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_account_number[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bank_name[' + size + ']" /></td>\
									<td><input type="text" name="bacs_sort_code[' + size + ']" /></td>\
									<td><input type="text" name="bacs_iban[' + size + ']" /></td>\
									<td><input type="text" name="bacs_bic[' + size + ']" /></td>\
								</tr>').appendTo('#bacs_accounts table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}

	/**
	 * Save account details table.
	 */
	public function save_account_details() {

		$accounts = array();

		if ( isset( $_POST['bacs_account_name'] ) ) {

			$account_names   = array_map( 'wc_clean', $_POST['bacs_account_name'] );
			$account_numbers = array_map( 'wc_clean', $_POST['bacs_account_number'] );
			$bank_names      = array_map( 'wc_clean', $_POST['bacs_bank_name'] );
			$sort_codes      = array_map( 'wc_clean', $_POST['bacs_sort_code'] );
			$ibans           = array_map( 'wc_clean', $_POST['bacs_iban'] );
			$bics            = array_map( 'wc_clean', $_POST['bacs_bic'] );

			foreach ( $account_names as $i => $name ) {
				if ( ! isset( $account_names[ $i ] ) ) {
					continue;
				}

				$accounts[] = array(
					'account_name'   => $account_names[ $i ],
					'account_number' => $account_numbers[ $i ],
					'bank_name'      => $bank_names[ $i ],
					'sort_code'      => $sort_codes[ $i ],
					'iban'           => $ibans[ $i ],
					'bic'            => $bics[ $i ],
				);
			}
		}

		update_option( 'woocommerce_bacs_accounts', $accounts );

	}

	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id
	 */
	public function thankyou_page( $order_id ) {

		if ( $this->instructions ) {
			echo wpautop( wptexturize( wp_kses_post( $this->instructions ) ) );
		}
		$this->bank_details( $order_id );

	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

		if ( ! $sent_to_admin && 'bacs' === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
			$this->bank_details( $order->get_id() );
		}

	}

	/**
	 * Get bank details and place into a list format.
	 *
	 * @param int $order_id
	 */
	private function bank_details( $order_id = '' ) {

		if ( empty( $this->account_details ) ) {
			return;
		}

		// Get order and store in $order
		$order 		= wc_get_order( $order_id );

		// Get the order country and country $locale
		$country 	= $order->get_billing_country();
		$locale		= $this->get_country_locale();

		// Get sortcode label in the $locale array and use appropriate one
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'woocommerce' );

		$bacs_accounts = apply_filters( 'woocommerce_bacs_accounts', $this->account_details );

		if ( ! empty( $bacs_accounts ) ) {
			$account_html = '';
			$has_details  = false;

			foreach ( $bacs_accounts as $bacs_account ) {
				$bacs_account = (object) $bacs_account;

				if ( $bacs_account->account_name ) {
					$account_html .= '<h3 class="wc-bacs-bank-details-account-name">' . wp_kses_post( wp_unslash( $bacs_account->account_name ) ) . ':</h3>' . PHP_EOL;
				}

				$account_html .= '<ul class="wc-bacs-bank-details order_details bacs_details">' . PHP_EOL;

				// BACS account fields shown on the thanks page and in emails
				$account_fields = apply_filters( 'woocommerce_bacs_account_fields', array(
					'bank_name' => array(
						'label' => __( 'Bank', 'woocommerce' ),
						'value' => $bacs_account->bank_name,
					),
					'account_number' => array(
						'label' => __( 'Account number', 'woocommerce' ),
						'value' => $bacs_account->account_number,
					),
					'sort_code'     => array(
						'label' => $sortcode,
						'value' => $bacs_account->sort_code,
					),
					'iban'          => array(
						'label' => __( 'IBAN', 'woocommerce' ),
						'value' => $bacs_account->iban,
					),
					'bic'           => array(
						'label' => __( 'BIC', 'woocommerce' ),
						'value' => $bacs_account->bic,
					),
				), $order_id );

				foreach ( $account_fields as $field_key => $field ) {
					if ( ! empty( $field['value'] ) ) {
						$account_html .= '<li class="' . esc_attr( $field_key ) . '">' . wp_kses_post( $field['label'] ) . ': <strong>' . wp_kses_post( wptexturize( $field['value'] ) ) . '</strong></li>' . PHP_EOL;
						$has_details   = true;
					}
				}

				$account_html .= '</ul>';
			}

			if ( $has_details ) {
				echo '<section class="woocommerce-bacs-bank-details"><h2 class="wc-bacs-bank-details-heading">' . __( 'Our bank details', 'woocommerce' ) . '</h2>' . PHP_EOL . $account_html . '</section>';
			}
		}

	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'on-hold', __( 'Awaiting BACS payment', 'woocommerce' ) );
		} else {
			$order->payment_complete();
		}

		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'    => 'success',
			'redirect'  => $this->get_return_url( $order ),
		);

	}

	/**
	 * Get country locale if localized.
	 *
	 * @return array
	 */
	public function get_country_locale() {

		if ( empty( $this->locale ) ) {

			// Locale information to be used - only those that are not 'Sort Code'
			$this->locale = apply_filters( 'woocommerce_get_bacs_locale', array(
				'AU' => array(
					'sortcode'	=> array(
						'label'		=> __( 'BSB', 'woocommerce' ),
					),
				),
				'CA' => array(
					'sortcode'	=> array(
						'label'		=> __( 'Bank transit number', 'woocommerce' ),
					),
				),
				'IN' => array(
					'sortcode'	=> array(
						'label'		=> __( 'IFSC', 'woocommerce' ),
					),
				),
				'IT' => array(
					'sortcode'	=> array(
						'label'		=> __( 'Branch sort', 'woocommerce' ),
					),
				),
				'NZ' => array(
					'sortcode'	=> array(
						'label'		=> __( 'Bank code', 'woocommerce' ),
					),
				),
				'SE' => array(
					'sortcode'	=> array(
						'label'		=> __( 'Bank code', 'woocommerce' ),
					),
				),
				'US' => array(
					'sortcode'	=> array(
						'label'		=> __( 'Routing number', 'woocommerce' ),
					),
				),
				'ZA' => array(
					'sortcode'	=> array(
						'label'		=> __( 'Branch code', 'woocommerce' ),
					),
				),
			) );

		}

		return $this->locale;

	}
}
