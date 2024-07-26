/**
 * External dependencies
 */
import { Card } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './settings-payments-woocommerce-payments.scss';

export const SettingsPaymentsWooCommercePayments: React.FC = () => {
	return (
		<div className="settings-payments-woocommerce-payments__container">
			<h1>WooCommerce Payments Settings</h1>
			<Card>
				<p>This is a placeholder for WooCommerce Payments settings.</p>
				{ /* Add more WooCommerce Payments specific settings here */ }
			</Card>
		</div>
	);
};

export default SettingsPaymentsWooCommercePayments;
