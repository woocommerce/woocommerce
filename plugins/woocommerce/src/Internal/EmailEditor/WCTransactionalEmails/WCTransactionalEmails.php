<?php

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

class WCTransactionalEmails {

	public function __construct() {}

	public function init() {
		$this->init_email_templates();
	}

	public function init_email_templates() {
		$is_wc_email_settings_page = is_admin()
        && isset($_GET['page'])
        && $_GET['page'] === 'wc-settings'
        && isset($_GET['tab'])
        && $_GET['tab'] === 'email';

		if ( $is_wc_email_settings_page ) {
			$email_template_generator = new WCEmailTemplateGenerator();
			$email_template_generator->init();
		}
	}
}