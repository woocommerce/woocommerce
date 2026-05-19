/**
 * External dependencies
 */
import React from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ImporterRowResult, ImporterSummary } from '../data/types';

interface Props {
	summary: ImporterSummary;
}

const STATUS_LABEL: Record< ImporterRowResult[ 'status' ], string > = {
	created: __( 'Created', 'woocommerce' ),
	updated: __( 'Updated', 'woocommerce' ),
	skipped: __( 'Skipped', 'woocommerce' ),
	failed: __( 'Failed', 'woocommerce' ),
};

const ImporterSummaryPanel: React.FC< Props > = ( { summary } ) => {
	const { created, updated, skipped, failed, notified, rows } = summary;

	return (
		<div className="woocommerce-fulfillment-importer-summary">
			<ul className="woocommerce-fulfillment-importer-summary__counts">
				<li>
					{ sprintf(
						/* translators: %d: number of created fulfillments */
						_n(
							'%d fulfillment created',
							'%d fulfillments created',
							created,
							'woocommerce'
						),
						created
					) }
				</li>
				<li>
					{ sprintf(
						/* translators: %d: number of updated fulfillments */
						_n(
							'%d fulfillment updated',
							'%d fulfillments updated',
							updated,
							'woocommerce'
						),
						updated
					) }
				</li>
				<li>
					{ sprintf(
						/* translators: %d: number of skipped rows */
						_n(
							'%d row skipped',
							'%d rows skipped',
							skipped,
							'woocommerce'
						),
						skipped
					) }
				</li>
				<li>
					{ sprintf(
						/* translators: %d: number of failed rows */
						_n(
							'%d row failed',
							'%d rows failed',
							failed,
							'woocommerce'
						),
						failed
					) }
				</li>
				<li>
					{ sprintf(
						/* translators: %d: number of customer notifications sent */
						_n(
							'%d customer notification sent',
							'%d customer notifications sent',
							notified,
							'woocommerce'
						),
						notified
					) }
				</li>
			</ul>

			{ rows.length > 0 && (
				<table className="woocommerce-fulfillment-importer-summary__rows widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Row', 'woocommerce' ) }</th>
							<th>{ __( 'Status', 'woocommerce' ) }</th>
							<th>{ __( 'Order', 'woocommerce' ) }</th>
							<th>{ __( 'Fulfillment', 'woocommerce' ) }</th>
							<th>{ __( 'Message', 'woocommerce' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ rows.map( ( row, index ) => (
							<tr
								key={ `${ row.row }-${ index }` }
								className={ `is-status-${ row.status }` }
							>
								<td>{ row.row }</td>
								<td>
									{ STATUS_LABEL[ row.status ] || row.status }
								</td>
								<td>{ row.order_id ?? '' }</td>
								<td>{ row.fulfillment_id ?? '' }</td>
								<td>{ row.message }</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</div>
	);
};

export default ImporterSummaryPanel;
