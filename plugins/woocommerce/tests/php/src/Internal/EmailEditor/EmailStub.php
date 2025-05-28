<?php

declare(strict_types = 1);

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

// Make sure WC_Email class exists.
if ( ! class_exists( \WC_Email::class ) ) {
	require_once WC_ABSPATH . 'includes/emails/class-wc-email.php';
}

/**
 * Email class for testing purposes.
 */
class EmailStub extends \WC_Email {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'test_email';
		$this->title          = 'Test Email';
		$this->description    = 'This is a test email for unit testing';
		$this->template_html  = 'emails/test-email.php';
		$this->template_plain = 'emails/plain/test-email.php';
		$this->placeholders   = array();

		parent::__construct();
	}

	/**
	 * Get form fields.
	 *
	 * @return array
	 */
	public function get_form_fields(): array {
		return array(
			'recipient' => array(
				'default' => 'test@example.com',
			),
		);
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return 'Default Subject';
	}
}
