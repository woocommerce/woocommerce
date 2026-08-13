<?php
/**
 * Fake third-party email for E2E scenarios 15-16.
 *
 * Registered conditionally via the woocommerce_email_classes filter in the
 * main plugin file. Stays dormant unless wc_test_fake_third_party_email_enabled='yes'.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

namespace WC_Email_Template_Sync_Test_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal WC_Email subclass standing in for a real third-party transactional email.
 *
 * Real third-party plugins must subclass WC_Email; the registry, post generator, and
 * divergence detector all call methods on a live WC_Email instance, so filter-only
 * registration is insufficient. This fixture provides the minimum surface those classes
 * need (id, title, description, template paths, basic subject/heading defaults) so that
 * scenarios 15 (non-opted-in) and 16 (opted-in with explicit version) can exercise the
 * full sync pipeline.
 */
class Fake_Third_Party_Email extends \WC_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'fake_thirdparty';
		$this->title       = 'Fake third-party email (test fixture)';
		$this->description = 'E2E fixture for RSM-146 scope tests. Do not enable in production.';

		// Safe fallback to an existing core template so that any rendering paths that
		// reach the file loader don't fail with a missing-template error.
		$this->template_html  = 'emails/customer-new-account.php';
		$this->template_plain = 'emails/plain/customer-new-account.php';
		$this->customer_email = true;

		parent::__construct();
	}

	/**
	 * Default subject (overridable via merchant settings, but tests don't touch it).
	 */
	public function get_default_subject(): string {
		return 'Fake third-party email subject';
	}

	/**
	 * Default heading (overridable via merchant settings, but tests don't touch it).
	 */
	public function get_default_heading(): string {
		return 'Fake third-party email heading';
	}
}
