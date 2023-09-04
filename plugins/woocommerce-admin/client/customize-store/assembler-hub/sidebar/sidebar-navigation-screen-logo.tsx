/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';

export const SidebarNavigationScreenLogo = () => {
	return (
		<SidebarNavigationScreen
			title={ __( 'Add your logo', 'woocommerce' ) }
			description={ __(
				"Ensure your store is on-brand by adding your logo. For best results, upload a SVG or PNG that's a minimum of 300px wide.",
				'woocommerce'
			) }
			content={
				<>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-header"></div>
				</>
			}
		/>
	);
};
