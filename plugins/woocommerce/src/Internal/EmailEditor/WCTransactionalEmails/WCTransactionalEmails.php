<?php

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

class WCTransactionalEmails {

	public static $core_transactional_emails = array(
		'cancelled_order',
		'customer_completed_order',
		'customer_failed_order',
		'customer_invoice',
		'customer_new_account',
		'customer_note',
		'customer_on_hold_order',
		'customer_processing_order',
		'customer_refunded_order',
		'customer_reset_password',
		'failed_order',
		'new_order',
	);

	public function init() {
		$this->init_email_templates();
	}

	public function init_email_templates() {
		$is_wc_email_settings_page = is_admin()
        && isset($_GET['page'], $_GET['tab'])
        && $_GET['page'] === 'wc-settings'
        && $_GET['tab'] === 'email';

		if ( $is_wc_email_settings_page ) {
			$email_template_generator = new WCEmailTemplateGenerator();
			$email_template_generator->init();
		}
	}
}