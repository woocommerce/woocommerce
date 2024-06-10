/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import {
	// @ts-ignore No types for this exist yet.
	__experimentalItemGroup as ItemGroup,
	// @ts-ignore No types for this exist yet.
	__experimentalNavigatorButton as NavigatorButton,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/components';
import { createInterpolateElement, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
// @ts-expect-error Missing type.
import SidebarNavigationItem from '@wordpress/edit-site/build-module/components/sidebar-navigation-item';

/**
 * Internal dependencies
 */
import { ADMIN_URL } from '~/utils/admin-settings';
import { SidebarNavigationScreen } from './sidebar-navigation-screen';

import { trackEvent } from '~/customize-store/tracking';
import { FlowType } from '~/customize-store/types';
import { CustomizeStoreContext } from '..';
import { Link } from '@woocommerce/components';
import { PATTERN_CATEGORIES } from './pattern-screen/categories';
import { capitalize } from 'lodash';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

export const SidebarNavigationScreenHomepagePTK = () => {
	const { context } = useContext( CustomizeStoreContext );

	const aiOnline = context.flowType === FlowType.AIOnline;

	const title = aiOnline
		? __( 'Change your homepage', 'woocommerce' )
		: __( 'Choose your homepage', 'woocommerce' );
	const sidebarMessage = aiOnline
		? __(
				'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the <EditorLink>Editor</EditorLink>.',
				'woocommerce'
		  )
		: __(
				'Create an engaging homepage by selecting one of our pre-designed layouts. You can continue customizing this page, including the content, later via the <EditorLink>Editor</EditorLink>.',
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ title }
			description={ createInterpolateElement( sidebarMessage, {
				EditorLink: (
					<Link
						onClick={ () => {
							trackEvent(
								'customize_your_store_assembler_hub_editor_link_click',
								{
									source: 'homepage',
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
			} ) }
			content={
				<div className="woocommerce-customize-store__sidebar-homepage-content">
					<div className="edit-site-sidebar-navigation-screen-patterns__group-homepage">
						{ Object.entries( PATTERN_CATEGORIES ).map(
							( [ categoryKey, { label } ], index ) => (
								<ItemGroup key={ index }>
									<NavigatorButton
										path={ `/customize-store/assembler-hub/homepage/${ categoryKey }` }
										onClick={ () => {
											const categoryUrl = getNewPath(
												{ customizing: true },
												`/customize-store/assembler-hub/homepage/${ categoryKey }`,
												{}
											);
											navigateTo( { url: categoryUrl } );
										} }
										as={ SidebarNavigationItem }
										withChevron
									>
										{ capitalize( label ) }
									</NavigatorButton>
								</ItemGroup>
							)
						) }
					</div>
				</div>
			}
		/>
	);
};
