/**
 * External dependencies
 */
import React, { useCallback, useMemo, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Flex } from '@wordpress/components';
import { Pill, Table } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import ImporterCounters from './importer-counters';
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
		key: 'message',
		label: __( 'Message', 'woocommerce' ),
		isLeftAligned: true,
	},
];

/**
 * The order cell always shows the number the CSV referred to, even when the
 * order was not found; rows that resolved an order link to its edit screen
 * in a new tab so the summary stays put.
 */
function orderCell( row: ImporterRowResult ) {
	const display =
		row.order_number ||
		( row.order_id !== undefined ? String( row.order_id ) : '' );
	if ( row.order_id === undefined ) {
		return { display, value: display };
	}
	// The server sends the HPOS-aware URL; the fallback covers older rows.
	const href =
		row.order_edit_url ||
		getAdminLink( `post.php?post=${ row.order_id }&action=edit` );
	return {
		display: (
			<a href={ href } target="_blank" rel="noreferrer">
				{ display || row.order_id }
				<span className="screen-reader-text">
					{ __( '(opens in a new tab)', 'woocommerce' ) }
				</span>
			</a>
		),
		value: row.order_id,
	};
}

const ImporterSummaryPanel: React.FC< Props > = ( { summary } ) => {
	const { created, updated, skipped, failed, notified, rows } = summary;
	const [ visibleCount, setVisibleCount ] = useState( ROWS_PER_PAGE );

	const visibleRows = useMemo(
		() => rows.slice( 0, visibleCount ),
		[ rows, visibleCount ]
	);

	const tableRows = useMemo(
		() =>
			visibleRows.map( ( row ) => {
				const label = STATUS_LABEL[ row.status ];
				return [
					{ display: row.row, value: row.row },
					{
						display: (
							<Pill className={ `is-status-${ row.status }` }>
								{ label }
							</Pill>
						),
						value: row.status,
					},
					orderCell( row ),
					{ display: row.message, value: row.message },
				];
			} ),
		[ visibleRows ]
	);

	const hasMore = rows.length > visibleCount;

	const handleShowMore = useCallback(
		() => setVisibleCount( ( current ) => current + ROWS_PER_PAGE ),
		[]
	);

	let resultsBlock: React.ReactNode = null;
	if ( rows.length > 0 ) {
		resultsBlock = (
			<div className="woocommerce-fulfillment-importer-summary__rows">
				<Table
					caption={ __( 'Import results', 'woocommerce' ) }
					headers={ TABLE_HEADERS }
					rows={ tableRows }
					rowKey={ ( _row, index ) =>
						visibleRows[ index ]?.row ?? index
					}
				/>
				{ hasMore ? (
					<Flex
						className="woocommerce-fulfillment-importer-summary__load-more"
						justify="center"
					>
						<Button variant="secondary" onClick={ handleShowMore }>
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
					</Flex>
				) : null }
			</div>
		);
	}

	return (
		// The counters carry their own live region; a second one here would
		// double up announcements.
		<div className="woocommerce-fulfillment-importer-summary">
			<ImporterCounters
				counts={ { created, updated, skipped, failed, notified } }
			/>

			{ resultsBlock }
		</div>
	);
};

export default ImporterSummaryPanel;
