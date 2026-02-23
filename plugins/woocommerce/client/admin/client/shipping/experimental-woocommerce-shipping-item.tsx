/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';
import WooIcon from './woo-icon.svg';

const WOOCOMMERCE_SHIPPING_PLUGIN_SLUG = 'woocommerce-shipping';

const WooCommerceShippingItem = ( {
	isPluginInstalled,
	onSetupClick,
	pluginsBeingSetup,
}: {
	isPluginInstalled: boolean;
	pluginsBeingSetup: Array< string >;
	onSetupClick: ( slugs: string[] ) => PromiseLike< void >;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleSetupClick = () => {
		onSetupClick( [ WOOCOMMERCE_SHIPPING_PLUGIN_SLUG ] ).then(
			() => {
				createSuccessNotice(
					isPluginInstalled
						? __( 'WooCommerce Shipping activated!', 'woocommerce' )
						: __(
								'WooCommerce Shipping is installed!',
								'woocommerce'
						  ),
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
					src={ WooIcon }
					alt="WooCommerce Shipping Logo"
				/>
			</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'WooCommerce Shipping', 'woocommerce' ) }
					<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Print USPS, UPS, and DHL Express labels straight from your WooCommerce dashboard and save on shipping.',
						'woocommerce'
					) }
					<br />
					<ExternalLink href="https://woocommerce.com/woocommerce-shipping/">
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					isSecondary
					onClick={ handleSetupClick }
					isBusy={ pluginsBeingSetup.includes(
						WOOCOMMERCE_SHIPPING_PLUGIN_SLUG
					) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ isPluginInstalled
						? __( 'Activate', 'woocommerce' )
						: __( 'Get started', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default WooCommerceShippingItem;
