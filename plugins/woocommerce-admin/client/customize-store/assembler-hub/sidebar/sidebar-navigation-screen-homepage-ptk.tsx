/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	// @ts-ignore No types for this exist yet.
	__experimentalItemGroup as ItemGroup,
	Modal,
	// @ts-ignore No types for this exist yet.
	__experimentalNavigatorButton as NavigatorButton,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/components';
import {
	createInterpolateElement,
	useContext,
	useMemo,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import {
	store as coreStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/core-data';
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
import { useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useNetworkStatus } from '~/utils/react-hooks/use-network-status';
import { isIframe, sendMessageToParent } from '~/customize-store/utils';
import { useEditorBlocks } from '../hooks/use-editor-blocks';

export const SidebarNavigationScreenHomepagePTK = ( {
	onNavigateBackClick,
}: {
	onNavigateBackClick: () => void;
} ) => {
	const { context, sendEvent } = useContext( CustomizeStoreContext );

	const isNetworkOffline = useNetworkStatus();
	const isPTKPatternsAPIAvailable = context.isPTKPatternsAPIAvailable;
	const trackingAllowed = useSelect( ( sel ) =>
		sel( OPTIONS_STORE_NAME ).getOption( 'woocommerce_allow_tracking' )
	);
	const isTrackingDisallowed = trackingAllowed === 'no' || ! trackingAllowed;

	const currentTemplate = useSelect(
		( sel ) =>
			// @ts-expect-error No types for this exist yet.
			sel( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	const [ blocks ] = useEditorBlocks(
		'wp_template',
		currentTemplate?.id ?? ''
	);

	const numberOfPatternsAdded = useMemo( () => {
		const categories = Object.keys( PATTERN_CATEGORIES );

		const initialAccumulator = categories.reduce(
			( acc, cat ) => ( {
				...acc,
				[ cat ]: 0,
			} ),
			{} as Record< string, number >
		);

		return blocks.reduce( ( acc, block ) => {
			const blockCategories: Array< string > =
				block.attributes?.metadata?.categories ?? [];

			const foundCategory = blockCategories.find( ( blockCategory ) =>
				categories.includes( blockCategory )
			);

			if ( foundCategory ) {
				return {
					...acc,
					[ foundCategory ]: acc[ foundCategory ] + 1,
				};
			}

			return acc;
		}, initialAccumulator );
	}, [ blocks ] );

	let notice;
	if ( isNetworkOffline ) {
		notice = __(
			"Looks like we can't detect your network. Please double-check your internet connection and refresh the page.",
			'woocommerce'
		);
	} else if ( ! isPTKPatternsAPIAvailable ) {
		notice = __(
			"Unfortunately, we're experiencing some technical issues — please come back later to access more patterns.",
			'woocommerce'
		);
	} else if ( isTrackingDisallowed ) {
		notice = __(
			'Opt in to <OptInModal>usage tracking</OptInModal> to get access to more patterns.',
			'woocommerce'
		);
	}

	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = () => setIsModalOpen( true );
	const closeModal = () => setIsModalOpen( false );

	const [ optInDataSharing, setIsOptInDataSharing ] =
		useState< boolean >( true );

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
			onNavigateBackClick={ onNavigateBackClick }
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
										<div className="edit-site-sidebar-navigation-screen-patterns__group-homepage-label-container">
											<span>{ capitalize( label ) }</span>
											{ blocks.length > 0 &&
												numberOfPatternsAdded[
													categoryKey
												] > 0 && (
													<span className="edit-site-sidebar-navigation-screen-patterns__group-homepage-number-pattern">
														{
															numberOfPatternsAdded[
																categoryKey
															]
														}
													</span>
												) }
										</div>
									</NavigatorButton>
								</ItemGroup>
							)
						) }
						{ notice && (
							<div className="woocommerce-customize-store_sidebar-patterns-upgrade-notice">
								<h4>
									{ __(
										'Want more patterns?',
										'woocommerce'
									) }
								</h4>
								<p>
									{ createInterpolateElement( notice, {
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
											'Opt in to usage tracking',
											'woocommerce'
										) }
										onRequestClose={ closeModal }
										shouldCloseOnClickOutside={ false }
									>
										<CheckboxControl
											className="core-profiler__checkbox"
											label={ interpolateComponents( {
												mixedString: __(
													'I agree to share my data to tailor my store setup experience, get more relevant content, and help make WooCommerce better for everyone. You can opt out at any time in WooCommerce settings. {{link}}Learn more about usage tracking{{/link}}.',
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
											checked={ optInDataSharing }
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
												{ __(
													'Cancel',
													'woocommerce'
												) }
											</Button>
											<Button
												onClick={ () => {
													optIn();
													if ( isIframe( window ) ) {
														sendMessageToParent( {
															type: 'INSTALL_PATTERNS',
														} );
													} else {
														sendEvent(
															'INSTALL_PATTERNS'
														);
													}
												} }
												variant="primary"
												disabled={ ! optInDataSharing }
											>
												{ __(
													'Opt in',
													'woocommerce'
												) }
											</Button>
										</div>
									</Modal>
								) }
							</div>
						) }
					</div>
				</div>
			}
		/>
	);
};
