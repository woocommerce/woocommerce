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
import { Button, Modal, CheckboxControl, Spinner } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from '../sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { FontPairing } from '../global-styles';
import { CustomizeStoreContext } from '../..';
import { FlowType } from '~/customize-store/types';
import { trackEvent } from '~/customize-store/tracking';
import { installFontFamilies } from '../../utils/fonts';
import { enableTracking } from '~/customize-store/design-without-ai/services';

export const SidebarNavigationScreenTypography = ( {
	onNavigateBackClick,
}: {
	onNavigateBackClick: () => void;
} ) => {
	const { context } = useContext( CustomizeStoreContext );
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
		trackEvent(
			'customize_your_store_assembler_hub_opt_in_usage_tracking'
		);
	};

	const skipOptIn = () => {
		trackEvent(
			'customize_your_store_assembler_hub_skip_opt_in_usage_tracking'
		);
	};

	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = () => setIsModalOpen( true );
	const closeModal = () => setIsModalOpen( false );

	const [ isFetchingFonts, setIsFetchingFonts ] = useState( false );

	const [ OptInDataSharing, setIsOptInDataSharing ] =
		useState< boolean >( true );

	return (
		<SidebarNavigationScreen
			title={ title }
			onNavigateBackClick={ onNavigateBackClick }
			description={ createInterpolateElement( label, {
				EditorLink: (
					<Link
						onClick={ () => {
							trackEvent(
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
							trackEvent(
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
										'Access more fonts',
										'woocommerce'
									) }
									onRequestClose={ closeModal }
									shouldCloseOnClickOutside={ false }
								>
									<CheckboxControl
										className="core-profiler__checkbox"
										label={ interpolateComponents( {
											mixedString: __(
												'More fonts are available! Opt in to connect your store and access the full font library, plus get more relevant content and a tailored store setup experience. Opting in will enable {{link}}usage tracking{{/link}}, which you can opt out of at any time via WooCommerece settings.',
												'woocommerce'
											),
											components: {
												link: (
													<Link
														href="https://woocommerce.com/usage-tracking?utm_medium=product"
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
											onClick={ async () => {
												optIn();
												setIsFetchingFonts( true );
												await enableTracking();
												await installFontFamilies();

												closeModal();
												setIsFetchingFonts( false );
											} }
											variant="primary"
											disabled={ ! OptInDataSharing }
										>
											{ isFetchingFonts ? (
												<Spinner />
											) : (
												__( 'Opt in', 'woocommerce' )
											) }
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
