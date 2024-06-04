<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;

class ConfigureSettingsAccountPrivacy extends MapFieldsToOptions implements StepProcessor {
	protected array $options_map = array(
		"allow_customers_to_place_orders_without_an_account"=> "woocommerce_enable_guest_checkout",
		"allow_customers_to_log_into_an_existing_account_during_checkout"=> "woocommerce_enable_checkout_login_reminder",
		"allow_customers_to_create_an_account_during_checkout"=> "woocommerce_enable_signup_and_login_from_checkout",
		"allow_customers_to_create_account_on_the_my_account_page"=> "woocommerce_enable_myaccount_registration",
		"when_creating_an_account_automatically_generate_an_account_username_for_the_customer_based_on_their_name_surname_or_email"=> "woocommerce_registration_generate_username",
		"when_creating_an_account_send_the_new_user_a_link_to_set_their_password"=> "woocommerce_registration_generate_password",
		"remove_personal_data_from_orders_on_request"=> "woocommerce_erasure_request_removes_order_data",
		"remove_access_to_downloads_on_request"=> "woocommerce_erasure_request_removes_download_data",
		"allow_personal_data_to_be_removed_in_bulk_from_orders"=> "woocommerce_allow_bulk_remove_personal_data",
		"registration_privacy_policy"=> "woocommerce_registration_privacy_policy_text",
		"checkout_privacy_policy"=> "woocommerce_checkout_privacy_policy_text",
		"retain_inactive_accounts"=> "woocommerce_delete_inactive_accounts",
		"retain_pending_orders"=> "woocommerce_trash_pending_orders",
		"retain_failed_orders"=> "woocommerce_trash_failed_orders",
		"retain_cancelled_orders"=> "woocommerce_trash_cancelled_orders",
		"retain_completed_orders"=> "woocommerce_anonymize_completed_orders"

	);

	protected function provide_fields( $schema ): array {
		$fields = parent::provide_fields( $schema );
		unset($fields['number']);
		unset($fields['unit']);

		if (isset($schema->personal_data_retention)) {
			foreach ($schema->personal_data_retention as $retention_field => $value) {
				$fields[$retention_field] = (array) $value;
			}
		}

		return $fields;
	}


	public function process( $schema ): StepProcessorResult {
		parent::process($schema);

		return StepProcessorResult::success();
	}
}
