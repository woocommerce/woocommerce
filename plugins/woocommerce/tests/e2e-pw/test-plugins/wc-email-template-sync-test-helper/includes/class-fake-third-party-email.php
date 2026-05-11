<?php
/**
 * Fake third-party email for E2E scenarios 15-16.
 *
 * Registered conditionally via the woocommerce_email_classes filter in the
 * main plugin file. Stays dormant unless wc_test_fake_third_party_email_enabled='yes'.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

namespace WC_Email_Template_Sync_Test_Helper;

defined( 'ABSPATH' ) || exit;

class Fake_Third_Party_Email extends \WC_Email {

	public function __construct() {
		$this->id          = 'fake_thirdparty';
		$this->title       = 'Fake third-party email (test fixture)';
		$this->description = 'E2E fixture for RSM-146 scope tests. Do not enable in production.';
		$this->template_html  = 'emails/customer-new-account.php';   // safe fallback existing template
		$this->template_plain = 'emails/plain/customer-new-account.php';
		$this->customer_email = true;

		parent::__construct();
	}

	public function get_default_subject(): string {
		return 'Fake third-party email subject';
	}

	public function get_default_heading(): string {
		return 'Fake third-party email heading';
	}
}
