/**
 * Order Attribution panel — read-only display of marketing attribution data.
 *
 * Reads from `order.meta_data`, filtered to `_wc_order_attribution_*` keys.
 * Replaces the legacy `woocommerce-order-source-data` meta box.
 */

import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody } from '@wordpress/components';
import { useOrder } from '../data/order-context';

const ATTRIBUTION_PREFIX = '_wc_order_attribution_';

/** Friendly labels for the known attribution keys, in display order. */
const ATTRIBUTION_FIELDS: Array< { key: string; label: string } > = [
	{ key: 'source_type', label: __( 'Source type', 'woocommerce' ) },
	{ key: 'origin', label: __( 'Origin', 'woocommerce' ) },
	{ key: 'utm_source', label: __( 'UTM source', 'woocommerce' ) },
	{ key: 'utm_medium', label: __( 'UTM medium', 'woocommerce' ) },
	{ key: 'utm_campaign', label: __( 'UTM campaign', 'woocommerce' ) },
	{ key: 'utm_content', label: __( 'UTM content', 'woocommerce' ) },
	{ key: 'utm_term', label: __( 'UTM term', 'woocommerce' ) },
	{ key: 'referrer', label: __( 'Referrer', 'woocommerce' ) },
	{ key: 'session_entry', label: __( 'Session entry', 'woocommerce' ) },
	{ key: 'session_pages', label: __( 'Pages viewed', 'woocommerce' ) },
	{ key: 'session_count', label: __( 'Session count', 'woocommerce' ) },
	{ key: 'device_type', label: __( 'Device', 'woocommerce' ) },
	{ key: 'user_agent', label: __( 'User agent', 'woocommerce' ) },
];

export function OrderAttributionPanel() {
	const { order } = useOrder();

	if ( ! order ) {
		return null;
	}

	const metaMap = new Map< string, string >();
	for ( const m of order.meta_data || [] ) {
		if ( typeof m.key === 'string' && m.key.startsWith( ATTRIBUTION_PREFIX ) ) {
			const short = m.key.slice( ATTRIBUTION_PREFIX.length );
			metaMap.set( short, String( m.value ?? '' ) );
		}
	}

	const rows = ATTRIBUTION_FIELDS.filter( ( f ) => {
		const v = metaMap.get( f.key );
		return typeof v === 'string' && v.trim() !== '';
	} );

	return (
		<Card
			className="wc-react-order-edit__panel"
			aria-labelledby="wc-react-order-edit-attribution-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-attribution-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'Attribution', 'woocommerce' ) }
				</h2>
			</CardHeader>

			<CardBody className="wc-react-order-edit__panel-body">
				{ rows.length === 0 ? (
					<p className="wc-react-order-edit__empty">
						{ __( 'No attribution data recorded.', 'woocommerce' ) }
					</p>
				) : (
					<dl className="wc-react-order-edit__meta-list">
						{ rows.map( ( f ) => (
							<div key={ f.key } className="wc-react-order-edit__meta-row">
								<dt>{ f.label }</dt>
								<dd>{ metaMap.get( f.key ) }</dd>
							</div>
						) ) }
					</dl>
				) }
			</CardBody>
		</Card>
	);
}
