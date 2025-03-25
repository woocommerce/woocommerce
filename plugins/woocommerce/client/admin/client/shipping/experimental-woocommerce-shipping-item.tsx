/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ExternalLink } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useLayoutContext } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';
import WooIcon from './woo-icon.svg';

const WooCommerceShippingItem: React.FC< {
	isPluginInstalled: boolean | undefined;
} > = ( { isPluginInstalled } ) => {
	const { layoutString } = useLayoutContext();

	const handleSetupClick = () => {
		recordEvent( 'tasklist_click', {
			task_name: 'shipping-recommendation',
			context: `${ layoutString }/wc-settings`,
		} );
		navigateTo( {
			url: getNewPath( { task: 'shipping-recommendation' }, '/', {} ),
		} );
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
				<Button isSecondary onClick={ handleSetupClick }>
					{ isPluginInstalled
						? __( 'Activate', 'woocommerce' )
						: __( 'Get started', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default WooCommerceShippingItem;
