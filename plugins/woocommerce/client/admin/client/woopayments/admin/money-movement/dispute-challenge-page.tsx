/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	getWooPaymentsDispute,
	getWooPaymentsDisputeFileDetails,
} from './data';
import { DisputeEvidenceForm } from './dispute-evidence-form';
import {
	type DocumentEvidenceField,
	type EvidenceFileMap,
	MAX_EVIDENCE_FILE_BYTES,
	extractSavedEvidenceFileIds,
} from './dispute-evidence-fields';
import type { WooPaymentsDispute } from './types';
import { getErrorMessage } from './utils';
import { LiveStatusMessage, StatusMessage } from './table';
import '../style.scss';

export const WooPaymentsDisputeChallengePage = () => {
	const [ dispute, setDispute ] = useState< WooPaymentsDispute | null >(
		null
	);
	const [ fileDetails, setFileDetails ] = useState< EvidenceFileMap >( {} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ fileDetailsWarning, setFileDetailsWarning ] = useState<
		string | null
	>( null );
	const location = useLocation();
	const disputeId = new URLSearchParams( location.search ).get( 'id' ) || '';
	const loadingMessage = __( 'Loading dispute…', 'woocommerce' );
	let liveStatusMessage = '';

	if ( errorMessage ) {
		liveStatusMessage = errorMessage;
	} else if ( isLoading ) {
		liveStatusMessage = loadingMessage;
	} else if ( fileDetailsWarning ) {
		liveStatusMessage = fileDetailsWarning;
	} else if ( dispute ) {
		liveStatusMessage = __(
			'Dispute evidence form loaded.',
			'woocommerce'
		);
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
				const savedFileIds = extractSavedEvidenceFileIds( nextDispute );
				const fallbackFileDetails = Object.entries(
					savedFileIds
				).reduce< EvidenceFileMap >(
					( files, [ field, fileId ] ) => ( {
						...files,
						[ field as DocumentEvidenceField ]: {
							id: fileId,
							filename: fileId,
							size: MAX_EVIDENCE_FILE_BYTES,
						},
					} ),
					{}
				);

				if ( isMounted ) {
					setDispute( nextDispute );
					setFileDetails( fallbackFileDetails );
					setFileDetailsWarning( null );
					setErrorMessage( null );
					setIsLoading( false );
				}

				const savedFileEntries = Object.entries( savedFileIds );

				if ( ! savedFileEntries.length ) {
					return;
				}

				const settledFileDetails = await Promise.allSettled(
					savedFileEntries.map(
						async ( [ field, fileId ] ) =>
							[
								field,
								await getWooPaymentsDisputeFileDetails(
									fileId
								),
							] as const
					)
				);

				if ( ! isMounted ) {
					return;
				}

				let hasFileDetailsFailure = false;
				const nextFileDetails =
					settledFileDetails.reduce< EvidenceFileMap >(
						( files, result, index ) => {
							const [ field, fileId ] = savedFileEntries[ index ];

							if ( result.status === 'fulfilled' ) {
								return {
									...files,
									[ field as DocumentEvidenceField ]:
										result.value[ 1 ],
								};
							}

							hasFileDetailsFailure = true;
							recordEvent( 'wcpay_dispute_file_details_failed', {
								dispute_id: disputeId,
								dispute_status: nextDispute.status,
								dispute_reason: nextDispute.reason,
								field,
								file_id: fileId,
								message: getErrorMessage(
									result.reason,
									__(
										'Unable to load dispute evidence file details.',
										'woocommerce'
									)
								),
							} );

							return files;
						},
						fallbackFileDetails
					);

				setFileDetails( nextFileDetails );
				setFileDetailsWarning(
					hasFileDetailsFailure
						? __(
								'Some saved evidence files could not be verified. They count toward the full 4.5 MB evidence limit until details can be loaded or the files are replaced.',
								'woocommerce'
						  )
						: null
				);
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
			<LiveStatusMessage
				isError={ !! errorMessage || !! fileDetailsWarning }
			>
				{ liveStatusMessage }
			</LiveStatusMessage>
			{ isLoading && <StatusMessage>{ loadingMessage }</StatusMessage> }
			{ errorMessage && (
				<StatusMessage isError>{ errorMessage }</StatusMessage>
			) }
			{ fileDetailsWarning && ! errorMessage && (
				<StatusMessage isError>{ fileDetailsWarning }</StatusMessage>
			) }
			{ dispute && ! errorMessage && (
				<DisputeEvidenceForm
					dispute={ dispute }
					fileDetails={ fileDetails }
					onDisputeUpdated={ setDispute }
				/>
			) }
		</section>
	);
};
