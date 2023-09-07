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

export const SidebarNavigationScreenColorPalette = () => {
	return (
		<SidebarNavigationScreen
			title={ __( 'Change the color palette', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					'Based on the info you shared, our AI tool recommends using this color palette. Want to change it? You can select or add new colors below, or update them later in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.',
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							href={ `${ ADMIN_URL }/site-editor.php` }
							type="external"
						/>
					),
					StyleLink: (
						<Link
							href={ `${ ADMIN_URL }/site-editor.php?path=%2Fwp_global_styles&canvas=edit` }
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
