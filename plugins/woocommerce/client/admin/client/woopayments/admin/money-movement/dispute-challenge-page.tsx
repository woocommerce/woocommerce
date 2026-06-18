/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getWooPaymentsDispute } from './data';
import type { WooPaymentsDispute } from './types';
import { formatLabel, getErrorMessage, getResourceId } from './utils';
import { LiveStatusMessage, StatusMessage } from './table';
import '../style.scss';

export const WooPaymentsDisputeChallengePage = () => {
	const [ dispute, setDispute ] = useState< WooPaymentsDispute | null >(
		null
	);
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const location = useLocation();
	const disputeId = new URLSearchParams( location.search ).get( 'id' ) || '';
	const failClosedMessage = __(
		'Dispute evidence submission is not available in this native WooPayments admin surface yet.',
		'woocommerce'
	);
	const loadingMessage = __( 'Loading dispute…', 'woocommerce' );
	let liveStatusMessage = '';

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
	} else if ( dispute ) {
		liveStatusMessage = failClosedMessage;
	}

	useEffect( () => {
		let isMounted = true;

		const loadDispute = async () => {
			if ( ! disputeId ) {
				setErrorMessage(
					__( 'A dispute ID is required.', 'woocommerce' )
				);
				setIsLoading( false );
				return;
			}

			try {
				const nextDispute = await getWooPaymentsDispute( disputeId );

				if ( isMounted ) {
					setDispute( nextDispute );
					setErrorMessage( null );
				}
			} catch ( error ) {
				if ( isMounted ) {
					setErrorMessage(
						getErrorMessage(
							error,
							__(
								'Unable to load WooPayments dispute details.',
								'woocommerce'
							)
						)
					);
				}
			} finally {
				if ( isMounted ) {
					setIsLoading( false );
				}
			}
		};

		loadDispute();

		return () => {
			isMounted = false;
		};
	}, [ disputeId ] );

	return (
		<section
			className="woocommerce-woopayments-money-movement"
			aria-busy={ isLoading }
		>
			<h2>{ __( 'Challenge dispute', 'woocommerce' ) }</h2>
			<LiveStatusMessage isError={ !! errorMessage }>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ errorMessage && <StatusMessage>{ errorMessage }</StatusMessage> }
			{ dispute && ! errorMessage && (
				<div className="woocommerce-woopayments-money-movement__notice">
					<p>{ failClosedMessage }</p>
					<dl className="woocommerce-woopayments-money-movement__details">
						<div>
							<dt>{ __( 'Dispute ID', 'woocommerce' ) }</dt>
							<dd>{ getResourceId( dispute ) || disputeId }</dd>
						</div>
						<div>
							<dt>{ __( 'Reason', 'woocommerce' ) }</dt>
							<dd>{ formatLabel( dispute.reason ) }</dd>
						</div>
						<div>
							<dt>{ __( 'Status', 'woocommerce' ) }</dt>
							<dd>{ formatLabel( dispute.status ) }</dd>
						</div>
					</dl>
				</div>
			) }
		</section>
	);
};
