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
	Spinner,
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
import { SidebarNavigationScreen } from '../sidebar-navigation-screen';
import { trackEvent } from '~/customize-store/tracking';
import { CustomizeStoreContext } from '../..';
import { Link } from '@woocommerce/components';
import { PATTERN_CATEGORIES } from '../pattern-screen/categories';
import { capitalize } from 'lodash';
import { getNewPath, navigateTo, useQuery } from '@woocommerce/navigation';
import { useSelect } from '@wordpress/data';
import { useNetworkStatus } from '~/utils/react-hooks/use-network-status';
import { useEditorBlocks } from '../../hooks/use-editor-blocks';
import { isTrackingAllowed } from '../../utils/is-tracking-allowed';
import clsx from 'clsx';
import './style.scss';
import { usePatterns } from '~/customize-store/assembler-hub/hooks/use-patterns';
import { THEME_SLUG } from '~/customize-store/data/constants';
import apiFetch from '@wordpress/api-fetch';
import { enableTracking } from '~/customize-store/design-without-ai/services';

const isActiveElement = ( path: string | undefined, category: string ) => {
	if ( path?.includes( category ) ) {
		return true;
	}
};

export const SidebarNavigationScreenHomepagePTK = ( {
	onNavigateBackClick,
}: {
	onNavigateBackClick: () => void;
} ) => {
	const { context } = useContext( CustomizeStoreContext );

	const isNetworkOffline = useNetworkStatus();
	const isPTKPatternsAPIAvailable = context.isPTKPatternsAPIAvailable;

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

	const {
		blockPatterns,
		isLoading: isLoadingPatterns,
		invalidateCache,
	} = usePatterns();

	const patternsFromPTK = blockPatterns.filter(
		( pattern ) =>
			! pattern.name.includes( THEME_SLUG ) &&
			! pattern.name.includes( 'woocommerce' ) &&
			pattern.source !== 'core' &&
			pattern.source !== 'pattern-directory/featured' &&
			pattern.source !== 'pattern-directory/theme' &&
			pattern.source !== 'pattern-directory/core'
	);

	const notice = useMemo( () => {
		let noticeText;
		if ( isNetworkOffline ) {
			noticeText = __(
				"Looks like we can't detect your network. Please double-check your internet connection and refresh the page.",
				'woocommerce'
			);
		} else if ( ! isPTKPatternsAPIAvailable ) {
			noticeText = __(
				"Unfortunately, we're experiencing some technical issues — please come back later to access more patterns.",
				'woocommerce'
			);
		} else if ( ! isTrackingAllowed() ) {
			noticeText = __(
				'Opt in to <OptInModal>usage tracking</OptInModal> to get access to more patterns.',
				'woocommerce'
			);
		} else if ( ! isLoadingPatterns && patternsFromPTK.length === 0 ) {
			noticeText = __(
				'Unfortunately, a technical issue is preventing more patterns from being displayed. Please <FetchPatterns>try again</FetchPatterns> later.',
				'woocommerce'
			);
		}
		return noticeText;
	}, [
		isNetworkOffline,
		isPTKPatternsAPIAvailable,
		isLoadingPatterns,
		patternsFromPTK.length,
	] );

	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = () => setIsModalOpen( true );
	const closeModal = () => setIsModalOpen( false );

	const [ optInDataSharing, setIsOptInDataSharing ] =
		useState< boolean >( true );

	const [ isFetchingPatterns, setIsFetchingPatterns ] = useState( false );

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

	const title = __( 'Design your homepage', 'woocommerce' );

	const sidebarMessage = __(
		'Create an engaging homepage by adding and combining different patterns and layouts. You can continue customizing this page, including the content, later via the <EditorLink>Editor</EditorLink>.',
		'woocommerce'
	);

	const path = useQuery().path;

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
										className={ clsx( {
											'edit-site-sidebar-navigation-screen-patterns__group-homepage-item--active':
												isActiveElement(
													path,
													categoryKey
												),
										} ) }
										path={ `/customize-store/assembler-hub/homepage/${ categoryKey }` }
										onClick={ () => {
											const categoryUrl = getNewPath(
												{ customizing: true },
												`/customize-store/assembler-hub/homepage/${ categoryKey }`,
												{}
											);
											navigateTo( {
												url: categoryUrl,
											} );
											trackEvent(
												'customize_your_store_assembler_pattern_category_click',
												{ category: categoryKey }
											);
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
										FetchPatterns: (
											<Button
												onClick={ async () => {
													await apiFetch( {
														path: `/wc/private/patterns`,
														method: 'POST',
													} );

													invalidateCache();
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
											'Access more patterns',
											'woocommerce'
										) }
										onRequestClose={ closeModal }
										shouldCloseOnClickOutside={ false }
									>
										<CheckboxControl
											className="core-profiler__checkbox"
											label={ interpolateComponents( {
												mixedString: __(
													'More patterns from the WooCommerce.com library are available! Opt in to connect your store and access the full library, plus get more relevant content and a tailored store setup experience. Opting in will enable {{link}}usage tracking{{/link}}, which you can opt out of at any time via WooCommerce settings.',
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
												onClick={ async () => {
													optIn();
													await enableTracking();
													setIsFetchingPatterns(
														true
													);
													await apiFetch< {
														success: boolean;
													} >( {
														path: `/wc/private/patterns`,
														method: 'POST',
													} );
													invalidateCache();
													closeModal();
													setIsFetchingPatterns(
														false
													);
												} }
												variant="primary"
												disabled={ ! optInDataSharing }
											>
												{ isFetchingPatterns ? (
													<Spinner />
												) : (
													__(
														'Opt in',
														'woocommerce'
													)
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
