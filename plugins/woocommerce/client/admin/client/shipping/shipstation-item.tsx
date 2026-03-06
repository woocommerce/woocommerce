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
import type { ShippingPartnerTrackingProps } from './experimental-woocommerce-shipping-item';

const SHIPSTATION_PLUGIN_SLUG = 'woocommerce-shipstation-integration';

const ShipStationItem = ( {
	isPluginInstalled,
	onInstallClick,
	onActivateClick,
	pluginsBeingSetup,
	tracking,
}: {
	isPluginInstalled: boolean;
	pluginsBeingSetup: Array< string >;
	onInstallClick: ( slugs: string[] ) => PromiseLike< void >;
	onActivateClick: ( slugs: string[] ) => PromiseLike< void >;
	tracking?: ShippingPartnerTrackingProps;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleClick = () => {
		const trackingBase = {
			...( tracking ?? {} ),
			selected_plugin: SHIPSTATION_PLUGIN_SLUG,
		};

		recordEvent( 'shipping_partner_click', trackingBase );

		const action = isPluginInstalled ? onActivateClick : onInstallClick;
		const eventName = isPluginInstalled
			? 'shipping_partner_activate'
			: 'shipping_partner_install';

		action( [ SHIPSTATION_PLUGIN_SLUG ] ).then(
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: true,
				} );
				createSuccessNotice(
					isPluginInstalled
						? __( 'ShipStation activated!', 'woocommerce' )
						: __( 'ShipStation is installed!', 'woocommerce' ),
					{}
				);
			},
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: false,
				} );
			}
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
