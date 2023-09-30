/**
 * External dependencies
 */
import {
	useEffect,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import classNames from 'classnames';
import { Link } from '@woocommerce/components';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
	Button,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';
import { SiteHub } from '../assembler-hub/site-hub';
import { ThemeCard } from './theme-card';
import { DesignChangeWarningModal } from './design-change-warning-modal';
import './intro.scss';
import { ADMIN_URL } from '~/utils/admin-settings';

export type events =
	| { type: 'DESIGN_WITH_AI' }
	| { type: 'JETPACK_OFFLINE_HOWTO' }
	| { type: 'CLICKED_ON_BREADCRUMB' }
	| { type: 'SELECTED_BROWSE_ALL_THEMES' }
	| { type: 'SELECTED_ACTIVE_THEME'; payload: { theme: string } }
	| { type: 'SELECTED_NEW_THEME'; payload: { theme: string } };

export * as actions from './actions';
export * as services from './services';

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: { themeCards, activeThemeHasMods, customizeStoreTaskCompleted },
	} = context;

	const [ isNetworkOffline, setIsNetworkOffline ] = useState( false );
	const isJetpackOffline = false;

	const setOfflineBannerIamge = () => {
		setIsNetworkOffline( true );
	};

	const removeOfflineBannerImage = () => {
		setIsNetworkOffline( false );
	};

	useEffect( () => {
		window.addEventListener( 'offline', setOfflineBannerIamge );
		window.addEventListener( 'online', removeOfflineBannerImage );
		return () => {
			window.addEventListener( 'offline', setOfflineBannerIamge );
			window.addEventListener( 'online', removeOfflineBannerImage );
		};
	}, [] );

	let bannerText;
	let bannerTitle;
	let bannerButtonText;
	if ( isNetworkOffline ) {
		bannerText = __(
			"Unfortunately, the [AI Store designer] isn't available right now as we can't detect your network. Please check your internet connection and try again.",
			'woocommerce'
		);
		bannerTitle = __(
			'Looking to design your store using AI?',
			'woocommerce'
		);
		bannerButtonText = __( 'Retry', 'woocommerce' );
	} else if ( isJetpackOffline ) {
		bannerTitle = __(
			'Looking to design your store using AI?',
			'woocommerce'
		);
		bannerText = __(
			"It looks like you're using Jetpack's offline mode — switch to online mode to start designing with AI.",
			'woocommerce'
		);
		bannerButtonText = __( 'Find out how', 'woocommerce' );
	} else {
		bannerTitle = __(
			'Use the power of AI to design your store',
			'woocommerce'
		);
		bannerText = __(
			'Design the look of your store, create pages, and generate copy using our built-in AI tools.',
			'woocommerce'
		);
		bannerButtonText = __( 'Design with AI', 'woocommerce' );
	}
	const [ openDesignChangeWarningModal, setOpenDesignChangeWarningModal ] =
		useState( false );

	return (
		<>
			{ openDesignChangeWarningModal && (
				<DesignChangeWarningModal
					title={ __(
						'Are you sure you want to start a new design?',
						'woocommerce'
					) }
					isOpen={ true }
					onRequestClose={ () => {
						setOpenDesignChangeWarningModal( false );
					} }
				>
					<p>
						{ createInterpolateElement(
							__(
								"The [AI designer*] will create a new store design for you, and you'll lose any changes you've made to your active theme. If you'd prefer to continue editing your theme, you can do so via the <EditorLink>Editor</EditorLink>.",
								'woocommerce'
							),
							{
								EditorLink: (
									<Link
										onClick={ () => {
											window.open(
												`${ ADMIN_URL }site-editor.php`,
												'_blank'
											);
											return false;
										} }
										href=""
									/>
								),
							}
						) }
					</p>
					<div className="woocommerce-customize-store__design-change-warning-modal-footer">
						<Button
							onClick={ () =>
								setOpenDesignChangeWarningModal( false )
							}
							variant="link"
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							onClick={ () =>
								sendEvent( { type: 'DESIGN_WITH_AI' } )
							}
							variant="primary"
						>
							{ __( 'Design with AI', 'woocommerce' ) }
						</Button>
					</div>
				</DesignChangeWarningModal>
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
					<div
						className={ classNames(
							'woocommerce-customize-store-banner',
							{
								'offline-banner':
									isNetworkOffline || isJetpackOffline,
							}
						) }
					>
						<div
							className={ `woocommerce-customize-store-banner-content` }
						>
							<h1>{ bannerTitle }</h1>
							<p>{ bannerText }</p>
							<Button
								onClick={ () => {
									if ( isJetpackOffline ) {
										sendEvent( {
											type: 'JETPACK_OFFLINE_HOWTO',
										} );
									} else if ( isNetworkOffline === false ) {
										if (
											activeThemeHasMods &&
											! customizeStoreTaskCompleted
										) {
											setOpenDesignChangeWarningModal(
												true
											);
										} else {
											sendEvent( {
												type: 'DESIGN_WITH_AI',
											} );
										}
									}
								} }
								variant={
									isJetpackOffline ? 'link' : 'primary'
								}
							>
								{ bannerButtonText }
							</Button>
						</div>
					</div>

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
