/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';

export const SidebarNavigationScreenPages = () => {
	return (
		<SidebarNavigationScreen
			title={ __( 'Add more pages', 'woocommerce' ) }
			description={ __(
				"Enhance your customers' experience by customizing existing pages or adding new ones. You can continue customizing and adding pages later in Editor.",
				'woocommerce'
			) }
			content={ <></> }
		/>
	);
};
