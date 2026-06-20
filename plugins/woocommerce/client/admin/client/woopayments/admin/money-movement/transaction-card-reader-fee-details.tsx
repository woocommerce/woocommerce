/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getWooPaymentsReaderChargeSummary } from './data';
import type {
	WooPaymentsReaderChargeSummaryResponse,
	WooPaymentsReaderChargeSummaryRow,
} from './types';
import { formatAmount, formatLabel, getErrorMessage } from './utils';
import { LiveStatusMessage, StatusMessage } from './table';

const READER_CHARGE_SUMMARY_TIMEOUT_MS = 15000;

const getRows = (
	response: WooPaymentsReaderChargeSummaryResponse
): WooPaymentsReaderChargeSummaryRow[] => {
	if ( Array.isArray( response ) ) {
		return response;
	}

	return response.data || response.rows || [];
};

const getReaderId = ( row: WooPaymentsReaderChargeSummaryRow ) =>
	row.reader_id || row.readerId || '';

const getTransactionCount = ( row: WooPaymentsReaderChargeSummaryRow ) =>
	row.transactions ?? row.transaction_count ?? '';

const getFeeAmount = ( row: WooPaymentsReaderChargeSummaryRow ) => {
	if ( typeof row.fee === 'number' ) {
		return row.fee;
	}

	if ( row.fee && typeof row.fee === 'object' ) {
		return row.fee.amount;
	}

	return row.amount;
};

const getFeeCurrency = ( row: WooPaymentsReaderChargeSummaryRow ) => {
	if ( row.fee && typeof row.fee === 'object' && row.fee.currency ) {
		return row.fee.currency;
	}

	return row.currency;
};

const escapeCsvValue = ( value: string ) =>
	`"${ value.replace( /"/g, '""' ) }"`;

const buildCsv = ( rows: WooPaymentsReaderChargeSummaryRow[] ) => {
	const headers = [
		__( 'Reader id', 'woocommerce' ),
		__( 'Status', 'woocommerce' ),
		__( 'Transactions', 'woocommerce' ),
		__( 'Fee', 'woocommerce' ),
	];
	const body = rows.map( ( row ) =>
		[
			getReaderId( row ),
			row.status ? formatLabel( row.status ) : '',
			String( getTransactionCount( row ) ),
			formatAmount( getFeeAmount( row ), getFeeCurrency( row ) ),
		]
			.map( escapeCsvValue )
			.join( ',' )
	);

	return [ headers.map( escapeCsvValue ).join( ',' ), ...body ].join( '\n' );
};

const downloadCsv = ( rows: WooPaymentsReaderChargeSummaryRow[] ) => {
	const blob = new Blob( [ buildCsv( rows ) ], {
		type: 'text/csv;charset=utf-8',
	} );
	const url = window.URL.createObjectURL( blob );
	const anchor = document.createElement( 'a' );
	anchor.href = url;
	anchor.download = 'card-readers.csv';
	document.body.appendChild( anchor );
	anchor.click();
	anchor.remove();
	window.URL.revokeObjectURL( url );
};

export const WooPaymentsCardReaderFeeDetails = ( {
	transactionId,
}: {
	transactionId: string;
} ) => {
	const [ rows, setRows ] = useState< WooPaymentsReaderChargeSummaryRow[] >(
		[]
	);
	const [ isLoading, setIsLoading ] = useState( true );
	const [ hasError, setHasError ] = useState( false );
	const [ errorDetail, setErrorDetail ] = useState( '' );

	useEffect( () => {
		let isMounted = true;
		let didTimeout = false;
		const abortController =
			typeof AbortController === 'undefined'
				? null
				: new AbortController();
		const timeoutMessage = __( 'The request timed out.', 'woocommerce' );
		const timeoutId = window.setTimeout( () => {
			didTimeout = true;

			if ( abortController ) {
				abortController.abort();
				return;
			}

			if ( isMounted ) {
				setRows( [] );
				setErrorDetail( timeoutMessage );
				setHasError( true );
				setIsLoading( false );
			}
		}, READER_CHARGE_SUMMARY_TIMEOUT_MS );

		setIsLoading( true );
		setHasError( false );
		setErrorDetail( '' );

		getWooPaymentsReaderChargeSummary( transactionId, {
			...( abortController ? { signal: abortController.signal } : {} ),
		} )
			.then( ( response ) => {
				if ( ! isMounted || didTimeout ) {
					return;
				}

				setRows( getRows( response ) );
			} )
			.catch( ( error ) => {
				if ( ! isMounted ) {
					return;
				}

				setRows( [] );
				setErrorDetail(
					didTimeout ? timeoutMessage : getErrorMessage( error, '' )
				);
				setHasError( true );
			} )
			.finally( () => {
				window.clearTimeout( timeoutId );

				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
			window.clearTimeout( timeoutId );
			abortController?.abort();
		};
	}, [ transactionId ] );

	const loadingMessage = __( 'Loading reader details…', 'woocommerce' );
	const errorMessage = __( 'Readers details not loaded', 'woocommerce' );
	const emptyMessage = __( 'No reader details found.', 'woocommerce' );
	let liveMessage = __( 'Reader details loaded.', 'woocommerce' );

	if ( hasError ) {
		liveMessage = errorDetail
			? `${ errorMessage }. ${ errorDetail }`
			: errorMessage;
	} else if ( isLoading ) {
		liveMessage = loadingMessage;
	} else if ( ! rows.length ) {
		liveMessage = emptyMessage;
	}

	const handleDownload = rows.length ? () => downloadCsv( rows ) : undefined;

	return (
		<section
			className="woocommerce-woopayments-overview-card"
			aria-labelledby="woocommerce-woopayments-card-readers-heading"
			aria-busy={ isLoading }
		>
			<div className="woocommerce-woopayments-overview-card__header">
				<h3 id="woocommerce-woopayments-card-readers-heading">
					{ __( 'Card readers', 'woocommerce' ) }
				</h3>
				<Button
					variant="secondary"
					disabled={ ! rows.length }
					accessibleWhenDisabled
					onClick={ handleDownload }
				>
					{ __( 'Download', 'woocommerce' ) }
				</Button>
			</div>
			<LiveStatusMessage isError={ hasError }>
				{ liveMessage }
			</LiveStatusMessage>
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ hasError && (
				<StatusMessage isError>
					{ errorDetail
						? `${ errorMessage }. ${ errorDetail }`
						: errorMessage }
				</StatusMessage>
			) }
			{ ! isLoading && ! hasError && ! rows.length && (
				<StatusMessage>{ emptyMessage }</StatusMessage>
			) }
			{ ! isLoading && ! hasError && !! rows.length && (
				<table className="woocommerce-woopayments-money-movement__reader-fees-table">
					<thead>
						<tr>
							<th scope="col">
								{ __( 'Reader id', 'woocommerce' ) }
							</th>
							<th scope="col">
								{ __( 'Status', 'woocommerce' ) }
							</th>
							<th scope="col">
								{ __( 'Transactions', 'woocommerce' ) }
							</th>
							<th scope="col">{ __( 'Fee', 'woocommerce' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ rows.map( ( row, index ) => (
							<tr
								key={ `${
									getReaderId( row ) || 'reader'
								}-${ index }` }
							>
								<td>{ getReaderId( row ) || '-' }</td>
								<td>
									{ row.status
										? formatLabel( row.status )
										: '-' }
								</td>
								<td>{ getTransactionCount( row ) || '-' }</td>
								<td>
									{ formatAmount(
										getFeeAmount( row ),
										getFeeCurrency( row )
									) }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</section>
	);
};
