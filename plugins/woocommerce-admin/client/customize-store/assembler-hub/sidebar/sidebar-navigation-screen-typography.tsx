/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useContext } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Link } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
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
	const isFontLibraryAvailable = context.isFontLibraryAvailable;

	const label = aiOnline
		? __(
				"AI has selected a font pairing that's the best fit for your business. If you'd like to change them, select a new option below now, or later in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.",
				'woocommerce'
		  )
		: __(
				'Select the pair of fonts that best suits your brand. The larger font will be used for headings, and the smaller for supporting content. You can change your font at any time in <EditorLink>Editor</EditorLink> | <StyleLink>Styles</StyleLink>.',
				'woocommerce'
		  );

	const trackingAllowed = useSelect( ( select ) =>
		select( OPTIONS_STORE_NAME ).getOption( 'woocommerce_allow_tracking' )
	);

	let upgradeNotice;

	if ( ! trackingAllowed && ! isFontLibraryAvailable ) {
		upgradeNotice = __(
			'Upgrade to the <WordPressLink>latest version of WordPress</WordPressLink> and opt in to usage tracking to get access to more fonts.',
			'woocommerce'
		);
	} else if ( ! trackingAllowed && isFontLibraryAvailable ) {
		upgradeNotice = __(
			'Opt in to <OptInModal>usage tracking</OptInModal> to get access to more fonts.',
			'woocommerce'
		);
	} else if ( trackingAllowed && ! isFontLibraryAvailable ) {
		upgradeNotice = __(
			'Upgrade to the <WordPressLink>latest version of WordPress</WordPressLink> or install <GutenbergLink>Gutenberg</GutenbergLink> to get access to more fonts.',
			'woocommerce'
		);
	} else {
		upgradeNotice = '';
	}

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
					{ upgradeNotice && (
						<p className="woocommerce-customize-store_sidebar-typography-upgrade-notice">
							{ createInterpolateElement( upgradeNotice, {
								WordPressLink: (
									<Link
										href="https://wordpress.org/download/"
										target="_blank"
										type="external"
									/>
								),
								GutenbergLink: (
									<Link
										href="https://wordpress.org/plugins/gutenberg/"
										target="_blank"
										type="external"
									/>
								),
								OptInModal: (
									<Link
										onClick={ () => {
											recordEvent(
												'customize_your_store_assembler_hub_opt_in_modal_click',
												{
													source: 'typography',
												}
											);
										} }
										href=""
									>
										{ __( 'usage tracking' ) }
									</Link>
								),
							} ) }
						</p>
					) }
				</div>
			}
		/>
	);
};
