/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

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

	return (
		<div className="woocommerce-customize-store-splash">
			<div className="woocommerce-customize-store-splash__content">
				<div className="woocommerce-customize-store-splash__left">
					<h1>{ __( 'Customize your store', 'woocommerce' ) }</h1>
					<p>
						{ __(
							'Design a store that reflects your brand and business. Customize your active theme, select a professionally designed theme, or create a new look using our store designer.',
							'woocommerce'
						) }
					</p>
				</div>

				<div className="woocommerce-customize-store-splash__right">
					<div className="woocommerce-customize-store-splash__banner design-your-own">
						<h2>{ __( 'Design Your Own', 'woocommerce' ) }</h2>
						<p>
							{ __(
								'Quickly create a beautiful store using our built-in store designer. Choose your layout, select a style and much more',
								'woocommerce'
							) }
						</p>
						<Button variant="primary" onClick={ handleDesignClick }>
							{ __( 'Start designing', 'woocommerce' ) }
						</Button>
					</div>

					<div className="woocommerce-customize-store-splash__banner pick-your-theme">
						<h2>
							{ __( 'Pick your perfect theme', 'woocommerce' ) }
						</h2>
						<p>
							{ __(
								'Bring your vision to life - no coding required. Explore hundreds of free and paid ecommerce-optimised themes.',
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
						<Button
							variant="secondary"
							onClick={ handleMarketplaceClick }
						>
							{ __( 'Browse the Marketplace', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</div>
		</div>
	);
};

export default CustomizeStoreController;
