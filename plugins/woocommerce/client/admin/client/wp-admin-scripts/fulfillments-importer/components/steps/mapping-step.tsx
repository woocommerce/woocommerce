/**
 * External dependencies
 */
import React, { useCallback, useMemo } from 'react';
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { Icon, caution } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { CanonicalColumnKey, MappingChoice } from '../../data/types';
import {
	hasAllRequiredColumns,
	REQUIRED_COLUMNS,
} from '../../hooks/use-importer-state';
import type { StepComponentProps } from './types';

const FIELD_LABELS: Record< Exclude< CanonicalColumnKey, '' >, string > = {
	order_number: __( 'Order number', 'woocommerce' ),
	tracking_number: __( 'Tracking number', 'woocommerce' ),
	shipment_provider: __( 'Carrier / provider', 'woocommerce' ),
	tracking_url: __( 'Tracking URL', 'woocommerce' ),
	items: __( 'Items', 'woocommerce' ),
};

const MAPPING_OPTIONS: Array< {
	label: string;
	value: MappingChoice;
	disabled?: boolean;
} > = [
	// Blank option shown while a column is still unassigned; picking a field
	// or "Do not import" is the way out of it, so it cannot be re-selected.
	{ value: '', label: '', disabled: true },
	{ value: 'order_number', label: FIELD_LABELS.order_number },
	{ value: 'tracking_number', label: FIELD_LABELS.tracking_number },
	{ value: 'shipment_provider', label: FIELD_LABELS.shipment_provider },
	{ value: 'tracking_url', label: FIELD_LABELS.tracking_url },
	{ value: 'items', label: FIELD_LABELS.items },
	{ value: 'skip', label: __( 'Do not import', 'woocommerce' ) },
];

// An unassigned column reads as excluded, except while a required field is
// missing: then it stays blank so it can be flagged as a candidate.
function displayedMapping(
	mapped: MappingChoice,
	hasMissingRequired: boolean
): MappingChoice {
	return mapped === '' && ! hasMissingRequired ? 'skip' : mapped;
}

const MappingStep: React.FC< StepComponentProps > = ( { state, dispatch } ) => {
	const continueDisabled = ! hasAllRequiredColumns( state.mapping );

	const setMapping = useCallback(
		( col: number, value: string ) => {
			dispatch( {
				type: 'SET_MAPPING_FOR_COL',
				col,
				value: value as MappingChoice,
			} );
		},
		[ dispatch ]
	);

	const mappedValues = useMemo(
		() => new Set( Object.values( state.mapping ).filter( Boolean ) ),
		[ state.mapping ]
	);
	const missingRequired = useMemo(
		() => REQUIRED_COLUMNS.filter( ( req ) => ! mappedValues.has( req ) ),
		[ mappedValues ]
	);
	const hasMissingRequired = missingRequired.length > 0;

	const missingLabels = missingRequired
		.map(
			( key ) => FIELD_LABELS[ key as Exclude< CanonicalColumnKey, '' > ]
		)
		.join( ', ' );

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
			<Card className="woocommerce-fulfillment-importer-step__card">
				<CardBody>
					<h2>{ __( 'Map your columns', 'woocommerce' ) }</h2>

					{ hasMissingRequired ? (
						<Notice status="error" isDismissible={ false }>
							{ sprintf(
								/* translators: %s: names of the required fields that are not mapped. */
								_n(
									'%s is not mapped. Choose the column that holds it, or go back and upload a different file.',
									'%s are not mapped. Choose the columns that hold them, or go back and upload a different file.',
									missingRequired.length,
									'woocommerce'
								),
								missingLabels
							) }
						</Notice>
					) : null }

					<div>
						<p>
							{ __(
								'Adjust any column that did not auto-detect.',
								'woocommerce'
							) }
						</p>
						<p className="woocommerce-fulfillment-importer-rows-found">
							{ sprintf(
								/* translators: %d: number of data rows in the CSV. */
								_n(
									'%d row found.',
									'%d rows found.',
									state.total,
									'woocommerce'
								),
								state.total
							) }
						</p>
					</div>

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
								// A column is flagged only while it is genuinely
								// unassigned and a required field is missing;
								// columns set to "Do not import" are left alone.
								const isUnassignedCandidate =
									row.mapped === '' && hasMissingRequired;
								const isRequiredMapping =
									REQUIRED_COLUMNS.includes(
										row.mapped as CanonicalColumnKey
									);
								return (
									<tr key={ row.index }>
										<th scope="row">
											{ isRequiredMapping ||
											isUnassignedCandidate ? (
												<span className="woocommerce-fulfillment-importer-mapping-table__required">
													{ __(
														'Required',
														'woocommerce'
													) }
												</span>
											) : null }
											<span className="woocommerce-fulfillment-importer-mapping-table__header-name">
												{ row.header }
											</span>
										</th>
										<td>{ row.sample }</td>
										<td
											className={
												isUnassignedCandidate
													? 'is-error'
													: undefined
											}
										>
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
												aria-invalid={
													isUnassignedCandidate
												}
												help={
													// help wires up aria-describedby.
													isUnassignedCandidate ? (
														<>
															<Icon
																icon={ caution }
																size={ 16 }
															/>
															{ __(
																'Not mapped.',
																'woocommerce'
															) }
														</>
													) : undefined
												}
												value={ displayedMapping(
													row.mapped,
													hasMissingRequired
												) }
												options={ MAPPING_OPTIONS }
												onChange={ ( value: string ) =>
													setMapping(
														row.index,
														value
													)
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

					{ /* Last screen before anything is saved, and sending
					     customer emails cannot be undone. */ }
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
				</CardBody>
			</Card>

			<footer className="woocommerce-fulfillment-importer-step__footer">
				<Button
					variant="tertiary"
					onClick={ () => dispatch( { type: 'BACK_TO_UPLOAD' } ) }
				>
					{ __( 'Back', 'woocommerce' ) }
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
