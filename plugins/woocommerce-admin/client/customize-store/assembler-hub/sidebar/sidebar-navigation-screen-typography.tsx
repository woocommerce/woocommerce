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
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { FontPairing } from './global-styles';

export const SidebarNavigationScreenTypography = () => {
	const { storedSettings } = useSelect( ( select ) => {
		const { getSettings } = unlock( select( editSiteStore ) );

		return {
			storedSettings: getSettings( false ),
		};
	}, [] );

	return (
		<SidebarNavigationScreen
			title={ __( 'Change your font', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					"AI has selected a font pairing that's the best fit for your business. If you'd like to change them, select a new option below now, or later in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.",
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							onClick={ () => {
								recordEvent(
									'customize_your_store_assembler_hub_editor_link_click',
									{
										source: 'typography',
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
										source: 'typography',
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
			content={
				<div className="woocommerce-customize-store_sidebar-typography-content">
					<BlockEditorProvider
						settings={ storedSettings }
						onChange={ noop }
						onInput={ noop }
					>
						<FontPairing />
					</BlockEditorProvider>
				</div>
			}
		/>
	);
};
