/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useContext } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { FontPairing } from './global-styles';
import { CustomizeStoreContext } from '..';
import { FlowType } from '~/customize-store/types';

export const SidebarNavigationScreenTypography = () => {
	const { context } = useContext( CustomizeStoreContext );
	const aiOnline = context.flowType === FlowType.AIOnline;

	const label = aiOnline
		? __(
				"AI has selected a font pairing that's the best fit for your business. If you'd like to change them, select a new option below now, or later in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.",
				'woocommerce'
		  )
		: __(
				'Select the pair of fonts that best suits your brand. The larger font will be used for headings, and the smaller for supporting content. You can change your font at any time in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.',
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ __( 'Change your font', 'woocommerce' ) }
			description={ createInterpolateElement( label, {
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
								`${ ADMIN_URL }site-editor.php?path=%2Fwp_global_styles`,
								'_blank'
							);
							return false;
						} }
						href=""
					/>
				),
			} ) }
			content={
				<div className="woocommerce-customize-store_sidebar-typography-content">
					<FontPairing />
				</div>
			}
		/>
	);
};
