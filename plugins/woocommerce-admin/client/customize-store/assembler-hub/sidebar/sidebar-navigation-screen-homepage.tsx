/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';

export const SidebarNavigationScreenHomepage = () => {
	return (
		<SidebarNavigationScreen
			title={ __( 'Change your homepage', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the <EditorLink>Editor</EditorLink>.',
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							href={ `${ ADMIN_URL }/site-editor.php` }
							type="external"
						/>
					),
				}
			) }
			content={
				<>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-header"></div>
				</>
			}
		/>
	);
};
