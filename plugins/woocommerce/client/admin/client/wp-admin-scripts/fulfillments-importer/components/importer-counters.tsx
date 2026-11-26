/**
 * External dependencies
 */
import React from 'react';
import { _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { RunChunkCounts } from '../data/types';

interface Props {
	counts: RunChunkCounts;
}

/**
 * The five result counters, shared by the import and summary steps so the
 * numbers settle in place instead of the layout changing between the two
 * screens. Zeros are shown deliberately; a zero count is meaningful.
 */
const ImporterCounters: React.FC< Props > = ( { counts } ) => {
	const items: Array< { key: string; value: number; label: string } > = [
		{
			key: 'created',
			value: counts.created,
			label: _n(
				'Fulfillment created',
				'Fulfillments created',
				counts.created,
				'woocommerce'
			),
		},
		{
			key: 'updated',
			value: counts.updated,
			label: _n(
				'Fulfillment updated',
				'Fulfillments updated',
				counts.updated,
				'woocommerce'
			),
		},
		{
			key: 'skipped',
			value: counts.skipped,
			label: _n(
				'Row skipped',
				'Rows skipped',
				counts.skipped,
				'woocommerce'
			),
		},
		{
			key: 'failed',
			value: counts.failed,
			label: _n(
				'Row failed',
				'Rows failed',
				counts.failed,
				'woocommerce'
			),
		},
		{
			key: 'notified',
			value: counts.notified,
			label: _n(
				'Customer notified',
				'Customers notified',
				counts.notified,
				'woocommerce'
			),
		},
	];

	return (
		<dl className="woocommerce-fulfillment-importer-counts">
			{ items.map( ( item ) => (
				<div
					key={ item.key }
					className="woocommerce-fulfillment-importer-counts__item"
				>
					<dt>{ item.label }</dt>
					<dd>{ item.value.toLocaleString() }</dd>
				</div>
			) ) }
		</dl>
	);
};

export default ImporterCounters;
