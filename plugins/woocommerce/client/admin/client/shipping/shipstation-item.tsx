/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';

const SHIPSTATION_PLUGIN_SLUG = 'woocommerce-shipstation-integration';

const ShipStationItem = ( {
	isPluginInstalled,
	onInstallClick,
	onActivateClick,
	pluginsBeingSetup,
}: {
	isPluginInstalled: boolean;
	pluginsBeingSetup: Array< string >;
	onInstallClick: ( slugs: string[] ) => PromiseLike< void >;
	onActivateClick: ( slugs: string[] ) => PromiseLike< void >;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleClick = () => {
		recordEvent( 'settings_shipping_recommendation_setup_click', {
			plugin: SHIPSTATION_PLUGIN_SLUG,
			action: isPluginInstalled ? 'activate' : 'install',
		} );
		const action = isPluginInstalled ? onActivateClick : onInstallClick;
		action( [ SHIPSTATION_PLUGIN_SLUG ] ).then(
			() => {
				createSuccessNotice(
					isPluginInstalled
						? __( 'ShipStation activated!', 'woocommerce' )
						: __( 'ShipStation is installed!', 'woocommerce' ),
					{}
				);
			},
			() => {}
		);
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-shipping-plugin-item">
			<div className="woocommerce-list__item-before">
				<img
					className="woocommerce-shipping-plugin-item__logo"
					src="https://ps.w.org/woocommerce-shipstation-integration/assets/icon-128x128.png"
					alt=""
				/>
			</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'ShipStation', 'woocommerce' ) }
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Ship your WooCommerce orders with confidence, save on top carriers, and automate your processes with ShipStation.',
						'woocommerce'
					) }
					<br />
					<ExternalLink href="https://woocommerce.com/products/shipstation-integration/">
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					onClick={ handleClick }
					variant={ isPluginInstalled ? 'primary' : 'secondary' }
					isBusy={ pluginsBeingSetup.includes(
						SHIPSTATION_PLUGIN_SLUG
					) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ isPluginInstalled
						? __( 'Activate', 'woocommerce' )
						: __( 'Install', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default ShipStationItem;
