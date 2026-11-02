<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal\Emails;

use Automattic\WooCommerce\Internal\OrderWithdrawal\OrderWithdrawalFormProcessor;

/**
 * Formats order withdrawal email data for templates.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalEmailDataFormatter {

	/**
	 * Normalize and validate submitted withdrawal data before rendering emails.
	 *
	 * @param array<string,mixed> $data Form data.
	 * @return array<string,string>|null
	 */
	public function normalize_withdrawal_data( array $data ): ?array {
		$required_fields = array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME,
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME,
			OrderWithdrawalFormProcessor::FIELD_EMAIL,
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER,
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE,
			OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS,
		);

		foreach ( $required_fields as $field ) {
			if ( ! isset( $data[ $field ] ) || ! is_scalar( $data[ $field ] ) ) {
				return null;
			}
		}

		$normalized_data = array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => sanitize_text_field( (string) $data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ] ),
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => sanitize_text_field( (string) $data[ OrderWithdrawalFormProcessor::FIELD_LAST_NAME ] ),
			OrderWithdrawalFormProcessor::FIELD_EMAIL      => sanitize_email( (string) $data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ] ),
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => sanitize_text_field( (string) $data[ OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER ] ),
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE => sanitize_text_field( (string) $data[ OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE ] ),
			OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS => sanitize_textarea_field( (string) $data[ OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS ] ),
		);

		if ( '' === $normalized_data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ] || ! is_email( $normalized_data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ] ) ) {
			return null;
		}

		if ( '' === $normalized_data[ OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER ] ) {
			return null;
		}

		if ( ! in_array( $normalized_data[ OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE ], array( OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_FULL, OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC ), true ) ) {
			return null;
		}

		return $normalized_data;
	}

	/**
	 * Get the customer's full name for display.
	 *
	 * @param array<string,string> $data Form data.
	 */
	public function get_customer_name( array $data ): string {
		return trim( ( $data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ] ?? '' ) . ' ' . ( $data[ OrderWithdrawalFormProcessor::FIELD_LAST_NAME ] ?? '' ) );
	}

	/**
	 * Get the label for a withdrawal type value.
	 *
	 * @param string $withdrawal_type Withdrawal type value.
	 */
	public function get_withdrawal_type_label( string $withdrawal_type ): string {
		$options = array(
			OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_FULL     => __( 'The full order', 'woocommerce' ),
			OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC => __( 'Specific items only', 'woocommerce' ),
		);

		return $options[ $withdrawal_type ] ?? '';
	}

	/**
	 * Get order withdrawal detail rows for email templates.
	 *
	 * @param array<string,string> $data         Form data.
	 * @param int                  $submitted_at Unix timestamp for the submission.
	 * @return array<string,string>
	 */
	public function get_detail_rows( array $data, int $submitted_at ): array {
		$date_format        = (string) get_option( 'date_format' );
		$time_format        = (string) get_option( 'time_format' );
		$additional_details = $data[ OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS ] ?? '';
		$additional_details = '' === $additional_details ? __( 'None provided', 'woocommerce' ) : $additional_details;
		$submitted_at_text  = wp_date( trim( $date_format . ' ' . $time_format ), $submitted_at );

		if ( false === $submitted_at_text ) {
			$submitted_at_text = '';
		}

		return array(
			__( 'Submitted', 'woocommerce' )          => $submitted_at_text,
			__( 'Name', 'woocommerce' )               => $this->get_customer_name( $data ),
			__( 'Email address', 'woocommerce' )      => $data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ] ?? '',
			__( 'Order number', 'woocommerce' )       => $data[ OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER ] ?? '',
			__( 'Withdrawing', 'woocommerce' )        => $this->get_withdrawal_type_label( $data[ OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE ] ?? '' ),
			__( 'Additional details', 'woocommerce' ) => $additional_details,
		);
	}
}
