/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';
import { SiteHub } from '../assembler-hub/site-hub';
import { ThemeCard } from './theme-card';
import {
	DesignChangeWarningModal,
	StartNewDesignWarningModal,
	StartOverWarningModal,
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
} from './intro-banners';

export type events =
	| { type: 'DESIGN_WITH_AI' }
	| { type: 'JETPACK_OFFLINE_HOWTO' }
	| { type: 'CLICKED_ON_BREADCRUMB' }
	| { type: 'SELECTED_BROWSE_ALL_THEMES' }
	| { type: 'SELECTED_ACTIVE_THEME'; payload: { theme: string } }
	| { type: 'SELECTED_NEW_THEME'; payload: { theme: string } };

export * as actions from './actions';
export * as services from './services';

type BannerStatus = keyof typeof BANNER_COMPONENTS;

const BANNER_COMPONENTS = {
	'network-offline': NetworkOfflineBanner,
	'task-incomplete-active-theme-has-mods': ThemeHasModsBanner,
	'jetpack-offline': JetpackOfflineBanner,
	'existing-ai-theme': ExistingAiThemeBanner,
	'existing-theme': ExistingThemeBanner,
	default: DefaultBanner,
};

const MODAL_COMPONENTS = {
	'no-modal': null,
	'task-incomplete-override-design-changes': DesignChangeWarningModal,
	'task-complete-with-ai-theme': StartOverWarningModal,
	'task-complete-without-ai-theme': StartNewDesignWarningModal,
};

type ModalStatus = keyof typeof MODAL_COMPONENTS;

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: {
			themeData,
			activeThemeHasMods,
			customizeStoreTaskCompleted,
			currentThemeIsAiGenerated,
		},
	} = context;

	const isJetpackOffline = false;

	const isNetworkOffline = useNetworkStatus();

	const [ openDesignChangeWarningModal, setOpenDesignChangeWarningModal ] =
		useState( false );

	let modalStatus: ModalStatus = 'no-modal';
	let bannerStatus: BannerStatus = 'default';

	switch ( true ) {
		case isNetworkOffline:
			bannerStatus = 'network-offline';
			break;
		case isJetpackOffline as boolean:
			bannerStatus = 'jetpack-offline';
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
					<p>
						{ __(
							'Create a store that reflects your brand and business. Select one of our professionally designed themes to customize, or create your own using AI.',
							'woocommerce'
						) }
					</p>
				</div>

				<div className="woocommerce-customize-store-main">
					<BannerComponent
						setOpenDesignChangeWarningModal={
							setOpenDesignChangeWarningModal
						}
						sendEvent={ sendEvent }
					/>

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
				</div>
			</div>
		</>
	);
};
