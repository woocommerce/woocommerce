/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
// @ts-ignore no types exist yet.
import { BlockEditorProvider } from '@wordpress/block-editor';
import { noop } from 'lodash';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
import { PanelBody } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { ColorPalette, ColorPanel } from './global-styles';

const SidebarNavigationScreenColorPaletteContent = () => {
	const { storedSettings } = useSelect( ( select ) => {
		const { getSettings } = unlock( select( editSiteStore ) );

		return {
			storedSettings: getSettings( false ),
		};
	}, [] );

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
				animation: 'containerFadeIn 1000ms ease-in-out forwards',
			} }
		>
			<BlockEditorProvider
				settings={ storedSettings }
				onChange={ noop }
				onInput={ noop }
			>
				<ColorPalette />

				<PanelBody
					className="woocommerce-customize-store__color-panel-container"
					title={ __( 'or create your own', 'woocommerce' ) }
					initialOpen={ false }
				>
					<ColorPanel />
				</PanelBody>
			</BlockEditorProvider>
		</div>
	);
};

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
							onClick={ () => {
								recordEvent(
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
								recordEvent(
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
				}
			) }
			content={ <SidebarNavigationScreenColorPaletteContent /> }
		/>
	);
};
