/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { addFilterForEmail } from './config-tools';

/**
 * Replace text in the email editor.
 * This is used to contextualize some default texts to the email editing context
 */
export const initTextHooks = (): void => {
	const replaceTextMatrix = {
		'You’ve tried to select a block that is part of a template that may be used elsewhere on your site. Would you like to edit the template?':
			{
				domain: 'default',
				replacementText: __(
					'You’ve tried to select a block that is part of a template that may be used in other emails. Would you like to edit the template?',
					'woocommerce'
				),
			},
	};

	addFilterForEmail(
		'i18n.gettext',
		'woocommerce/email-editor/override-text',
		( translation, text, domain ) => {
			if (
				replaceTextMatrix[ text ] &&
				replaceTextMatrix[ text ].domain === ( domain || 'default' )
			) {
				return replaceTextMatrix[ text ].replacementText;
			}
			return translation;
		}
	);
};
