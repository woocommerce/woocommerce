/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { OrderStatus } from '@woocommerce/components';

const orderStatusMap = {
	processing: __( 'Processing Order' ),
	pending: __( 'Pending Order' ),
	completed: __( 'Completed Order' ),
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
