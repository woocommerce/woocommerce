/**
 * External dependencies
 */
import { MenuItem, VisuallyHidden } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { external } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

export const HelpMenuItem = () => {
	return (
		<MenuItem
			role="menuitem"
			icon={ external }
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore href is okay here
			href={ __(
				'https://wordpress.org/documentation/article/wordpress-block-editor/',
				'woocommerce'
			) }
			target="_blank"
			rel="noopener noreferrer"
		>
			{ __( 'Help', 'woocommerce' ) }
			<VisuallyHidden as="span">
				{
					/* translators: accessibility text */
					__( '(opens in a new tab)', 'woocommerce' )
				}
			</VisuallyHidden>
		</MenuItem>
	);
};
