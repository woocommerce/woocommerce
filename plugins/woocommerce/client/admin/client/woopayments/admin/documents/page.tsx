/**
 * External dependencies
 */
import { Button, Notice } from '@wordpress/components';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { useLocation, useNavigate } from 'react-router-dom';

// @ts-expect-error - Use the WordPress-bundled DataViews entry in wp-admin builds.
import { DataViews, type Field } from '@wordpress/dataviews/wp'; // eslint-disable-line @woocommerce/dependency-group

/**
 * Internal dependencies
 */
import {
	buildWooPaymentsDocumentUrl,
	getWooPaymentsDocuments,
	getWooPaymentsDocumentsAccount,
	getWooPaymentsDocumentsSummary,
} from './data';
import {
	buildDocumentsRoutePath,
	dataViewsViewToDocumentsQuery,
	documentsQueryToDataViewsView,
	parseDocumentsQuery,
} from './query';
import type {
	WooPaymentsDocument,
	WooPaymentsDocumentsAccountResponse,
	WooPaymentsDocumentsDataView,
	WooPaymentsDocumentsSummary,
	WooPaymentsVatDetails,
} from './types';
import { WooPaymentsVatModal } from './vat-modal';
import { formatDate } from '../money-movement/utils';
import { SpotlightPromotion } from '../../promotions/spotlight';

type DocumentsAccountState = {
	enabled: boolean;
	hasSubmittedVatData: boolean;
	country: string;
	isTestMode: boolean;
};

type PendingDownload = {
	document: WooPaymentsDocument;
	target: '_blank' | '_self';
};

const getDocumentId = ( document: WooPaymentsDocument ) =>
	document.document_id || document.id || '';

const isVatInvoice = ( document: WooPaymentsDocument ) =>
	document.type === 'vat_invoice';

const getErrorMessage = ( error: unknown, fallback: string ): string => {
	if ( error instanceof Error && error.message ) {
		return error.message;
	}

	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}

	return fallback;
};

const getDocumentsAccountState = (
	response: WooPaymentsDocumentsAccountResponse
): DocumentsAccountState => {
	const account = response.account;
	const isExplicitTestMode =
		!! account?.test_mode ||
		!! account?.test_drive ||
		!! account?.sandbox ||
		account?.mode === 'test';

	return {
		enabled: !! response.documents?.enabled,
		hasSubmittedVatData: !! response.documents?.has_submitted_vat_data,
		country: response.documents?.country || '',
		isTestMode: !! account?.connected && isExplicitTestMode,
	};
};

const getDocumentTypeLabel = ( type?: string ) => {
	if ( type === 'vat_invoice' ) {
		return __( 'Tax Invoice', 'woocommerce' );
	}

	if ( ! type ) {
		return __( 'Document', 'woocommerce' );
	}

	return type
		.replace( /_/g, ' ' )
		.replace( /\b\w/g, ( match ) => match.toUpperCase() );
};

const getDocumentDescription = ( document: WooPaymentsDocument ) => {
	if ( isVatInvoice( document ) ) {
		return sprintf(
			/* translators: 1: Period start date. 2: Period end date. */
			__( 'Tax invoice for %1$s to %2$s', 'woocommerce' ),
			formatDate( document.period_from ),
			formatDate( document.period_to )
		);
	}

	if ( typeof document.description === 'string' && document.description ) {
		return document.description;
	}

	return '-';
};

const getSummaryCount = (
	summary: WooPaymentsDocumentsSummary,
	totalCount: number
) => {
	if ( typeof summary.count === 'number' ) {
		return summary.count;
	}

	if ( typeof summary.total_count === 'number' ) {
		return summary.total_count;
	}

	return totalCount;
};

const getDirectDownloadDocument = (
	search: string
): WooPaymentsDocument | null => {
	const params = new URLSearchParams( search || '' );
	const documentId = params.get( 'document_id' );
	const documentType = params.get( 'document_type' );

	if ( ! documentId || ! documentType ) {
		return null;
	}

	return {
		document_id: documentId,
		type: documentType,
	};
};

export const WooPaymentsDocumentsPage = () => {
	const location = useLocation();
	const navigate = useNavigate();
	const query = useMemo(
		() => parseDocumentsQuery( location.search || '' ),
		[ location.search ]
	);
	const [ documents, setDocuments ] = useState< WooPaymentsDocument[] >( [] );
	const [ summary, setSummary ] = useState< WooPaymentsDocumentsSummary >(
		{}
	);
	const [ totalCount, setTotalCount ] = useState( 0 );
	const [ accountState, setAccountState ] =
		useState< DocumentsAccountState | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ pendingDownload, setPendingDownload ] =
		useState< PendingDownload | null >( null );
	const directDownloadAttempted = useRef( false );
	const view = useMemo(
		() => documentsQueryToDataViewsView( query ),
		[ query ]
	);

	const openDocument = useCallback(
		( document: WooPaymentsDocument, target: '_blank' | '_self' ) => {
			const documentId = getDocumentId( document );

			if ( ! documentId ) {
				return;
			}

			if (
				isVatInvoice( document ) &&
				! accountState?.hasSubmittedVatData
			) {
				setPendingDownload( {
					document,
					target,
				} );
				return;
			}

			window.open( buildWooPaymentsDocumentUrl( documentId ), target );
		},
		[ accountState?.hasSubmittedVatData ]
	);

	useEffect( () => {
		let isMounted = true;

		setIsLoading( true );
		setErrorMessage( null );

		getWooPaymentsDocumentsAccount()
			.then( async ( response ) => {
				if ( ! isMounted ) {
					return;
				}

				const nextAccountState = getDocumentsAccountState( response );
				setAccountState( nextAccountState );

				if ( ! nextAccountState.enabled ) {
					setDocuments( [] );
					setSummary( {} );
					setTotalCount( 0 );
					setIsLoading( false );
					return;
				}

				const [ listResponse, summaryResponse ] = await Promise.all( [
					getWooPaymentsDocuments( query ),
					getWooPaymentsDocumentsSummary( query ),
				] );

				if ( ! isMounted ) {
					return;
				}

				setDocuments( listResponse.data || [] );
				setTotalCount( listResponse.total_count || 0 );
				setSummary( summaryResponse || {} );
				setIsLoading( false );
			} )
			.catch( ( error ) => {
				if ( ! isMounted ) {
					return;
				}

				setErrorMessage(
					getErrorMessage(
						error,
						__(
							'There was a problem loading WooPayments documents.',
							'woocommerce'
						)
					)
				);
				setIsLoading( false );
			} );

		return () => {
			isMounted = false;
		};
	}, [ query ] );

	useEffect( () => {
		if (
			directDownloadAttempted.current ||
			isLoading ||
			! accountState?.enabled
		) {
			return;
		}

		const document = getDirectDownloadDocument( location.search );

		if ( ! document ) {
			return;
		}

		directDownloadAttempted.current = true;
		openDocument( document, '_self' );
	}, [ accountState?.enabled, isLoading, location.search, openDocument ] );

	const fields = useMemo< Field< WooPaymentsDocument >[] >(
		() => [
			{
				id: 'date',
				type: 'date',
				label: __( 'Date', 'woocommerce' ),
				enableHiding: true,
				filterBy: {
					operators: [ 'before', 'after', 'between' ],
				},
				render: ( { item }: { item: WooPaymentsDocument } ) =>
					formatDate( item.date ),
			},
			{
				id: 'type',
				type: 'text',
				label: __( 'Type', 'woocommerce' ),
				enableHiding: false,
				filterBy: {
					operators: [ 'is', 'isNot' ],
				},
				elements: [
					{
						label: __( 'Tax Invoice', 'woocommerce' ),
						value: 'vat_invoice',
					},
				],
				render: ( { item }: { item: WooPaymentsDocument } ) =>
					getDocumentTypeLabel( item.type ),
			},
			{
				id: 'description',
				label: __( 'Description', 'woocommerce' ),
				enableHiding: true,
				render: ( { item }: { item: WooPaymentsDocument } ) =>
					getDocumentDescription( item ),
			},
			{
				id: 'actions',
				label: __( 'Download', 'woocommerce' ),
				enableHiding: false,
				render: ( { item }: { item: WooPaymentsDocument } ) => {
					const documentId = getDocumentId( item );
					const documentType = getDocumentTypeLabel( item.type );

					return (
						<Button
							variant="secondary"
							disabled={ ! documentId }
							aria-label={ sprintf(
								/* translators: 1: Document type. 2: Document ID. */
								__( 'Download %1$s %2$s', 'woocommerce' ),
								documentType,
								documentId
							) }
							onClick={ () => openDocument( item, '_blank' ) }
						>
							{ __( 'Download', 'woocommerce' ) }
						</Button>
					);
				},
			},
		],
		[ openDocument ]
	);

	const handleChangeView = ( nextView: WooPaymentsDocumentsDataView ) => {
		navigate(
			buildDocumentsRoutePath(
				'/woopayments/documents',
				dataViewsViewToDocumentsQuery( nextView, query )
			)
		);
	};

	const handleVatCompleted = ( details: WooPaymentsVatDetails ) => {
		setAccountState( ( current ) =>
			current
				? {
						...current,
						hasSubmittedVatData: true,
				  }
				: current
		);
		setPendingDownload( ( current ) => {
			if ( current ) {
				window.open(
					buildWooPaymentsDocumentUrl(
						getDocumentId( current.document )
					),
					current.target
				);
			}

			return null;
		} );

		return details;
	};

	if ( isLoading && ! accountState ) {
		return (
			<div role="status" aria-live="polite" aria-busy="true">
				{ __( 'Loading Documents…', 'woocommerce' ) }
			</div>
		);
	}

	if ( errorMessage ) {
		return (
			<div className="woocommerce-woopayments-documents">
				<h1>{ __( 'Documents', 'woocommerce' ) }</h1>
				<Notice status="error" isDismissible={ false }>
					{ errorMessage }
				</Notice>
			</div>
		);
	}

	if ( ! accountState?.enabled ) {
		return (
			<div className="woocommerce-woopayments-documents">
				<h1>{ __( 'Documents', 'woocommerce' ) }</h1>
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'Documents are not available for this WooPayments account.',
						'woocommerce'
					) }
				</Notice>
			</div>
		);
	}

	const summaryCount = getSummaryCount( summary, totalCount );
	const header = (
		<div className="woocommerce-woopayments-documents__header">
			<div>
				<div className="woocommerce-woopayments-documents__title-row">
					<h1>{ __( 'Documents', 'woocommerce' ) }</h1>
					{ accountState.isTestMode && (
						<span className="woocommerce-woopayments-documents__test-mode">
							{ __( 'Test Mode', 'woocommerce' ) }
						</span>
					) }
				</div>
				<p>
					{ sprintf(
						/* translators: %d: Number of documents. */
						_n(
							'%d document',
							'%d documents',
							summaryCount,
							'woocommerce'
						),
						summaryCount
					) }
				</p>
			</div>
		</div>
	);

	return (
		<div className="woocommerce-woopayments-documents">
			<div
				role="status"
				aria-live="polite"
				aria-atomic="true"
				className="screen-reader-text"
			>
				{ isLoading ? __( 'Loading Documents…', 'woocommerce' ) : '' }
			</div>
			<DataViews
				view={ view }
				onChangeView={ handleChangeView }
				fields={ fields }
				data={ documents }
				isLoading={ isLoading }
				search
				searchLabel={ __( 'Search documents', 'woocommerce' ) }
				header={ header }
				paginationInfo={ {
					totalItems: totalCount,
					totalPages: Math.ceil(
						totalCount / ( view.perPage || 25 )
					),
				} }
				defaultLayouts={ {
					table: {},
				} }
				getItemId={ getDocumentId }
			/>
			{ ! isLoading && documents.length === 0 && (
				<div className="woocommerce-woopayments-documents__empty">
					{ __( 'No documents found.', 'woocommerce' ) }
				</div>
			) }
			<SpotlightPromotion />
			{ pendingDownload && (
				<WooPaymentsVatModal
					country={ accountState.country }
					onClose={ () => setPendingDownload( null ) }
					onCompleted={ handleVatCompleted }
				/>
			) }
		</div>
	);
};

export default WooPaymentsDocumentsPage;
