/**
 * External dependencies
 */
import React from 'react';
import { _n } from '@wordpress/i18n';

interface Counts {
	created: number;
	updated: number;
	skipped: number;
	failed: number;
	notified: number;
}

interface Props {
	counts: Counts;
}

/**
 * The five result counters, shared by the import and summary steps so the
 * numbers settle in place instead of the layout and wording changing between
 * the two screens. Zeros are always shown: "0 customers notified" after
 * ticking the box is exactly what a merchant needs to see.
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
		<dl
			className="woocommerce-fulfillment-importer-counts"
			aria-live="polite"
		>
			{ items.map( ( item ) => (
				<div
					key={ item.key }
					className={ `woocommerce-fulfillment-importer-counts__item is-${ item.key }` }
				>
					<dt>{ item.label }</dt>
					<dd>{ item.value }</dd>
				</div>
			) ) }
		</dl>
	);
};

export default ImporterCounters;
