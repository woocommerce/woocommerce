/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';

const SHIPSTATION_PLUGIN_SLUG = 'woocommerce-shipstation-integration';

const ShipStationItem = ( {
	onSetupClick,
	pluginsBeingSetup,
}: {
	pluginsBeingSetup: Array< string >;
	onSetupClick: ( slugs: string[] ) => PromiseLike< void >;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleSetupClick = () => {
		onSetupClick( [ SHIPSTATION_PLUGIN_SLUG ] ).then( () => {
			createSuccessNotice(
				__( 'ShipStation is installed!', 'woocommerce' ),
				{}
			);
		} );
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
					isSecondary
					onClick={ handleSetupClick }
					isBusy={ pluginsBeingSetup.includes(
						SHIPSTATION_PLUGIN_SLUG
					) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ __( 'Get started', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default ShipStationItem;
