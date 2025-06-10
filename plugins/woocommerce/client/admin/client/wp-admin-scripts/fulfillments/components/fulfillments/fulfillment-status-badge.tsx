/**
 * Internal dependencies
 */
import { Fulfillment } from '../../data/types';

export default function FulfillmentStatusBadge( {
	fulfillment,
}: {
	fulfillment: Fulfillment;
} ) {
	return (
		<div
			className={ `woocommerce-fulfillment-status-badge woocommerce-fulfillment-status-badge__${ fulfillment.status }` }
		>
			{ /* TODO: Find a way to convert this to a human readable string. */ }
			{ fulfillment.status }
		</div>
	);
}
