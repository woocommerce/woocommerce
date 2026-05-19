/**
 * External dependencies
 */
import React, { useMemo, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

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

const ROWS_PER_PAGE = 100;

// content-visibility lets the browser skip layout/paint for off-screen rows.
const ROW_STYLE: React.CSSProperties = {
	contentVisibility: 'auto',
	containIntrinsicSize: '2rem',
};

const ImporterSummaryPanel: React.FC< Props > = ( { summary } ) => {
	const { created, updated, skipped, failed, notified, rows } = summary;
	const [ visibleCount, setVisibleCount ] = useState( ROWS_PER_PAGE );

	const visibleRows = useMemo(
		() => rows.slice( 0, visibleCount ),
		[ rows, visibleCount ]
	);
	const hasMore = rows.length > visibleCount;

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

			{ rows.length > 0 ? (
				<>
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
							{ visibleRows.map( ( row, index ) => (
								<tr
									key={
										row.fulfillment_id
											? `f-${ row.fulfillment_id }`
											: `r-${ row.row }-${ index }`
									}
									className={ `is-status-${ row.status }` }
									style={ ROW_STYLE }
								>
									<td>{ row.row }</td>
									<td>
										{ STATUS_LABEL[ row.status ] ||
											row.status }
									</td>
									<td>{ row.order_id ?? '' }</td>
									<td>{ row.fulfillment_id ?? '' }</td>
									<td>{ row.message }</td>
								</tr>
							) ) }
						</tbody>
					</table>
					{ hasMore && (
						<div className="woocommerce-fulfillment-importer-summary__load-more">
							<Button
								variant="secondary"
								onClick={ () =>
									setVisibleCount(
										( current ) => current + ROWS_PER_PAGE
									)
								}
							>
								{ sprintf(
									/* translators: 1: rows currently shown, 2: total rows */
									__(
										'Show more (showing %1$d of %2$d)',
										'woocommerce'
									),
									visibleRows.length,
									rows.length
								) }
							</Button>
						</div>
					) }
				</>
			) : null }
		</div>
	);
};

export default ImporterSummaryPanel;
