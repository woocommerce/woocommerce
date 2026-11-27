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
					__i18n_text_domain__
				),
			},
		// Shown by "Edit" in core's Template panel from WordPress 7.2.
		'Editing template. Changes made here affect all posts and pages that use the template.':
			{
				domain: 'default',
				replacementText: __(
					'Editing template. Changes made here affect all emails that use the template.',
					__i18n_text_domain__
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
