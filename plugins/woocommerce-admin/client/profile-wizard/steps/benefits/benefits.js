/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Benefit } from './benefit';
import ManagementIcon from './images/management';
import SalesTaxIcon from './images/sales_tax';
import ShippingLabels from './images/shipping_labels';
import SpeedIcon from './images/speed';

export const Benefits = ( { isJetpackSetup = false, isWcsSetup = false } ) => {
	return (
		<div className="woocommerce-profile-wizard__benefits">
			{ ! isJetpackSetup && (
				<Benefit
					title={ __(
						'Store management on the go',
						'woocommerce-admin'
					) }
					icon={ <ManagementIcon /> }
					description={ __(
						'Your store in your pocket. Manage orders, receive sales notifications, and more. Only with a Jetpack connection.',
						'woocommerce-admin'
					) }
				/>
			) }
			{ ( ! isWcsSetup || ! isJetpackSetup ) && (
				<Benefit
					title={ __( 'Automated sales taxes', 'woocommerce-admin' ) }
					icon={ <SalesTaxIcon /> }
					description={ __(
						'Ensure that the correct rate of tax is charged on all of your orders automatically, and print shipping labels at home.',
						'woocommerce-admin'
					) }
				/>
			) }
			{ ! isJetpackSetup && (
				<Benefit
					title={ __(
						'Improved speed & security',
						'woocommerce-admin'
					) }
					icon={ <SpeedIcon /> }
					description={ __(
						'Automatically block brute force attacks and speed up your store using our powerful, global server network to cache images.',
						'woocommerce-admin'
					) }
				/>
			) }
			{ isJetpackSetup && ! isWcsSetup && (
				<Benefit
					title={ __(
						'Print shipping labels at home',
						'woocommerce-admin'
					) }
					icon={ <ShippingLabels /> }
					description={ __(
						'Save time at the post office by printing shipping labels for your orders at home.',
						'woocommerce-admin'
					) }
				/>
			) }
		</div>
	);
};
