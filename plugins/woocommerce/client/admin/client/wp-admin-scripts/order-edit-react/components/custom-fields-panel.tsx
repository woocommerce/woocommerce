/**
 * Custom fields / metadata panel.
 *
 * Surveys flagged that today's WC hides `_`-prefixed meta by default ("I need to
 * unhide _meta first"). In the new view, all meta is visible by default — no
 * hiding. v1: read-only.
 */

import { __ } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody } from '@wordpress/components';
import { useOrder } from '../data/order-context';

export function CustomFieldsPanel() {
	const { order } = useOrder();

	if ( ! order ) {
		return null;
	}

	const visibleMeta = ( order.meta_data || [] ).filter( ( m ) => {
		// Skip clearly internal WC bookkeeping keys that would just be noise.
		// Show `_`-prefixed plugin meta — that's the whole point of the survey ask.
		const internal = [
			'_billing_address_index',
			'_shipping_address_index',
			'_recorded_sales',
			'_recorded_coupon_usage_counts',
			'_new_order_email_sent',
		];
		return ! internal.includes( m.key );
	} );

	return (
		<Card
			className="wc-react-order-edit__panel"
			aria-labelledby="wc-react-order-edit-meta-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-meta-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'Custom fields', 'woocommerce' ) }
				</h2>
				<Button
					variant="tertiary"
					size="compact"
					disabled
					aria-label={ __( 'Editing custom fields is a Future spec item', 'woocommerce' ) }
				>
					{ __( 'Edit', 'woocommerce' ) }
				</Button>
			</CardHeader>

			<CardBody className="wc-react-order-edit__panel-body">
				{ visibleMeta.length === 0 ? (
					<p className="wc-react-order-edit__empty">
						{ __( 'No custom fields.', 'woocommerce' ) }
					</p>
				) : (
					<dl className="wc-react-order-edit__meta-list">
						{ visibleMeta.map( ( m, i ) => (
							<div key={ m.id ?? i } className="wc-react-order-edit__meta-row">
								<dt>
									<code>{ m.key }</code>
								</dt>
								<dd>{ formatMetaValue( m.value ) }</dd>
							</div>
						) ) }
					</dl>
				) }
			</CardBody>
		</Card>
	);
}

function formatMetaValue( value: unknown ): string {
	if ( value === null || value === undefined ) {
		return '';
	}
	if ( typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean' ) {
		return String( value );
	}
	try {
		return JSON.stringify( value );
	} catch {
		return '[object]';
	}
}
