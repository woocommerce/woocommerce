/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import classNames from 'classnames';
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

import './intro.scss';

export type events =
	| { type: 'DESIGN_WITH_AI' }
	| { type: 'JETPACK_OFFLINE_HOWTO' }
	| { type: 'CLICKED_ON_BREADCRUMB' }
	| { type: 'SELECTED_BROWSE_ALL_THEMES' }
	| { type: 'SELECTED_ACTIVE_THEME' }
	| { type: 'SELECTED_NEW_THEME'; payload: { theme: string } };

export * as actions from './actions';
export * as services from './services';

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: { themeCards },
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

	return (
		<>
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
										sendEvent( { type: 'DESIGN_WITH_AI' } );
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
							<div className="theme-card" key={ themeCard.slug }>
								<div>
									<img
										src={ themeCard.image }
										alt={ themeCard.description }
									/>
								</div>
								<div className="theme-card__info">
									<h2 className="theme-card__title">
										{ themeCard.name }
									</h2>
									{ themeCard.styleVariations && (
										<ul className="theme-card__color-variations">
											{ themeCard.styleVariations.map(
												( styleVariation ) => (
													<li
														key={
															styleVariation.title
														}
													>
														<div
															className="theme-card__color-variation-left"
															style={ {
																border: styleVariation?.primary_border
																	? '1px solid ' +
																	  styleVariation.primary_border
																	: 'none',
																backgroundColor:
																	styleVariation.primary,
															} }
														></div>
														<div
															className="theme-card__color-variation-right"
															style={ {
																border: styleVariation?.secondary_border
																	? '1px solid ' +
																	  styleVariation.secondary_border
																	: 'none',
																backgroundColor:
																	styleVariation.secondary,
															} }
														></div>
													</li>
												)
											) }
										</ul>
									) }
								</div>
								<div>
									{ themeCard.isActive && (
										<span className="theme-card__active">
											{ __(
												'Active theme',
												'woocommerce'
											) }
										</span>
									) }
									<span className="theme-card__free">
										Free
									</span>
								</div>
							</div>
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
