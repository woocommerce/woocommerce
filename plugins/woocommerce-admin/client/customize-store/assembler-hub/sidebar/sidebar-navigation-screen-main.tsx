/**
 * WordPress dependencies
 */
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
import { createInterpolateElement, useContext } from '@wordpress/element';
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

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { CustomizeStoreContext } from '~/customize-store/assembler-hub';
import { FlowType } from '~/customize-store/types';
import { trackEvent } from '~/customize-store/tracking';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import {
	SidebarNavigationAnimationDirection,
	SidebarNavigationContext,
} from '../components/sidebar';
import { isFullComposabilityFeatureAndAPIAvailable } from '../utils/is-full-composability-enabled';

export const SidebarNavigationScreenMain = () => {
	const {
		context: { flowType },
	} = useContext( CustomizeStoreContext );
	const aiOnline = flowType === FlowType.AIOnline;
	const { navigate } = useContext( SidebarNavigationContext );

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
								trackEvent(
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
								const logoUrl = getNewPath(
									{ customizing: true },
									'/customize-store/assembler-hub/logo',
									{}
								);

								navigateTo( { url: logoUrl } );
								navigate(
									SidebarNavigationAnimationDirection.Forward
								);
								trackEvent(
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
								const colorPaletteUrl = getNewPath(
									{ customizing: true },
									'/customize-store/assembler-hub/color-palette',
									{}
								);

								navigateTo( { url: colorPaletteUrl } );
								navigate(
									SidebarNavigationAnimationDirection.Forward
								);
								trackEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'color-palette',
									}
								);
							} }
						>
							{ aiOnline
								? __(
										'Change the color palette',
										'woocommerce'
								  )
								: __(
										'Choose your color palette',
										'woocommerce'
								  ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/typography"
							withChevron
							icon={ typography }
							onClick={ () => {
								const typographyUrl = getNewPath(
									{ customizing: true },
									'/customize-store/assembler-hub/typography',
									{}
								);

								navigateTo( { url: typographyUrl } );
								navigate(
									SidebarNavigationAnimationDirection.Forward
								);
								trackEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'typography',
									}
								);
							} }
						>
							{ aiOnline
								? __( 'Change fonts', 'woocommerce' )
								: __( 'Choose fonts', 'woocommerce' ) }
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
								const headerUrl = getNewPath(
									{ customizing: true },
									'/customize-store/assembler-hub/header',
									{}
								);

								navigateTo( { url: headerUrl } );
								navigate(
									SidebarNavigationAnimationDirection.Forward
								);
								trackEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'header',
									}
								);
							} }
						>
							{ aiOnline
								? __( 'Change your header', 'woocommerce' )
								: __( 'Choose your header', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/homepage"
							withChevron
							icon={ home }
							onClick={ () => {
								const homepageUrl =
									isFullComposabilityFeatureAndAPIAvailable()
										? getNewPath(
												{ customizing: true },
												'/customize-store/assembler-hub/homepage/intro',
												{}
										  )
										: getNewPath(
												{ customizing: true },
												'/customize-store/assembler-hub/homepage',
												{}
										  );

								navigateTo( { url: homepageUrl } );
								navigate(
									SidebarNavigationAnimationDirection.Forward
								);
								trackEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'home',
									}
								);
							} }
						>
							{ aiOnline
								? __( 'Change your homepage', 'woocommerce' )
								: __( 'Design your homepage', 'woocommerce' ) }
						</NavigatorButton>
						<NavigatorButton
							as={ SidebarNavigationItem }
							path="/customize-store/assembler-hub/footer"
							withChevron
							icon={ footer }
							onClick={ () => {
								const footerUrl = getNewPath(
									{ customizing: true },
									'/customize-store/assembler-hub/footer',
									{}
								);

								navigateTo( { url: footerUrl } );
								navigate(
									SidebarNavigationAnimationDirection.Forward
								);
								trackEvent(
									'customize_your_store_assembler_hub_sidebar_item_click',
									{
										item: 'footer',
									}
								);
							} }
						>
							{ aiOnline
								? __( 'Change your footer', 'woocommerce' )
								: __( 'Choose your footer', 'woocommerce' ) }
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
