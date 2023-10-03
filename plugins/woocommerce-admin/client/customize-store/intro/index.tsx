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
import { DesignChangeWarningModal } from './design-change-warning-modal';
import { useNetworkStatus } from '~/utils/react-hooks/use-network-status';
import './intro.scss';
import {
	NetworkOfflineBanner,
	ThemeHasModsBanner,
	JetpackOfflineBanner,
	DefaultBanner,
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
	'existing-ai-theme': DefaultBanner,
	default: DefaultBanner,
};

const MODAL_COMPONENTS = {
	'no-modal': null,
	'task-incomplete-override-design-changes': DesignChangeWarningModal,
	'task-complete-with-ai-theme': null,
	'task-complete-without-ai-theme': null,
};

type ModalStatus = keyof typeof MODAL_COMPONENTS;

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: { themeCards, activeThemeHasMods, customizeStoreTaskCompleted },
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
		case activeThemeHasMods && ! customizeStoreTaskCompleted:
			bannerStatus = 'task-incomplete-active-theme-has-mods';
			break;
		case context.intro.currentThemeIsAiGenerated:
			bannerStatus = 'existing-ai-theme';
			break;
	}

	switch ( true ) {
		case openDesignChangeWarningModal === false:
			modalStatus = 'no-modal';
			break;
		case bannerStatus === 'task-incomplete-active-theme-has-mods':
			modalStatus = 'task-incomplete-override-design-changes';
			break;
	}

	const ModalComponent = MODAL_COMPONENTS[ modalStatus ];

	const BannerComponent = BANNER_COMPONENTS[ bannerStatus ];

	return (
		<>
			{ ModalComponent && (
				<ModalComponent
					isOpen={ openDesignChangeWarningModal }
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
						{ themeCards?.map( ( themeCard ) => (
							<ThemeCard
								key={ themeCard.slug }
								slug={ themeCard.slug }
								description={ themeCard.description }
								image={ themeCard.image }
								name={ themeCard.name }
								colorPalettes={ themeCard.colorPalettes }
								link={ themeCard?.link }
								isActive={ themeCard.isActive }
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
