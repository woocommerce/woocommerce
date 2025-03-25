/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { pluginsStore } from '@woocommerce/data';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';
import WooIcon from './woo-icon.svg';

const WoocommerceShippingItem: React.FC< {
	pluginsBeingSetup: Array< string >;
	onSetupClick: ( slugs: string[] ) => PromiseLike< void >;
} > = ( { onSetupClick, pluginsBeingSetup } ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const isSiteConnectedToJetpack = useSelect(
		( select ) => select( pluginsStore ).isJetpackConnected(),
		[]
	);

	const handleSetupClick = () => {
		onSetupClick( [ 'woocommerce-shipping' ] ).then( () => {
			const actions = [];
			if ( ! isSiteConnectedToJetpack ) {
				actions.push( {
					url: getAdminLink(
						'admin.php?page=wc-settings&tab=shipping&section=woocommerce-shipping-settings'
					),
					label: __(
						'Finish the setup by connecting your store to WordPress.com.',
						'woocommerce'
					),
				} );
			}

			createSuccessNotice(
				__( '🎉 WooCommerce Shipping is installed!', 'woocommerce' ),
				{
					actions,
				}
			);
		} );
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-shipping-plugin-item">
			<div className="woocommerce-list__item-before">
				<img
					className="woocommerce-shipping-plugin-item__logo"
					src={ WooIcon }
					alt=""
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
						'woocommerce-shipping'
					) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ __( 'Get started', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default WoocommerceShippingItem;
