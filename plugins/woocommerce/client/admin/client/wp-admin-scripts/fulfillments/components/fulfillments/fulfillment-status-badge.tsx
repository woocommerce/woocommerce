/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Fulfillment } from '../../data/types';

export default function FulfillmentStatusBadge( {
	fulfillment,
}: {
	fulfillment: Fulfillment;
} ) {
	const statuses = window.wcFulfillmentSettings?.fulfillment_statuses || {};
	const fulfillmentStatus = statuses[ fulfillment.status ] || {
		label: fulfillment.status,
		is_fulfilled: false,
		background_color: '',
		text_color: '',
	};

	return (
		<div
			className={ `woocommerce-fulfillment-status-badge woocommerce-fulfillment-status-badge__${ fulfillment.status }` }
			style={ {
				backgroundColor: fulfillmentStatus.background_color,
				color: fulfillmentStatus.text_color,
			} }
			role="status"
			aria-label={ `${ __( 'Fulfillment status:', 'woocommerce' ) } ${
				fulfillmentStatus.label
			}` }
		>
			{ fulfillmentStatus.label }
		</div>
	);
}
