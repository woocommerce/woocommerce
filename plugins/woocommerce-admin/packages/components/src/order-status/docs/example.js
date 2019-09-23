/** @format */
/**
 * Internal dependencies
 */
import { OrderStatus } from '@woocommerce/components';

export default () => (
	<div>
		<OrderStatus order={ { status: 'processing' } } />
		<OrderStatus order={ { status: 'pending' } } />
		<OrderStatus order={ { status: 'completed' } } />
	</div>
);
