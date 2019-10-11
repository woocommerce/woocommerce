/** @format */
/**
 * Internal dependencies
 */
import { OrderStatus } from '@woocommerce/components';

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const orderStatusMap = {
	processing: __( 'Processing Order' ),
	pending: __( 'Pending Order' ),
	completed: __( 'Completed Order' ),
};

export default () => (
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
