/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';
import { DesignChangeWarningModal } from './design-change-warning-modal';
import './intro.scss';
import { ADMIN_URL } from '~/utils/admin-settings';

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
		intro: { themeCards, activeThemeHasMods, customizeStoreTaskCompleted },
	} = context;

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
								onClick={ () => {
									if (
										activeThemeHasMods &&
										! customizeStoreTaskCompleted
									) {
										setOpenDesignChangeWarningModal( true );
									} else {
										sendEvent( { type: 'DESIGN_WITH_AI' } );
									}
								} }
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
