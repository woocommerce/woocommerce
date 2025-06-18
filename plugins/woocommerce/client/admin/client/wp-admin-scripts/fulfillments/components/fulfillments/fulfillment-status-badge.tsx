/**
 * Internal dependencies
 */
import { Fulfillment } from '../../data/types';

export default function FulfillmentStatusBadge( {
	fulfillment,
}: {
	fulfillment: Fulfillment;
} ) {
	const statuses = window.wcFulfillmentSettings.statuses;
	return (
		<div
			className={ `woocommerce-fulfillment-status-badge woocommerce-fulfillment-status-badge__${ fulfillment.status }` }
		>
			{ statuses[ fulfillment.status ] ?? fulfillment.status }
		</div>
	);
}
