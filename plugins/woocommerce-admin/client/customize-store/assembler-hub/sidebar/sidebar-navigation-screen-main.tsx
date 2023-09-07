/**
 * WordPress dependencies
 */
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
import { createInterpolateElement } from '@wordpress/element';
import {
	// @ts-ignore No types for this exist yet.
	__experimentalItemGroup as ItemGroup,
	// @ts-ignore No types for this exist yet.
	__experimentalNavigatorButton as NavigatorButton,
	// @ts-ignore No types for this exist yet.
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	siteLogo,
	color,
	typography,
	header,
	home,
	footer,
	pages,
} from '@wordpress/icons';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';

export const SidebarNavigationScreenMain = () => {
	return (
		<SidebarNavigationScreen
			isRoot
			title={ __( "Let's get creative", 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					'Use our style and layout tools to customize the design of your store. Content and images can be added or changed via the <EditorLink>Editor</EditorLink> later.',
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
					<div className="edit-site-sidebar-navigation-screen-patterns__group-header">
						<Heading level={ 2 }>
							{ __( 'Style', 'woocommerce' ) }
						</Heading>
					</div>
					<ItemGroup>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/logo"
							withChevron
							icon={ siteLogo }
						>
							{ __( 'Add your logo', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/color-palette"
							withChevron
							icon={ color }
						>
							{ __( 'Change the color palette', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/typography"
							withChevron
							icon={ typography }
						>
							{ __( 'Change fonts', 'woocommerce' ) }
						</NavigatorButton>
					</ItemGroup>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-header">
						<Heading level={ 2 }>
							{ __( 'Layout', 'woocommerce' ) }
						</Heading>
					</div>
					<ItemGroup>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/header"
							withChevron
							icon={ header }
						>
							{ __( 'Change your header', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/homepage"
							withChevron
							icon={ home }
						>
							{ __( 'Change your homepage', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/footer"
							withChevron
							icon={ footer }
						>
							{ __( 'Change your footer', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/pages"
							withChevron
							icon={ pages }
						>
							{ __( 'Add and edit other pages', 'woocommerce' ) }
						</NavigatorButton>
					</ItemGroup>
				</>
			}
		/>
	);
};
