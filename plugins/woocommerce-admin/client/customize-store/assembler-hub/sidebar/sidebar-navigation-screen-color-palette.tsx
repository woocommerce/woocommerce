/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useContext } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { PanelBody } from '@wordpress/components';
// @ts-ignore No types for this exist yet.
import { privateApis as blockEditorPrivateApis } from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { CustomizeStoreContext } from '../';
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { ColorPalette, ColorPanel } from './global-styles';
import { FlowType } from '~/customize-store/types';
import { trackEvent } from '~/customize-store/tracking';

const { GlobalStylesContext } = unlock( blockEditorPrivateApis );

const SidebarNavigationScreenColorPaletteContent = () => {
	// @ts-ignore No types for this exist yet.
	const { user } = useContext( GlobalStylesContext );
	const hasCreatedOwnColors = !! (
		user.settings.color && user.settings.color.palette.hasCreatedOwnColors
	);
	// Wrap in a BlockEditorProvider to ensure that the Iframe's dependencies are
	// loaded. This is necessary because the Iframe component waits until
	// the block editor store's `__internalIsInitialized` is true before
	// rendering the iframe. Without this, the iframe previews will not render
	// in mobile viewport sizes, where the editor canvas is hidden.
	return (
		<div
			className="woocommerce-customize-store_sidebar-color-content"
			style={ {
				opacity: 0,
				animation: 'containerFadeIn 300ms ease-in-out forwards',
			} }
		>
			<ColorPalette />
			<PanelBody
				className="woocommerce-customize-store__color-panel-container"
				title={ __( 'or create your own', 'woocommerce' ) }
				initialOpen={ hasCreatedOwnColors }
			>
				<ColorPanel />
			</PanelBody>
		</div>
	);
};

export const SidebarNavigationScreenColorPalette = () => {
	const {
		context: { flowType },
	} = useContext( CustomizeStoreContext );

	const aiOnline = flowType === FlowType.AIOnline;

	const title = aiOnline
		? __( 'Change the color palette', 'woocommerce' )
		: __( 'Choose your color palette', 'woocommerce' );
	const description = aiOnline
		? __(
				'Based on the info you shared, our AI tool recommends using this color palette. Want to change it? You can select or add new colors below, or update them later in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.',
				'woocommerce'
		  )
		: __(
				'Choose the color palette that best suits your brand. Want to change it? Create your custom color palette below, or update it later in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.',
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ title }
			description={ createInterpolateElement( description, {
				EditorLink: (
					<Link
						onClick={ () => {
							trackEvent(
								'customize_your_store_assembler_hub_editor_link_click',
								{
									source: 'color-palette',
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
				StyleLink: (
					<Link
						onClick={ () => {
							trackEvent(
								'customize_your_store_assembler_hub_style_link_click',
								{
									source: 'color-palette',
								}
							);
							window.open(
								`${ ADMIN_URL }site-editor.php?path=%2Fwp_global_styles&canvas=edit`,
								'_blank'
							);
							return false;
						} }
						href=""
					/>
				),
			} ) }
			content={ <SidebarNavigationScreenColorPaletteContent /> }
		/>
	);
};
