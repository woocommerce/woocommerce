/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';

const MyFill = () => (
	<Fill name="experimental_woocommerce_wcpay_feature">
		<div className="woocommerce-experiments-placeholder-slotfill">
			<div className="placeholder-slotfill-content">
				Slotfill goes in here!
			</div>
		</div>
	</Fill>
);

if ( window.wcAdminFeatures && window.wcAdminFeatures[ 'beta-tester-slotfill-examples' ] ) {
	registerPlugin(
		'beta-tester-woocommerce-experiments-placeholder-slotfill-example',
		{
			render: MyFill,
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			scope: 'woocommerce-admin',
		}
	);
}
