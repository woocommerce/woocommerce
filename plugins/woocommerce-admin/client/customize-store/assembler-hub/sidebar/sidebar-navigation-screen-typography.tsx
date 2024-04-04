/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useContext,
	useState,
} from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Link } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Button, Modal, CheckboxControl } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { FontPairing } from './global-styles';
import { CustomizeStoreContext } from '..';
import { FlowType } from '~/customize-store/types';
import { isIframe, sendMessageToParent } from '~/customize-store/utils';
export const SidebarNavigationScreenTypography = () => {
	const { context, sendEvent } = useContext( CustomizeStoreContext );
	const aiOnline = context.flowType === FlowType.AIOnline;
	const isFontLibraryAvailable = context.isFontLibraryAvailable;

	const title = aiOnline
		? __( 'Change your font', 'woocommerce' )
		: __( 'Choose fonts', 'woocommerce' );
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

	const isTrackingDisallowed = trackingAllowed === 'no' || ! trackingAllowed;
	let upgradeNotice;
	if ( isTrackingDisallowed && ! isFontLibraryAvailable ) {
		upgradeNotice = __(
			'Upgrade to the <WordPressLink>latest version of WordPress</WordPressLink> and <OptInModal>opt in to usage tracking</OptInModal> to get access to more fonts.',
			'woocommerce'
		);
	} else if ( isTrackingDisallowed && isFontLibraryAvailable ) {
		upgradeNotice = __(
			'Opt in to <OptInModal>usage tracking</OptInModal> to get access to more fonts.',
			'woocommerce'
		);
	} else if ( trackingAllowed && ! isFontLibraryAvailable ) {
		upgradeNotice = __(
			'Upgrade to the <WordPressLink>latest version of WordPress</WordPressLink> to get access to more fonts.',
			'woocommerce'
		);
	} else {
		upgradeNotice = '';
	}

	const optIn = () => {
		recordEvent(
			'customize_your_store_assembler_hub_opt_in_usage_tracking'
		);
	};

	const skipOptIn = () => {
		recordEvent(
			'customize_your_store_assembler_hub_skip_opt_in_usage_tracking'
		);
	};

	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = () => setIsModalOpen( true );
	const closeModal = () => setIsModalOpen( false );

	const [ OptInDataSharing, setIsOptInDataSharing ] =
		useState< boolean >( true );

	return (
		<SidebarNavigationScreen
			title={ title }
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
					{ isFontLibraryAvailable && <FontPairing /> }
					{ upgradeNotice && (
						<div className="woocommerce-customize-store_sidebar-typography-upgrade-notice">
							<h4>
								{ __(
									'Want more font pairings?',
									'woocommerce'
								) }
							</h4>
							<p>
								{ createInterpolateElement( upgradeNotice, {
									WordPressLink: (
										<Button
											href={ `${ ADMIN_URL }update-core.php` }
											variant="link"
										/>
									),
									OptInModal: (
										<Button
											onClick={ () => {
												openModal();
											} }
											variant="link"
										/>
									),
								} ) }
							</p>
							{ isModalOpen && (
								<Modal
									className={
										'woocommerce-customize-store__opt-in-usage-tracking-modal'
									}
									title={ __(
										'Get more fonts',
										'woocommerce'
									) }
									onRequestClose={ closeModal }
									shouldCloseOnClickOutside={ false }
								>
									<CheckboxControl
										className="core-profiler__checkbox"
										label={ interpolateComponents( {
											mixedString: __(
												'I would like to get store updates, including new fonts, on an ongoing basis. In doing so, I agree to share my data to tailor my store setup experience, get more relevant content, and help make WooCommerce better for everyone. You can opt out at any time in WooCommerce settings. {{link}}Learn more about usage tracking{{/link}}.',
												'woocommerce'
											),
											components: {
												link: (
													<Link
														href="woocommerce.com/usage-tracking?utm_medium=product"
														target="_blank"
														type="external"
													/>
												),
											},
										} ) }
										checked={ OptInDataSharing }
										onChange={ setIsOptInDataSharing }
									/>
									<div className="woocommerce-customize-store__design-change-warning-modal-footer">
										<Button
											onClick={ () => {
												skipOptIn();
												closeModal();
											} }
											variant="link"
										>
											{ __( 'Cancel', 'woocommerce' ) }
										</Button>
										<Button
											onClick={ () => {
												optIn();
												if ( isIframe( window ) ) {
													sendMessageToParent( {
														type: 'INSTALL_FONTS',
													} );
												} else {
													sendEvent(
														'INSTALL_FONTS'
													);
												}
											} }
											variant="primary"
											disabled={ ! OptInDataSharing }
										>
											{ __( 'Opt in', 'woocommerce' ) }
										</Button>
									</div>
								</Modal>
							) }
						</div>
					) }
				</div>
			}
		/>
	);
};
