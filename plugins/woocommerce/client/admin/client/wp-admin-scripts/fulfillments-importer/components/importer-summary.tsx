/**
 * External dependencies
 */
import React, { useMemo, useState } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Card, CardBody, Flex, FlexItem } from '@wordpress/components';
import { Table } from '@woocommerce/components';

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
			rows.slice( 0, visibleCount ).map( ( row ) => [
				{ display: row.row, value: row.row },
				{
					display: (
						<span className={ `is-status-${ row.status }` }>
							{ STATUS_LABEL[ row.status ] || row.status }
						</span>
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
			] ),
		[ rows, visibleCount ]
	);

	const hasMore = rows.length > visibleCount;

	const counts: Array< { key: string; label: string } > = [
		{
			key: 'created',
			label: sprintf(
				/* translators: %d: number of created fulfillments */
				_n(
					'%d fulfillment created',
					'%d fulfillments created',
					created,
					'woocommerce'
				),
				created
			),
		},
		{
			key: 'updated',
			label: sprintf(
				/* translators: %d: number of updated fulfillments */
				_n(
					'%d fulfillment updated',
					'%d fulfillments updated',
					updated,
					'woocommerce'
				),
				updated
			),
		},
		{
			key: 'skipped',
			label: sprintf(
				/* translators: %d: number of skipped rows */
				_n(
					'%d row skipped',
					'%d rows skipped',
					skipped,
					'woocommerce'
				),
				skipped
			),
		},
		{
			key: 'failed',
			label: sprintf(
				/* translators: %d: number of failed rows */
				_n( '%d row failed', '%d rows failed', failed, 'woocommerce' ),
				failed
			),
		},
		{
			key: 'notified',
			label: sprintf(
				/* translators: %d: number of customer notifications sent */
				_n(
					'%d customer notification sent',
					'%d customer notifications sent',
					notified,
					'woocommerce'
				),
				notified
			),
		},
	];

	return (
		<div className="woocommerce-fulfillment-importer-summary">
			<Flex
				className="woocommerce-fulfillment-importer-summary__counts"
				wrap
				gap={ 3 }
			>
				{ counts.map( ( item ) => (
					<FlexItem key={ item.key }>
						<Card size="small">
							<CardBody>{ item.label }</CardBody>
						</Card>
					</FlexItem>
				) ) }
			</Flex>

			{ rows.length > 0 ? (
				<div className="woocommerce-fulfillment-importer-summary__rows">
					<Table
						caption={ __( 'Import results', 'woocommerce' ) }
						headers={ TABLE_HEADERS }
						rows={ tableRows }
						rowKey={ ( _row, index ) => index }
					/>
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
									tableRows.length,
									rows.length
								) }
							</Button>
						</div>
					) }
				</div>
			) : null }
		</div>
	);
};

export default ImporterSummaryPanel;
