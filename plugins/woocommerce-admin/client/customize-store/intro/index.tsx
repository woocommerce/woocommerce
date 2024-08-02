/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import interpolateComponents from '@automattic/interpolate-components';
import { getNewPath } from '@woocommerce/navigation';
import { Sender } from 'xstate';
import {
	Notice,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent, FlowType } from '../types';
import { SiteHub } from '../assembler-hub/site-hub';
import { ThemeCard } from './theme-card';
import {
	DesignChangeWarningModal,
	StartNewDesignWarningModal,
	StartOverWarningModal,
	ThemeSwitchWarningModal,
} from './warning-modals';
import { useNetworkStatus } from '~/utils/react-hooks/use-network-status';
import './intro.scss';
import {
	NetworkOfflineBanner,
	ThemeHasModsBanner,
	JetpackOfflineBanner,
	DefaultBanner,
	ExistingAiThemeBanner,
	ExistingThemeBanner,
	NoAIBanner,
	ExistingNoAiThemeBanner,
	ClassicThemeBanner,
	NonDefaultBlockThemeBanner,
} from './intro-banners';
import welcomeTourImg from '../assets/images/design-your-own.svg';
import professionalThemeImg from '../assets/images/professional-theme.svg';
import { navigateOrParent } from '~/customize-store/utils';
import { RecommendThemesAPIResponse } from '~/customize-store/types';
import { customizeStoreStateMachineEvents } from '~/customize-store';
import { trackEvent } from '~/customize-store/tracking';
import { isNoAIFlow as isNoAiFlowGuard } from '../guards';

export type events =
	| { type: 'DESIGN_WITH_AI' }
	| { type: 'JETPACK_OFFLINE_HOWTO' }
	| { type: 'CLICKED_ON_BREADCRUMB' }
	| { type: 'SELECTED_BROWSE_ALL_THEMES' }
	| { type: 'SELECTED_ACTIVE_THEME'; payload: { theme: string } }
	| { type: 'SELECTED_NEW_THEME'; payload: { theme: string } }
	| { type: 'DESIGN_WITHOUT_AI' };

export * as actions from './actions';
export * as services from './services';

type BannerStatus = keyof typeof BANNER_COMPONENTS;

const BANNER_COMPONENTS = {
	'network-offline': NetworkOfflineBanner,
	'task-incomplete-active-theme-has-mods': ThemeHasModsBanner,
	'jetpack-offline': JetpackOfflineBanner,
	'existing-ai-theme': ExistingAiThemeBanner,
	'existing-theme': ExistingThemeBanner,
	[ FlowType.noAI ]: NoAIBanner,
	'existing-no-ai-theme': ExistingNoAiThemeBanner,
	'classic-theme': ClassicThemeBanner,
	'non-default-block-theme': NonDefaultBlockThemeBanner,
	default: DefaultBanner,
};

const MODAL_COMPONENTS = {
	'no-modal': null,
	'task-incomplete-override-design-changes': DesignChangeWarningModal,
	'task-complete-with-ai-theme': StartOverWarningModal,
	'task-complete-without-ai-theme': StartNewDesignWarningModal,
};

type ModalStatus = keyof typeof MODAL_COMPONENTS;

const ThemeCards = ( {
	sendEvent,
	themeData,
}: {
	sendEvent: Sender< customizeStoreStateMachineEvents >;
	themeData: RecommendThemesAPIResponse;
} ) => {
	return (
		<>
			<p className="select-theme-text">
				{ __(
					'Or select a professionally designed theme to customize and make your own.',
					'woocommerce'
				) }
			</p>

			<div className="woocommerce-customize-store-theme-cards">
				{ themeData.themes?.map( ( theme ) => (
					<ThemeCard
						key={ theme.slug }
						slug={ theme.slug }
						description={ theme.description }
						thumbnail_url={ theme.thumbnail_url }
						name={ theme.name }
						color_palettes={ theme.color_palettes }
						total_palettes={ theme.total_palettes }
						link_url={ theme?.link_url }
						is_active={ theme.is_active }
						is_free={ theme.is_free }
						price={ theme.price }
						onClick={ () => {
							if ( theme.is_active ) {
								sendEvent( {
									type: 'SELECTED_ACTIVE_THEME',
									payload: { theme: theme.slug },
								} );
							} else {
								sendEvent( {
									type: 'SELECTED_NEW_THEME',
									payload: { theme: theme.slug },
								} );
							}
						} }
					/>
				) ) }
			</div>

			<div className="woocommerce-customize-store-browse-themes">
				<button
					onClick={ () =>
						sendEvent( {
							type: 'SELECTED_BROWSE_ALL_THEMES',
						} )
					}
				>
					{ __( 'Browse all themes', 'woocommerce' ) }
				</button>
			</div>
		</>
	);
};

const CustomizedThemeBanners = ( {
	isBlockTheme,
	isDefaultTheme,
	isNoAiFlow,
	sendEvent,
}: {
	isBlockTheme: boolean | undefined;
	isDefaultTheme: boolean | undefined;
	isNoAiFlow: boolean;
	sendEvent: Sender< customizeStoreStateMachineEvents >;
} ) => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	return (
		<>
			<p className="select-theme-text">
				{ __( 'Design or choose a new theme', 'woocommerce' ) }
			</p>

			<div className="woocommerce-customize-store-cards">
				<div className="intro-card">
					<img
						src={ welcomeTourImg }
						alt={ __( 'Design your own theme', 'woocommerce' ) }
					/>

					<div>
						<h2 className="intro-card__title">
							{ __( 'Design your own theme', 'woocommerce' ) }
						</h2>

						<button
							className="intro-card__link"
							onClick={ () => {
								trackEvent(
									'customize_your_store_intro_design_theme',
									{
										theme_type: isBlockTheme
											? 'block'
											: 'classic',
									}
								);
								if ( isDefaultTheme && isNoAiFlow ) {
									navigateOrParent(
										window,
										getNewPath(
											{ customizing: true },
											'/customize-store/assembler-hub',
											{}
										)
									);
								} else {
									setIsModalOpen( true );
								}
							} }
						>
							{ __( 'Use the store designer', 'woocommerce' ) }
						</button>
					</div>
				</div>

				<div className="intro-card">
					<img
						src={ professionalThemeImg }
						alt={ __(
							'Choose a professionally designed theme',
							'woocommerce'
						) }
					/>

					<div>
						<h2 className="intro-card__title">
							{ __(
								'Choose a professionally designed theme',
								'woocommerce'
							) }
						</h2>

						<button
							className="intro-card__link"
							onClick={ () => {
								trackEvent(
									'customize_your_store_intro_browse_themes'
								);
								sendEvent( {
									type: 'SELECTED_BROWSE_ALL_THEMES',
								} );
							} }
						>
							{ __( 'Browse themes', 'woocommerce' ) }
						</button>
					</div>
				</div>
			</div>
			{ isModalOpen && (
				<ThemeSwitchWarningModal
					setIsModalOpen={ setIsModalOpen }
					isNoAiFlow={ isNoAiFlow }
					redirectToCYSFlow={ () =>
						sendEvent( {
							type: isNoAiFlow
								? 'DESIGN_WITHOUT_AI'
								: 'DESIGN_WITH_AI',
						} )
					}
				/>
			) }
		</>
	);
};

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: {
			activeTheme,
			themeData,
			customizeStoreTaskCompleted,
			currentThemeIsAiGenerated,
		},
		activeThemeHasMods,
	} = context;

	const isJetpackOffline = false;

	const isNetworkOffline = useNetworkStatus();

	const [ showError, setShowError ] = useState(
		context.flowType === FlowType.noAI && context.intro.hasErrors
	);

	const errorMessage =
		context.intro.errorStatus === 403
			? __(
					"Sorry, you don't have permission to update the theme.",
					'woocommerce'
			  )
			: __(
					'Oops! We encountered a problem while setting up the foundations. {{anchor}}Please try again{{/anchor}} or start with a theme.',
					'woocommerce'
			  );

	const [ openDesignChangeWarningModal, setOpenDesignChangeWarningModal ] =
		useState( false );

	let modalStatus: ModalStatus = 'no-modal';
	let bannerStatus: BannerStatus = 'default';

	const isDefaultTheme = activeTheme === 'twentytwentyfour';
	interface Theme {
		is_block_theme?: boolean;
	}

	const currentTheme = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		return select( 'core' ).getCurrentTheme() as Theme;
	}, [] );

	const isBlockTheme = currentTheme?.is_block_theme;

	switch ( true ) {
		case isNetworkOffline:
			bannerStatus = 'network-offline';
			break;
		case isJetpackOffline as boolean:
			bannerStatus = 'jetpack-offline';
			break;
		case context.flowType === FlowType.noAI && ! isBlockTheme:
			bannerStatus = 'classic-theme';
			break;
		case context.flowType === FlowType.noAI &&
			isBlockTheme &&
			! isDefaultTheme:
			bannerStatus = 'non-default-block-theme';
			break;
		case context.flowType === FlowType.noAI &&
			! customizeStoreTaskCompleted:
			bannerStatus = FlowType.noAI;
			break;
		case context.flowType === FlowType.noAI && customizeStoreTaskCompleted:
			bannerStatus = 'existing-no-ai-theme';
			break;
		case ! customizeStoreTaskCompleted && activeThemeHasMods:
			bannerStatus = 'task-incomplete-active-theme-has-mods';
			break;
		case customizeStoreTaskCompleted && currentThemeIsAiGenerated:
			bannerStatus = 'existing-ai-theme';
			break;
		case customizeStoreTaskCompleted && ! currentThemeIsAiGenerated:
			bannerStatus = 'existing-theme';
			break;
	}

	switch ( true ) {
		case openDesignChangeWarningModal === false:
			modalStatus = 'no-modal';
			break;
		case bannerStatus === 'task-incomplete-active-theme-has-mods':
			modalStatus = 'task-incomplete-override-design-changes';
			break;
		case bannerStatus === 'existing-ai-theme':
			modalStatus = 'task-complete-with-ai-theme';
			break;
		case bannerStatus === 'existing-theme':
			modalStatus = 'task-complete-without-ai-theme';
			break;
	}

	const ModalComponent = MODAL_COMPONENTS[ modalStatus ];

	const BannerComponent = BANNER_COMPONENTS[ bannerStatus ];

	const sidebarMessage =
		context.flowType === FlowType.AIOnline
			? __(
					'Create a store that reflects your brand and business. Select one of our professionally designed themes to customize, or create your own using AI.',
					'woocommerce'
			  )
			: __(
					'Design a store that reflects your brand and business. Customize your active theme, select a professionally designed theme, or create a new look using our store designer.',
					'woocommerce'
			  );

	return (
		<>
			{ ModalComponent && (
				<ModalComponent
					sendEvent={ sendEvent }
					setOpenDesignChangeWarningModal={
						setOpenDesignChangeWarningModal
					}
				/>
			) }
			<div className="woocommerce-customize-store-header">
				<SiteHub
					as={ motion.div }
					variants={ {
						view: { x: 0 },
					} }
					isTransparent={ false }
					className="woocommerce-customize-store__content"
				/>
			</div>

			<div className="woocommerce-customize-store-container">
				<div className="woocommerce-customize-store-sidebar">
					<div className="woocommerce-customize-store-sidebar__title">
						<button
							onClick={ () => {
								sendEvent( 'CLICKED_ON_BREADCRUMB' );
							} }
						>
							{ chevronLeft }
						</button>
						{ __( 'Customize your store', 'woocommerce' ) }
					</div>
					<p>{ sidebarMessage }</p>
				</div>

				<div className="woocommerce-customize-store-main">
					{ showError && (
						<Notice
							onRemove={ () => setShowError( false ) }
							className="woocommerce-cys-design-with-ai__error-notice"
							status="error"
						>
							{ interpolateComponents( {
								mixedString: errorMessage,
								components: {
									anchor: (
										// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions, jsx-a11y/anchor-is-valid
										<a
											className="woocommerce-customize-store-error-link"
											onClick={ () =>
												sendEvent( 'DESIGN_WITHOUT_AI' )
											}
										/>
									),
								},
							} ) }
						</Notice>
					) }
					<BannerComponent
						setOpenDesignChangeWarningModal={
							setOpenDesignChangeWarningModal
						}
						redirectToCYSFlow={ () =>
							sendEvent( 'DESIGN_WITHOUT_AI' )
						}
						sendEvent={ sendEvent }
					/>

					{ isDefaultTheme && ! customizeStoreTaskCompleted ? (
						<ThemeCards
							sendEvent={ sendEvent }
							themeData={ themeData }
						/>
					) : (
						<CustomizedThemeBanners
							isBlockTheme={ isBlockTheme }
							isDefaultTheme={ isDefaultTheme }
							sendEvent={ sendEvent }
							isNoAiFlow={ isNoAiFlowGuard( context.flowType ) }
						/>
					) }
				</div>
			</div>
		</>
	);
};
