/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';

import './intro.scss';

export type events =
	| { type: 'DESIGN_WITH_AI' }
	| { type: 'CLICKED_ON_BREADCRUMB' }
	| { type: 'SELECTED_BROWSE_ALL_THEMES' }
	| { type: 'SELECTED_ACTIVE_THEME'; payload: { theme: string } }
	| { type: 'SELECTED_NEW_THEME'; payload: { theme: string } };

export * as actions from './actions';
export * as services from './services';

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: { themeCards },
	} = context;

	return (
		<>
			<div className="woocommerce-customize-store-header">
				<h1>{ 'Site title' }</h1>
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
					<div className="woocommerce-customize-store-banner">
						<div
							className={ `woocommerce-customize-store-banner-content` }
						>
							<h1>
								{ __(
									'Use the power of AI to design your store',
									'woocommerce'
								) }
							</h1>
							<p>
								{ __(
									'Design the look of your store, create pages, and generate copy using our built-in AI tools.',
									'woocommerce'
								) }
							</p>
							<button
								onClick={ () =>
									sendEvent( { type: 'DESIGN_WITH_AI' } )
								}
							>
								{ __( 'Design with AI', 'woocommerce' ) }
							</button>
						</div>
					</div>

					<p>
						{ __(
							'Or select a professionally designed theme to customize and make your own.',
							'woocommerce'
						) }
					</p>

					<div className="woocommerce-customize-store-theme-cards">
						{ themeCards?.map( ( themeCard ) => (
							<Button
								variant="link"
								className="theme-card"
								key={ themeCard.slug }
								onClick={ () => {
									if ( themeCard.isActive ) {
										sendEvent( {
											type: 'SELECTED_ACTIVE_THEME',
											payload: { theme: themeCard.slug },
										} );
									} else {
										sendEvent( {
											type: 'SELECTED_NEW_THEME',
											payload: { theme: themeCard.slug },
										} );
									}
								} }
							>
								<div>
									<img
										src={ themeCard.image }
										alt={ themeCard.description }
									/>
								</div>
								<h2 className="theme-card__title">
									{ themeCard.name }
								</h2>
							</Button>
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
