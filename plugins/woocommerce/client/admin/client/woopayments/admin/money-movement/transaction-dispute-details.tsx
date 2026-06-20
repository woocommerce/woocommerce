/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { closeWooPaymentsDispute } from './data';
import { ACTIONABLE_DISPUTE_STATUSES } from './dispute-evidence-fields';
import type { WooPaymentsDispute, WooPaymentsTransaction } from './types';
import {
	formatAmount,
	formatDate,
	formatLabel,
	getDisputeId,
	getErrorMessage,
} from './utils';
import { getSettingsPaymentsProviderRouteUrl } from '../utils';

const RESPONDING_TO_DISPUTES_DOC_URL =
	'https://woocommerce.com/document/woopayments/fraud-and-disputes/managing-disputes/#responding';
const PAYMENT_INQUIRIES_DOC_URL =
	'https://woocommerce.com/document/woopayments/fraud-and-disputes/managing-disputes/#inquiries';

const getDisputeChallengeRoute = ( disputeId: string ) =>
	`/woopayments/disputes/challenge?id=${ encodeURIComponent( disputeId ) }`;

const getDisputeChallengeUrl = ( disputeId: string ) =>
	getSettingsPaymentsProviderRouteUrl(
		getDisputeChallengeRoute( disputeId )
	);
const REFUND_GUIDANCE_ID =
	'woocommerce-woopayments-transaction-dispute-refund-guidance';

const isAwaitingResponse = ( status?: string ) =>
	ACTIONABLE_DISPUTE_STATUSES.some(
		( actionableStatus ) => actionableStatus === status
	);

const isInquiry = ( status?: string ) => !! status?.startsWith( 'warning' );

const hasSubmittedEvidence = ( dispute: WooPaymentsDispute ) =>
	!! (
		dispute.evidence_details?.has_evidence ||
		dispute.metadata?.__evidence_submitted_at
	);

const getDisputeStatusLabel = ( status?: string ) => {
	if ( isAwaitingResponse( status ) ) {
		return __( 'Response needed', 'woocommerce' );
	}

	return formatLabel( status );
};

const getResolvedStatusDescription = ( dispute: WooPaymentsDispute ) => {
	switch ( dispute.status ) {
		case 'under_review':
			return __(
				"The customer's bank is reviewing your submitted evidence. This process can take more than 60 days.",
				'woocommerce'
			);
		case 'won':
			return __(
				'You won this dispute. The disputed amount and dispute fee have been returned to your account.',
				'woocommerce'
			);
		case 'lost':
			return __(
				'This dispute was lost. The disputed amount and dispute fee have been deducted from your account.',
				'woocommerce'
			);
		case 'warning_under_review':
			return __(
				"The customer's bank is reviewing the submitted inquiry evidence.",
				'woocommerce'
			);
		case 'warning_closed':
			return __( 'This payment inquiry is closed.', 'woocommerce' );
		default:
			return '';
	}
};

const getCustomerLabel = (
	dispute: WooPaymentsDispute,
	transaction: WooPaymentsTransaction
) =>
	dispute.customer_name ||
	dispute.order?.customer_name ||
	transaction.customer_name ||
	dispute.customer_email ||
	dispute.order?.customer_email ||
	transaction.customer_email ||
	'';

const getDueDate = ( dispute: WooPaymentsDispute ) =>
	dispute.evidence_details?.due_by || dispute.evidence_due_by;

const DetailRow = ( { label, value }: { label: string; value: ReactNode } ) => {
	if ( value === undefined || value === null || value === '' ) {
		return null;
	}

	return (
		<div>
			<dt>{ label }</dt>
			<dd>{ value }</dd>
		</div>
	);
};

const DisputeDocumentationLink = ( {
	isInquiryStatus,
}: {
	isInquiryStatus: boolean;
} ) => (
	<a
		href={
			isInquiryStatus
				? PAYMENT_INQUIRIES_DOC_URL
				: RESPONDING_TO_DISPUTES_DOC_URL
		}
	>
		{ isInquiryStatus
			? __( 'Learn more about payment inquiries', 'woocommerce' )
			: __( 'Learn more about responding to disputes', 'woocommerce' ) }
	</a>
);

const RespondToDisputeActions = ( {
	dispute,
	onAccept,
	isAccepting,
}: {
	dispute: WooPaymentsDispute;
	onAccept: () => void;
	isAccepting: boolean;
} ) => {
	const disputeId = getDisputeId( dispute );
	const isInquiryStatus = isInquiry( dispute.status );
	const challengeLabel = isInquiryStatus
		? __( 'Submit evidence', 'woocommerce' )
		: __( 'Challenge dispute', 'woocommerce' );

	return (
		<>
			<p>
				{ isInquiryStatus
					? __(
							'Submit evidence to respond to this payment inquiry, or issue a full refund before responding.',
							'woocommerce'
					  )
					: __(
							'Challenge the dispute with evidence, or accept it if you do not want to respond.',
							'woocommerce'
					  ) }
			</p>
			<div className="woocommerce-woopayments-money-movement__dispute-actions">
				<a
					className="components-button is-primary"
					href={ getDisputeChallengeUrl( disputeId ) }
					onClick={ () =>
						recordEvent( 'wcpay_dispute_challenge_clicked', {
							dispute_id: disputeId,
							status: dispute.status,
						} )
					}
				>
					{ challengeLabel }
				</a>
				{ isInquiryStatus ? (
					<Button
						variant="secondary"
						disabled
						accessibleWhenDisabled
						aria-describedby={ REFUND_GUIDANCE_ID }
					>
						{ __( 'Issue refund', 'woocommerce' ) }
					</Button>
				) : (
					<Button
						variant="secondary"
						isDestructive
						disabled={ isAccepting }
						accessibleWhenDisabled
						isBusy={ isAccepting }
						onClick={ isAccepting ? undefined : onAccept }
					>
						{ __( 'Accept dispute', 'woocommerce' ) }
					</Button>
				) }
			</div>
			{ isInquiryStatus && (
				<p
					id={ REFUND_GUIDANCE_ID }
					className="woocommerce-woopayments-money-movement__notice"
				>
					{ __(
						'Issue the refund from the full refund flow before responding to this inquiry.',
						'woocommerce'
					) }
				</p>
			) }
			<DisputeDocumentationLink isInquiryStatus={ isInquiryStatus } />
		</>
	);
};

const ResolvedDisputeActions = ( {
	dispute,
}: {
	dispute: WooPaymentsDispute;
} ) => {
	const disputeId = getDisputeId( dispute );
	const shouldShowSubmittedEvidenceLink =
		hasSubmittedEvidence( dispute ) ||
		dispute.status === 'under_review' ||
		dispute.status === 'warning_under_review';
	const statusDescription = getResolvedStatusDescription( dispute );

	return (
		<>
			{ statusDescription && <p>{ statusDescription }</p> }
			{ shouldShowSubmittedEvidenceLink && (
				<a
					href={ getDisputeChallengeUrl( disputeId ) }
					onClick={ () =>
						recordEvent( 'wcpay_view_submitted_evidence_clicked', {
							dispute_id: disputeId,
							status: dispute.status,
						} )
					}
				>
					{ __( 'View submitted evidence', 'woocommerce' ) }
				</a>
			) }
		</>
	);
};

export const WooPaymentsTransactionDisputeDetails = ( {
	transaction,
}: {
	transaction: WooPaymentsTransaction;
} ) => {
	const [ currentDispute, setCurrentDispute ] = useState<
		WooPaymentsDispute | undefined
	>( transaction.dispute );
	const [ isAcceptModalOpen, setIsAcceptModalOpen ] = useState( false );
	const [ isAccepting, setIsAccepting ] = useState( false );
	const [ shouldFocusDisputeDetails, setShouldFocusDisputeDetails ] =
		useState( false );
	const disputeHeadingRef = useRef< HTMLHeadingElement | null >( null );
	const disputeResponseRef = useRef< HTMLDivElement | null >( null );
	const shouldRestoreFocusAfterAcceptRef = useRef( false );

	useEffect( () => {
		setCurrentDispute( transaction.dispute );
	}, [ transaction.dispute ] );

	useEffect( () => {
		if ( ! shouldFocusDisputeDetails || isAcceptModalOpen ) {
			return;
		}

		disputeHeadingRef.current?.focus();
		setShouldFocusDisputeDetails( false );
	}, [ isAcceptModalOpen, shouldFocusDisputeDetails ] );

	if ( ! currentDispute ) {
		return null;
	}

	const disputeId = getDisputeId( currentDispute );
	const dueDate = getDueDate( currentDispute );
	const isAwaitingResponseStatus = isAwaitingResponse(
		currentDispute.status
	);
	const closeAcceptModal = () => {
		shouldRestoreFocusAfterAcceptRef.current = false;
		setIsAcceptModalOpen( false );
	};
	const shouldRestoreFocusAfterAccept = () => {
		if ( shouldRestoreFocusAfterAcceptRef.current ) {
			return true;
		}

		const ownerDocument =
			disputeResponseRef.current?.ownerDocument ||
			disputeHeadingRef.current?.ownerDocument;
		const activeElement = ownerDocument?.activeElement;

		return (
			! activeElement ||
			activeElement === ownerDocument?.body ||
			!! disputeResponseRef.current?.contains( activeElement )
		);
	};
	const handleAcceptDispute = async () => {
		shouldRestoreFocusAfterAcceptRef.current = true;
		setIsAccepting( true );

		try {
			const closedDispute = await closeWooPaymentsDispute( disputeId );
			const shouldRestoreFocus = shouldRestoreFocusAfterAccept();
			shouldRestoreFocusAfterAcceptRef.current = false;
			setCurrentDispute( {
				...currentDispute,
				...closedDispute,
			} );
			setIsAcceptModalOpen( false );
			if ( shouldRestoreFocus ) {
				setShouldFocusDisputeDetails( true );
			}
			dispatch( 'core/notices' ).createSuccessNotice(
				__( 'Dispute accepted.', 'woocommerce' )
			);
			recordEvent( 'wcpay_dispute_accepted', {
				dispute_id: disputeId,
			} );
		} catch ( error ) {
			dispatch( 'core/notices' ).createErrorNotice(
				getErrorMessage(
					error,
					__(
						'Unable to accept the WooPayments dispute.',
						'woocommerce'
					)
				)
			);
			shouldRestoreFocusAfterAcceptRef.current = false;
		} finally {
			setIsAccepting( false );
		}
	};

	return (
		<section
			className="woocommerce-woopayments-overview-card woocommerce-woopayments-money-movement__dispute-details"
			aria-labelledby="woocommerce-woopayments-transaction-dispute-details-heading"
		>
			<h3
				id="woocommerce-woopayments-transaction-dispute-details-heading"
				ref={ disputeHeadingRef }
				tabIndex={ -1 }
			>
				{ __( 'Dispute details', 'woocommerce' ) }
			</h3>
			<dl className="woocommerce-woopayments-money-movement__details woocommerce-woopayments-money-movement__details--nested">
				<DetailRow
					label={ __( 'Dispute ID', 'woocommerce' ) }
					value={ disputeId }
				/>
				<DetailRow
					label={ __( 'Reason', 'woocommerce' ) }
					value={ formatLabel( currentDispute.reason ) }
				/>
				<DetailRow
					label={ __( 'Status', 'woocommerce' ) }
					value={ getDisputeStatusLabel( currentDispute.status ) }
				/>
				<DetailRow
					label={ __( 'Response due', 'woocommerce' ) }
					value={ dueDate ? formatDate( dueDate ) : '' }
				/>
				<DetailRow
					label={ __( 'Amount', 'woocommerce' ) }
					value={ formatAmount(
						currentDispute.amount ?? transaction.amount,
						currentDispute.currency || transaction.currency
					) }
				/>
				<DetailRow
					label={ __( 'Customer', 'woocommerce' ) }
					value={ getCustomerLabel( currentDispute, transaction ) }
				/>
			</dl>
			<div
				ref={ disputeResponseRef }
				className="woocommerce-woopayments-money-movement__dispute-response"
			>
				{ isAwaitingResponseStatus ? (
					<RespondToDisputeActions
						dispute={ currentDispute }
						isAccepting={ isAccepting }
						onAccept={ () => setIsAcceptModalOpen( true ) }
					/>
				) : (
					<ResolvedDisputeActions dispute={ currentDispute } />
				) }
			</div>
			{ isAcceptModalOpen && (
				<Modal
					title={ __( 'Accept the dispute?', 'woocommerce' ) }
					onRequestClose={ closeAcceptModal }
				>
					<p>
						{ sprintf(
							/* translators: %s: dispute ID. */
							__(
								'Accepting dispute %s marks it as lost. This action cannot be undone.',
								'woocommerce'
							),
							disputeId
						) }
					</p>
					<div className="woocommerce-woopayments-money-movement__dispute-modal-actions">
						<Button variant="tertiary" onClick={ closeAcceptModal }>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							disabled={ isAccepting }
							accessibleWhenDisabled
							isBusy={ isAccepting }
							onClick={ handleAcceptDispute }
						>
							{ __( 'Accept dispute', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</section>
	);
};
