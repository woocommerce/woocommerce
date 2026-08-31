/**
 * External dependencies
 */
import React, { useCallback, useMemo } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Button, CheckboxControl, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { CanonicalColumnKey, ColumnMapping } from '../../data/types';
import {
	hasAllRequiredColumns,
	REQUIRED_COLUMNS,
} from '../../hooks/use-importer-state';
import type { StepComponentProps } from './types';

const CANONICAL_LABELS: Record< CanonicalColumnKey, string > = {
	order_number: __( 'Order number (required)', 'woocommerce' ),
	tracking_number: __( 'Tracking number (required)', 'woocommerce' ),
	shipment_provider: __( 'Carrier / provider (required)', 'woocommerce' ),
	tracking_url: __( 'Tracking URL', 'woocommerce' ),
	items: __( 'Items', 'woocommerce' ),
	'': __( 'Do not import', 'woocommerce' ),
};

const CANONICAL_OPTIONS: Array< {
	label: string;
	value: CanonicalColumnKey;
} > = (
	[
		'',
		'order_number',
		'tracking_number',
		'shipment_provider',
		'tracking_url',
		'items',
	] as CanonicalColumnKey[]
 ).map( ( key ) => ( {
	value: key,
	label: CANONICAL_LABELS[ key ],
} ) );

// Accepted header aliases keyed by their normalized form. Mirrors the server-side
// default alias table in FulfillmentsCsvImporter::get_column_aliases().
const HEADER_ALIASES: Record< string, CanonicalColumnKey > = {
	order_number: 'order_number',
	order: 'order_number',
	order_id: 'order_number',
	order_no: 'order_number',
	order_num: 'order_number',
	tracking_number: 'tracking_number',
	tracking: 'tracking_number',
	tracking_no: 'tracking_number',
	tracking_num: 'tracking_number',
	shipment_provider: 'shipment_provider',
	provider: 'shipment_provider',
	carrier: 'shipment_provider',
	shipping_provider: 'shipment_provider',
	shipping_carrier: 'shipment_provider',
	tracking_url: 'tracking_url',
	url: 'tracking_url',
	items: 'items',
	line_items: 'items',
};

// Lowercase and snake_case a CSV header so "Order No", "order-no" and
// "order_no" all resolve to the same alias entry.
function normalizeHeader( header: string ): string {
	return header
		.trim()
		.toLowerCase()
		.split( /[\s_-]+/ )
		.filter( Boolean )
		.join( '_' );
}

const MappingStep: React.FC< StepComponentProps > = ( { state, dispatch } ) => {
	const continueDisabled = ! hasAllRequiredColumns( state.mapping );

	const setMapping = useCallback(
		( col: number, value: string ) => {
			dispatch( {
				type: 'SET_MAPPING_FOR_COL',
				col,
				value: value as CanonicalColumnKey,
			} );
		},
		[ dispatch ]
	);

	const onAutoDetect = useCallback( () => {
		// Re-derive a best-effort mapping from sample headers using a fixed alias
		// table that mirrors the server side, so this stays useful without an
		// extra round-trip after manual edits.
		const detected: ColumnMapping = {};
		state.headers.forEach( ( header, index ) => {
			detected[ index ] =
				HEADER_ALIASES[ normalizeHeader( header ) ] ?? '';
		} );
		dispatch( { type: 'RESET_MAPPING_TO_DETECTED', mapping: detected } );
	}, [ dispatch, state.headers ] );

	const mappedValues = useMemo(
		() => new Set( Object.values( state.mapping ).filter( Boolean ) ),
		[ state.mapping ]
	);
	const hasMissingRequired = useMemo(
		() => REQUIRED_COLUMNS.some( ( req ) => ! mappedValues.has( req ) ),
		[ mappedValues ]
	);

	const rows = useMemo(
		() =>
			state.headers.map( ( header, index ) => ( {
				index,
				header,
				sample: state.sample[ index ] ?? '',
				mapped: state.mapping[ index ] ?? '',
			} ) ),
		[ state.headers, state.sample, state.mapping ]
	);

	return (
		<div className="woocommerce-fulfillment-importer-step woocommerce-fulfillment-importer-step--mapping">
			<h2>{ __( 'Map your columns', 'woocommerce' ) }</h2>
			<p>
				{ __(
					'Required fields are marked with "(required)". Adjust any column that did not auto-detect.',
					'woocommerce'
				) }
			</p>

			<table className="woocommerce-fulfillment-importer-mapping-table">
				<thead>
					<tr>
						<th>{ __( 'CSV column', 'woocommerce' ) }</th>
						<th>{ __( 'Sample value', 'woocommerce' ) }</th>
						<th>{ __( 'Map to field', 'woocommerce' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ rows.map( ( row ) => {
						// While a required column is unassigned, highlight only the
						// unmapped rows: they are the candidates for the missing field.
						const isUnmappedCandidate =
							row.mapped === '' && hasMissingRequired;
						return (
							<tr
								key={ row.index }
								className={
									isUnmappedCandidate
										? 'is-required-row'
										: undefined
								}
							>
								<th scope="row">{ row.header }</th>
								<td>{ row.sample }</td>
								<td>
									<SelectControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										aria-label={ sprintf(
											/* translators: %s: CSV column header name. */
											__(
												'Map column %s',
												'woocommerce'
											),
											row.header
										) }
										value={ row.mapped }
										options={ CANONICAL_OPTIONS }
										onChange={ ( value: string ) =>
											setMapping( row.index, value )
										}
									/>
									{ row.mapped === 'items' ? (
										<p className="woocommerce-fulfillment-importer-mapping-help">
											{ __(
												'Optional. Format: "<line_item_id>:<qty>" or "sku:<SKU>:<qty>", separated by "|" or ";". Leave blank to fulfill all order items; updates keep their existing items.',
												'woocommerce'
											) }
										</p>
									) : null }
								</td>
							</tr>
						);
					} ) }
				</tbody>
			</table>

			{ /* Lives here rather than on upload: this is the last screen
			     before anything is saved, and sending customer emails is the
			     one thing in this flow that cannot be undone. */ }
			<CheckboxControl
				__nextHasNoMarginBottom
				label={ __(
					'Send shipment notification emails to customers.',
					'woocommerce'
				) }
				checked={ state.notifyCustomer }
				onChange={ ( value: boolean ) =>
					dispatch( { type: 'SET_NOTIFY', value } )
				}
			/>

			<footer className="woocommerce-fulfillment-importer-step__footer">
				<Button variant="tertiary" onClick={ onAutoDetect }>
					{ __( 'Auto-detect', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					disabled={ continueDisabled }
					onClick={ () => dispatch( { type: 'GO_IMPORT' } ) }
				>
					{ __( 'Start import', 'woocommerce' ) }
				</Button>
			</footer>
		</div>
	);
};

export default MappingStep;
