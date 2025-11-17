/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { chevronLeft } from '@wordpress/icons';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useFullScreen } from '~/utils';
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
		// TODO: Redirect to editor and mark task complete
		// For now, always redirect to site editor (will work for both block and classic themes)
		window.location.href = '/wp-admin/site-editor.php';
	};

	const handleMarketplaceClick = () => {
		// TODO: Redirect to marketplace and mark task complete
		const marketplaceUrl =
			'/wp-admin/admin.php?page=wc-admin&tab=themes&path=%2Fextensions';
		window.location.href = marketplaceUrl;
	};

	const handleBackClick = () => {
		window.location.href = getNewPath( {}, '/', {} );
	};

	return (
		<div className="woocommerce-customize-store__container">
			<div className="woocommerce-customize-store-container">
				<div className="woocommerce-customize-store-sidebar">
					<div className="woocommerce-customize-store-sidebar__title">
						<button onClick={ handleBackClick }>
							{ chevronLeft }
						</button>
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
