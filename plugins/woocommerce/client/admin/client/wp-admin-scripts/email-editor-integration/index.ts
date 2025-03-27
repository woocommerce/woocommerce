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

addFilter( 'woocommerce_email_editor_send_button_label', NAME_SPACE, () =>
	__( 'Save email', 'woocommerce' )
);

addFilter(
	'woocommerce_email_editor_check_sending_method_configuration_link',
	NAME_SPACE,
	() => 'https://woocommerce.com/document/email-faq/'
);

registerBlockType( 'woo/email-content', wooContentPlaceholderBlock );
