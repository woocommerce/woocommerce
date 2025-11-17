/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { getNewPath } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { useFullScreen } from '~/utils';
import { isWooExpress } from '~/utils/is-woo-express';
import { isFeatureEnabled } from '~/utils/features';
import './splash-page.scss';

const CustomizeStoreController = () => {
	useFullScreen( [ 'woocommerce-customize-store' ] );

	useEffect( () => {
		document.body.classList.add( 'woocommerce-customize-store' );
		return () => {
			document.body.classList.remove( 'woocommerce-customize-store' );
		};
	}, [] );

	const handleDesignClick = () => {
		window.location.href = getAdminLink( 'site-editor.php' );
	};

	const handleMarketplaceClick = () => {
		// TODO: Mark task complete
		// Redirect to themes marketplace using same logic as redirectToThemes
		if ( isWooExpress() ) {
			window.location.href = getAdminLink( 'themes.php' );
		} else if ( isFeatureEnabled( 'marketplace' ) ) {
			window.location.href = getAdminLink(
				'admin.php?page=wc-admin&tab=themes&path=%2Fextensions'
			);
		} else {
			window.location.href =
				'https://woocommerce.com/product-category/themes/';
		}
	};

	const handleBackClick = () => {
		window.location.href = getNewPath( {}, '/', {} );
	};

	const icon = isRTL() ? chevronRight : chevronLeft;

	return (
		<div className="woocommerce-customize-store__container">
			<div className="woocommerce-customize-store-container">
				<div className="woocommerce-customize-store-sidebar">
					<div className="woocommerce-customize-store-sidebar__title">
						<button onClick={ handleBackClick }>{ icon }</button>
						{ __( 'Customize your store', 'woocommerce' ) }
					</div>
					<p>
						{ __(
							'Design a store that reflects your brand and business. Customize your active theme, select a professionally designed theme, or create a new look using our store designer.',
							'woocommerce'
						) }
					</p>
				</div>

				<div className="woocommerce-customize-store-main">
					<div className="woocommerce-customize-store-banner no-ai-banner">
						<div className="woocommerce-customize-store-banner-content">
							<div className="banner-actions">
								<h1>
									{ __( 'Design your own', 'woocommerce' ) }
								</h1>
								<p>
									{ __(
										'Quickly create a beautiful store using our built-in store designer. Choose your layout, select a style, and much more.',
										'woocommerce'
									) }
								</p>
								<Button
									variant="primary"
									onClick={ handleDesignClick }
								>
									{ __( 'Start designing', 'woocommerce' ) }
								</Button>
							</div>
						</div>
					</div>

					<div className="woocommerce-customize-store-banner pick-your-theme-banner">
						<div className="woocommerce-customize-store-banner-content">
							<div className="banner-actions">
								<h2>
									{ __(
										'Pick your perfect theme',
										'woocommerce'
									) }
								</h2>
								<div className="pick-your-theme-banner__content">
									<p>
										{ __(
											'Bring your vision to life — no coding required. Explore hundreds of free and paid ecommerce-optimized themes.',
											'woocommerce'
										) }
									</p>
									<ul>
										<li>
											{ __(
												'Themes for every industry',
												'woocommerce'
											) }
										</li>
										<li>
											{ __(
												'Ready to use out of the box',
												'woocommerce'
											) }
										</li>
										<li>
											{ __(
												'30-day money-back guarantee',
												'woocommerce'
											) }
										</li>
									</ul>
								</div>
								<Button
									variant="primary"
									onClick={ handleMarketplaceClick }
								>
									{ __(
										'Browse the Marketplace',
										'woocommerce'
									) }
								</Button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default CustomizeStoreController;
