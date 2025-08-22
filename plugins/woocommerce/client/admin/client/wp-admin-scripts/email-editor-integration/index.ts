// This file acts as a way of adding JS integration support for the email editor package

/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { initializeEditor } from '@woocommerce/email-editor';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';
import { modifyTemplateSidebar } from './templates';
import { modifySidebar } from './sidebar_settings';
import { registerEmailValidationRules } from './email-validation';

import './style.scss';

addFilter( 'woocommerce_email_editor_send_button_label', NAME_SPACE, () =>
	__( 'Save email', 'woocommerce' )
);

addFilter(
	'woocommerce_email_editor_check_sending_method_configuration_link',
	NAME_SPACE,
	() => 'https://woocommerce.com/document/email-faq/'
);

// Add filter to permanently delete emails.
// This is used to delete email posts from the database instead of moving them to the trash.
// The email posts can be recreated from the WooCommerce settings email listing page.
addFilter(
	'woocommerce_email_editor_trash_modal_should_permanently_delete',
	NAME_SPACE,
	() => true
);

modifySidebar();
modifyTemplateSidebar();
registerEmailValidationRules();
initializeEditor( 'woocommerce-email-editor' );
