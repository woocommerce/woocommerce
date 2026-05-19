/**
 * External dependencies
 */
import React, { useMemo, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Card, CardBody, Flex, FlexItem } from '@wordpress/components';
import { Pill, Table } from '@woocommerce/components';

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

const TABLE_HEADERS = [
	{ key: 'row', label: __( 'Row', 'woocommerce' ), isLeftAligned: true },
	{
		key: 'status',
		label: __( 'Status', 'woocommerce' ),
		isLeftAligned: true,
	},
	{ key: 'order', label: __( 'Order', 'woocommerce' ), isLeftAligned: true },
	{
		key: 'fulfillment',
		label: __( 'Fulfillment', 'woocommerce' ),
		isLeftAligned: true,
	},
	{
		key: 'message',
		label: __( 'Message', 'woocommerce' ),
		isLeftAligned: true,
	},
];

const ImporterSummaryPanel: React.FC< Props > = ( { summary } ) => {
	const { created, updated, skipped, failed, notified, rows } = summary;
	const [ visibleCount, setVisibleCount ] = useState( ROWS_PER_PAGE );

	const tableRows = useMemo(
		() =>
			rows.slice( 0, visibleCount ).map( ( row ) => {
				const label = STATUS_LABEL[ row.status ] || row.status;
				return [
					{ display: row.row, value: row.row },
					{
						display: (
							<Pill className={ `is-status-${ row.status }` }>
								<span aria-label={ label }>{ label }</span>
							</Pill>
						),
						value: row.status,
					},
					{
						display: row.order_id ?? '',
						value: row.order_id ?? '',
					},
					{
						display: row.fulfillment_id ?? '',
						value: row.fulfillment_id ?? '',
					},
					{ display: row.message, value: row.message },
				];
			} ),
		[ rows, visibleCount ]
	);

	const hasMore = rows.length > visibleCount;

	const counts: Array< { key: string; value: number; label: string } > = [
		{
			key: 'created',
			value: created,
			label: _n(
				'Fulfillment created',
				'Fulfillments created',
				created,
				'woocommerce'
			),
		},
		{
			key: 'updated',
			value: updated,
			label: _n(
				'Fulfillment updated',
				'Fulfillments updated',
				updated,
				'woocommerce'
			),
		},
		{
			key: 'skipped',
			value: skipped,
			label: _n( 'Row skipped', 'Rows skipped', skipped, 'woocommerce' ),
		},
		{
			key: 'failed',
			value: failed,
			label: _n( 'Row failed', 'Rows failed', failed, 'woocommerce' ),
		},
		{
			key: 'notified',
			value: notified,
			label: _n(
				'Customer notified',
				'Customers notified',
				notified,
				'woocommerce'
			),
		},
	];

	const totalProcessed = created + updated + skipped + failed;

	let resultsBlock: React.ReactNode = null;
	if ( totalProcessed === 0 ) {
		resultsBlock = (
			<Card>
				<CardBody>
					<p className="woocommerce-fulfillment-importer-summary__empty">
						{ __(
							'No rows were imported. Check that the CSV has the required columns and try again.',
							'woocommerce'
						) }
					</p>
				</CardBody>
			</Card>
		);
	} else if ( rows.length > 0 ) {
		resultsBlock = (
			<div className="woocommerce-fulfillment-importer-summary__rows">
				<Table
					caption={ __( 'Import results', 'woocommerce' ) }
					headers={ TABLE_HEADERS }
					rows={ tableRows }
					rowKey={ ( _row, index ) => index }
				/>
				{ hasMore && (
					<Flex
						className="woocommerce-fulfillment-importer-summary__load-more"
						justify="center"
					>
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
									'Show More (Showing %1$d of %2$d)',
									'woocommerce'
								),
								tableRows.length,
								rows.length
							) }
						</Button>
					</Flex>
				) }
			</div>
		);
	}

	return (
		<div className="woocommerce-fulfillment-importer-summary">
			<Flex
				className="woocommerce-fulfillment-importer-summary__counts"
				wrap
				gap={ 3 }
				justify="flex-start"
			>
				{ counts.map( ( item ) => (
					<FlexItem key={ item.key }>
						<Card size="small">
							<CardBody>
								<div
									className={ `woocommerce-fulfillment-importer-summary__count is-${ item.key }` }
								>
									<span className="woocommerce-fulfillment-importer-summary__count-value">
										{ item.value }
									</span>
									<span className="woocommerce-fulfillment-importer-summary__count-label">
										{ item.label }
									</span>
								</div>
							</CardBody>
						</Card>
					</FlexItem>
				) ) }
			</Flex>

			{ resultsBlock }
		</div>
	);
};

export default ImporterSummaryPanel;
