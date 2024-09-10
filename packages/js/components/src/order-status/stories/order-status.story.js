/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { OrderStatus } from '@woocommerce/components';

const orderStatusMap = {
	processing: __( 'Processing Order', 'woocommerce' ),
	pending: __( 'Pending Order', 'woocommerce' ),
	completed: __( 'Completed Order', 'woocommerce' ),
};

export const Basic = () => (
	<div>
		<OrderStatus
			order={ { status: 'processing' } }
			orderStatusMap={ orderStatusMap }
		/>
		<OrderStatus
			order={ { status: 'pending' } }
			orderStatusMap={ orderStatusMap }
		/>
		<OrderStatus
			order={ { status: 'completed' } }
			orderStatusMap={ orderStatusMap }
		/>
	</div>
);

export default {
	title: 'WooCommerce Admin/components/OrderStatus',
	component: OrderStatus,
};
