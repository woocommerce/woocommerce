<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderWithdrawal;

/**
 * Prepares order withdrawal template data.
 *
 * @internal Just for internal use.
 */
final class OrderWithdrawalFormView {

	/**
	 * Get template arguments for the order withdrawal form.
	 *
	 * @param OrderWithdrawalFormState $state Processed form state.
	 * @param string                   $form_action_url Form action URL.
	 * @param string                   $shop_url Shop URL.
	 * @return array<string,mixed>
	 *
	 * @since 11.1.0
	 */
	public function get_template_args( OrderWithdrawalFormState $state, string $form_action_url, string $shop_url ): array {
		return array(
			'screen'                  => $state->screen,
			'data'                    => $state->data,
			'errors'                  => $state->errors,
			'fields'                  => $this->get_prepared_form_fields( $state->errors ),
			'withdrawal_type_options' => $this->get_withdrawal_type_options(),
			'hidden_fields'           => $this->get_hidden_fields( $state->data ),
			'review_rows'             => $this->get_review_rows( $state->data ),
			'nonce_action'            => OrderWithdrawalFormProcessor::NONCE_ACTION,
			'nonce_field'             => OrderWithdrawalFormProcessor::NONCE_FIELD,
			'action_field'            => OrderWithdrawalFormProcessor::ACTION_FIELD,
			'action_review'           => OrderWithdrawalFormProcessor::ACTION_REVIEW,
			'action_confirm'          => OrderWithdrawalFormProcessor::ACTION_CONFIRM,
			'action_edit'             => OrderWithdrawalFormProcessor::ACTION_EDIT,
			'form_action_url'         => $form_action_url,
			'shop_url'                => $shop_url,
		);
	}

	/**
	 * Get form field definitions prepared for the template.
	 *
	 * @param array<string,string> $errors Validation errors keyed by field.
	 * @return array<string,array<string,mixed>>
	 */
	private function get_prepared_form_fields( array $errors ): array {
		$fields = array();

		foreach ( $this->get_form_field_schema() as $field_key => $field ) {
			$field['name']        = OrderWithdrawalFormProcessor::get_field_name( $field_key );
			$field['id']          = OrderWithdrawalFormProcessor::get_field_name( $field_key );
			$field['input_class'] = array( 'woocommerce-Input', 'woocommerce-Input--' . (string) $field['type'] );

			if ( isset( $errors[ $field_key ] ) ) {
				$field['class'][]                                = 'woocommerce-invalid';
				$field['custom_attributes']['aria-invalid']      = 'true';
				$field['custom_attributes']['aria-errormessage'] = OrderWithdrawalFormProcessor::get_field_name( $field_key ) . '_error';
			}

			if ( OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS === $field_key ) {
				$field['custom_attributes']['rows'] = '5';
			}

			$fields[ $field_key ] = $field;
		}

		return $fields;
	}

	/**
	 * Get hidden fields for review actions.
	 *
	 * @param array<string,string> $data Form data.
	 * @return array<int,array{name:string,value:string}>
	 */
	private function get_hidden_fields( array $data ): array {
		$hidden_fields = array();

		foreach ( $data as $field_key => $value ) {
			$hidden_fields[] = array(
				'name'  => OrderWithdrawalFormProcessor::get_field_name( $field_key ),
				'value' => $value,
			);
		}

		return $hidden_fields;
	}

	/**
	 * Get rows for the review screen.
	 *
	 * @param array<string,string> $data Form data.
	 * @return array<int,array{label:string,value:string}>
	 */
	private function get_review_rows( array $data ): array {
		return array(
			array(
				'label' => __( 'Name', 'woocommerce' ),
				'value' => $this->get_customer_name( $data ),
			),
			array(
				'label' => __( 'Email address', 'woocommerce' ),
				'value' => $data[ OrderWithdrawalFormProcessor::FIELD_EMAIL ],
			),
			array(
				'label' => __( 'Order number', 'woocommerce' ),
				'value' => $data[ OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER ],
			),
			array(
				'label' => __( 'Withdrawing', 'woocommerce' ),
				'value' => $this->get_withdrawal_type_label( $data[ OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE ] ),
			),
			array(
				'label' => __( 'Additional details', 'woocommerce' ),
				'value' => '' === $data[ OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS ] ? __( 'None provided', 'woocommerce' ) : $data[ OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS ],
			),
		);
	}

	/**
	 * Get the form field definitions for the template.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_form_field_schema(): array {
		return array(
			OrderWithdrawalFormProcessor::FIELD_FIRST_NAME => array(
				'label'        => __( 'First name', 'woocommerce' ),
				'type'         => 'text',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--first', 'form-row-first' ),
				'autocomplete' => 'given-name',
				'required'     => true,
			),
			OrderWithdrawalFormProcessor::FIELD_LAST_NAME  => array(
				'label'        => __( 'Last name', 'woocommerce' ),
				'type'         => 'text',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--last', 'form-row-last' ),
				'autocomplete' => 'family-name',
				'required'     => true,
			),
			OrderWithdrawalFormProcessor::FIELD_EMAIL      => array(
				'label'        => __( 'Email address', 'woocommerce' ),
				'type'         => 'email',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'autocomplete' => 'email',
				'required'     => true,
			),
			OrderWithdrawalFormProcessor::FIELD_EMAIL_CONFIRMATION => array(
				'label'        => __( 'Confirm email address', 'woocommerce' ),
				'type'         => 'email',
				'class'        => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'autocomplete' => 'email',
				'required'     => true,
			),
			OrderWithdrawalFormProcessor::FIELD_ORDER_NUMBER => array(
				'label'    => __( 'Order number', 'woocommerce' ),
				'type'     => 'text',
				'class'    => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'required' => true,
			),
			OrderWithdrawalFormProcessor::FIELD_WITHDRAWAL_TYPE => array(
				'label'    => __( 'What do you want to withdraw?', 'woocommerce' ),
				'type'     => 'radio',
				'required' => true,
			),
			OrderWithdrawalFormProcessor::FIELD_ADDITIONAL_DETAILS => array(
				'label'    => __( 'Additional details', 'woocommerce' ),
				'type'     => 'textarea',
				'class'    => array( 'woocommerce-form-row', 'woocommerce-form-row--wide', 'form-row-wide' ),
				'required' => false,
			),
		);
	}

	/**
	 * Get the available withdrawal type options.
	 *
	 * @return array<string,string>
	 */
	private function get_withdrawal_type_options(): array {
		return array(
			OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_FULL     => __( 'The full order', 'woocommerce' ),
			OrderWithdrawalFormProcessor::WITHDRAWAL_TYPE_SPECIFIC => __( 'Specific items only', 'woocommerce' ),
		);
	}

	/**
	 * Get the label for a withdrawal type value.
	 *
	 * @param string $withdrawal_type Withdrawal type value.
	 */
	private function get_withdrawal_type_label( string $withdrawal_type ): string {
		$options = $this->get_withdrawal_type_options();

		return $options[ $withdrawal_type ] ?? '';
	}

	/**
	 * Get the customer's full name for display.
	 *
	 * @param array<string,string> $data Form data.
	 */
	private function get_customer_name( array $data ): string {
		return trim( $data[ OrderWithdrawalFormProcessor::FIELD_FIRST_NAME ] . ' ' . $data[ OrderWithdrawalFormProcessor::FIELD_LAST_NAME ] );
	}
}
