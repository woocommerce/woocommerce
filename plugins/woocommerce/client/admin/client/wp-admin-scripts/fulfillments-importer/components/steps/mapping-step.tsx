/**
 * External dependencies
 */
import React, { useCallback, useMemo } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';

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
	order_number: __( 'Order number *', 'woocommerce' ),
	tracking_number: __( 'Tracking number *', 'woocommerce' ),
	shipment_provider: __( 'Carrier / provider *', 'woocommerce' ),
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

const HEADER_ALIASES: Array< {
	canonical: CanonicalColumnKey;
	matches: RegExp;
} > = [
	{
		canonical: 'order_number',
		matches: /^(order[_ ]?(number|id|no|num))$/i,
	},
	{
		canonical: 'tracking_number',
		matches: /^(tracking([_ ]?(number|no|num))?)$/i,
	},
	{
		canonical: 'shipment_provider',
		matches:
			/^(carrier|provider|shipment[_ ]?provider|shipping[_ ]?carrier|shipping[_ ]?provider)$/i,
	},
	{
		canonical: 'tracking_url',
		matches: /^(tracking[_ ]?url|url)$/i,
	},
	{ canonical: 'items', matches: /^(items|line[_ ]?items)$/i },
];

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
			const normalized = header.trim();
			const match = HEADER_ALIASES.find( ( a ) =>
				a.matches.test( normalized )
			);
			detected[ index ] = ( match?.canonical ??
				'' ) as CanonicalColumnKey;
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
					'Required fields are marked with *. Adjust any column that did not auto-detect.',
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
						const isRequiredAndUnmapped =
							! REQUIRED_COLUMNS.includes( row.mapped ) &&
							hasMissingRequired;
						return (
							<tr
								key={ row.index }
								className={
									isRequiredAndUnmapped
										? 'is-required-row'
										: undefined
								}
							>
								<th scope="row">{ row.header }</th>
								<td>{ row.sample }</td>
								<td>
									<SelectControl
										__next40pxDefaultSize
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
												'Optional. Format: "<line_item_id>:<qty>" or "sku:<SKU>:<qty>", separated by "|" or ";". Leave blank to fulfill all unfulfilled items.',
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
