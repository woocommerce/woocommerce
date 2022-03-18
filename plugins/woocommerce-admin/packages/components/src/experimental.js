/**
 * External dependencies
 */
import {
	__experimentalText,
	Text as TextComponent,
} from '@wordpress/components';

/**
 * Export experimental components within the components package to prevent a circular
 * dependency with woocommerce/experimental. Only for internal use.
 */
export const Text = TextComponent || __experimentalText;
