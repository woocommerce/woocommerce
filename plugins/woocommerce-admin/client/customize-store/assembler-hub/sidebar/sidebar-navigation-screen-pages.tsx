/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';

export const SidebarNavigationScreenPages = () => {
	return (
		<SidebarNavigationScreen
			title={ __( 'Add more pages', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					"Enhance your customers' experience by customizing existing pages or adding new ones. You can continue customizing and adding pages later in <EditorLink>Editor</EditorLink> | <PageLink>Pages</PageLink>.",
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							href={ `${ ADMIN_URL }/site-editor.php` }
							type="external"
						/>
					),
					PageLink: (
						<Link
							href={ `${ ADMIN_URL }/edit.php?post_type=page` }
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
