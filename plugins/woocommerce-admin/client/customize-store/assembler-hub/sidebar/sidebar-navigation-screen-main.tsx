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
} from '@wordpress/icons';
// @ts-ignore No types for this exist yet.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

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
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_editor_link_click',
									{
										source: 'main',
									}
								);
								window.open(
									`${ ADMIN_URL }site-editor.php`,
									'_blank'
								);
								return false;
							} }
							href=""
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
							path="/customize-store/assembler-hub/logo"
							withChevron
							icon={ siteLogo }
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'logo',
									}
								);
							} }
						>
							{ __( 'Add your logo', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/color-palette"
							withChevron
							icon={ color }
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'color-palette',
									}
								);
							} }
						>
							{ __( 'Change the color palette', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/typography"
							withChevron
							icon={ typography }
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'typography',
									}
								);
							} }
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
							path="/customize-store/assembler-hub/header"
							withChevron
							icon={ header }
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'header',
									}
								);
							} }
						>
							{ __( 'Change your header', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/homepage"
							withChevron
							icon={ home }
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'home',
									}
								);
							} }
						>
							{ __( 'Change your homepage', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/footer"
							withChevron
							icon={ footer }
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'footer',
									}
								);
							} }
						>
							{ __( 'Change your footer', 'woocommerce' ) }
						</NavigatorButton>
						{ /* TODO: Turn on this in Phrase 2  */ }
						{ /* <NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/pages"
							withChevron
							icon={ pages }
						>
							{ __( 'Add and edit other pages', 'woocommerce' ) }
						</NavigatorButton> */ }
					</ItemGroup>
				</>
			}
		/>
	);
};
