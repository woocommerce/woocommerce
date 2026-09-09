<?php
/**
 * Finance data REST schemas.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Enums\FinanceDataSource;
use Automattic\WooCommerce\Enums\PayoutStatus;

defined( 'ABSPATH' ) || exit;

/**
 * JSON schemas for the finance REST responses, and the data-model versions core can serve.
 *
 * @internal
 */
class FinanceDataSchemas {

	/**
	 * The data-model versions core can serve, per finance data type.
	 *
	 * @var array<string, int[]>
	 */
	public const SUPPORTED_VERSIONS = array(
		FinanceDataSource::BALANCE => array( 1 ),
		FinanceDataSource::PAYOUTS => array( 1 ),
	);

	/**
	 * Whether core can serve a data type at a given data-model version.
	 *
	 * @param string $data_type The finance data type.
	 * @param int    $version   The data-model version.
	 * @return bool
	 *
	 * @since 11.2.0
	 */
	public static function is_supported( string $data_type, int $version ): bool {
		return in_array( $version, self::SUPPORTED_VERSIONS[ $data_type ] ?? array(), true );
	}

	/**
	 * Get the schema for the providers list response.
	 *
	 * @return array
	 *
	 * @since 11.2.0
	 */
	public function get_providers_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'WooCommerce payments finance providers.',
			'type'       => 'object',
			'properties' => array(
				'providers' => array(
					'type'        => 'array',
					'description' => esc_html__( 'The payment gateways that can return finance data, with the data types each one supports.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => array(
						'type'        => 'object',
						'description' => esc_html__( 'A payment gateway that can return finance data.', 'woocommerce' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'gateway_id' => array(
								'type'        => 'string',
								'description' => esc_html__( 'The payment gateway id.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'title'      => array(
								'type'        => 'string',
								'description' => esc_html__( 'The payment gateway title.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'icon_url'   => array(
								'type'        => array( 'string', 'null' ),
								'format'      => 'uri',
								'description' => esc_html__( 'The URL of the payment gateway icon.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'data_types' => array(
								'type'        => 'array',
								'description' => esc_html__( 'The finance data types the gateway supports and the data-model version of each.', 'woocommerce' ),
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'context'    => array( 'view', 'edit' ),
									'readonly'   => true,
									'properties' => array(
										'type'           => array(
											'type'        => 'string',
											'enum'        => array( FinanceDataSource::BALANCE, FinanceDataSource::PAYOUTS ),
											'description' => esc_html__( 'The finance data type.', 'woocommerce' ),
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'schema_version' => array(
											'type'        => 'integer',
											'description' => esc_html__( 'The data-model version the gateway returns for this data type.', 'woocommerce' ),
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema for the balances response.
	 *
	 * @return array
	 *
	 * @since 11.2.0
	 */
	public function get_balances_schema(): array {
		return $this->get_page_schema( 'WooCommerce payments finance balances.', $this->get_balance_item_schema_v1() );
	}

	/**
	 * Get the schema for the payouts response.
	 *
	 * @return array
	 *
	 * @since 11.2.0
	 */
	public function get_payouts_schema(): array {
		return $this->get_page_schema( 'WooCommerce payments finance payouts.', $this->get_payout_item_schema_v1() );
	}

	/**
	 * Get the envelope schema shared by every finance data response.
	 *
	 * @param string $title       The schema title.
	 * @param array  $item_schema The schema of one item.
	 * @return array
	 */
	private function get_page_schema( string $title, array $item_schema ): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $title,
			'type'       => 'object',
			'properties' => array(
				'schema_version' => array(
					'type'        => 'integer',
					'description' => esc_html__( 'The data-model version of the items.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'items'          => array(
					'type'        => 'array',
					'description' => esc_html__( 'The items on this page.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'items'       => $item_schema,
				),
				'has_more'       => array(
					'type'        => 'boolean',
					'description' => esc_html__( 'Whether more items exist beyond this page.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'next_cursor'    => array(
					'type'        => array( 'string', 'null' ),
					'description' => esc_html__( 'Opaque cursor to pass as the cursor parameter to fetch the next page, or null when there is none.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'prev_cursor'    => array(
					'type'        => array( 'string', 'null' ),
					'description' => esc_html__( 'Opaque cursor to pass as the cursor parameter to fetch the previous page, or null when there is none.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get the schema of one balance item (data-model version 1).
	 *
	 * @return array
	 */
	private function get_balance_item_schema_v1(): array {
		return array(
			'type'                 => 'object',
			'description'          => esc_html__( 'The account balance for one currency.', 'woocommerce' ),
			'context'              => array( 'view', 'edit' ),
			'readonly'             => true,
			'additionalProperties' => false,
			'properties'           => array(
				'currency'         => $this->get_currency_schema(),
				'amount'           => array(
					'type'        => 'string',
					'description' => esc_html__( 'The balance as a decimal string in major currency units.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'available_amount' => array(
					'type'        => array( 'string', 'null' ),
					'description' => esc_html__( 'The amount available for payout as a decimal string, or null when the provider does not report it.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'payout_link'      => array(
					'type'        => array( 'object', 'null' ),
					'description' => esc_html__( 'A link for initiating a payout, or null when not available.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'title' => array(
							'type'        => 'string',
							'description' => esc_html__( 'The link title.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'url'   => array(
							'type'        => 'string',
							'format'      => 'uri',
							'description' => esc_html__( 'The link URL.', 'woocommerce' ),
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema of one payout item (data-model version 1).
	 *
	 * @return array
	 */
	private function get_payout_item_schema_v1(): array {
		return array(
			'type'                 => 'object',
			'description'          => esc_html__( 'A payout to the merchant bank account.', 'woocommerce' ),
			'context'              => array( 'view', 'edit' ),
			'readonly'             => true,
			'additionalProperties' => false,
			'properties'           => array(
				'id'              => array(
					'type'        => 'string',
					'description' => esc_html__( 'The provider-issued payout identifier.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'currency'        => $this->get_currency_schema(),
				'amount'          => array(
					'type'        => 'string',
					'description' => esc_html__( 'The payout amount as a decimal string in major currency units.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'bank_account'    => array(
					'type'        => array( 'string', 'null' ),
					'description' => esc_html__( 'The bank account name or details, or null when not reported.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_initiated'  => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => esc_html__( 'When the payout was initiated, as an RFC 3339 date-time in UTC.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_expected'   => array(
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'description' => esc_html__( 'The expected deposit date for pending payouts, or when the status last changed for complete and failed payouts, as an RFC 3339 date-time in UTC. Null when unknown.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'          => array(
					'type'        => 'string',
					'enum'        => PayoutStatus::get_all(),
					'description' => esc_html__( 'The high-level payout status.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'provider_status' => array(
					'type'        => array( 'string', 'null' ),
					'description' => esc_html__( 'The provider-specific payout status, or null when not reported.', 'woocommerce' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Get the schema of a currency code field.
	 *
	 * @return array
	 */
	private function get_currency_schema(): array {
		return array(
			'type'        => 'string',
			'pattern'     => '^[A-Z]{3}$',
			'description' => esc_html__( 'The three-letter ISO 4217 currency code.', 'woocommerce' ),
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
		);
	}
}
