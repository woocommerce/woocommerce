// This file acts as a way of adding JS integration support for the email editor package

/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { wooContentPlaceholderBlock } from './blocks/woo-email-content';
import { NAME_SPACE } from './constants';
import { modifyTemplateSidebar } from './templates';

// The type is copied from the email-editor package.
// When the type was imported from the email-editor package, the build failed due to more than 50 type errors.
type EmailContentValidationRule = {
	id: string;
	testContent: ( emailContent: string ) => boolean;
	message: string;
	actions: [];
};

addFilter( 'woocommerce_email_editor_send_button_label', NAME_SPACE, () =>
	__( 'Save email', 'woocommerce' )
);

// Add email validation rule
addFilter(
	'woocommerce_email_editor_content_validation_rules',
	NAME_SPACE,
	( rules: EmailContentValidationRule[] ) => {
		const emailValidationRule: EmailContentValidationRule = {
			id: 'sender-email-validation',
			testContent: () => {
				const input = document.querySelector< HTMLInputElement >(
					'input[name="from_email"]'
				);
				const email = input?.value;
				if ( ! email ) return false;

				return ! email || ! input?.checkValidity();
			},
			message: __(
				'The "from" email address is invalid. Please enter a valid email address that will appear as the sender in outgoing WooCommerce emails.',
				'woocommerce'
			),
			actions: [],
		};
		return [ ...( rules || [] ), emailValidationRule ];
	}
);

addFilter(
	'woocommerce_email_editor_check_sending_method_configuration_link',
	NAME_SPACE,
	() => 'https://woocommerce.com/document/email-faq/'
);

registerBlockType( 'woo/email-content', wooContentPlaceholderBlock );
modifyTemplateSidebar();
