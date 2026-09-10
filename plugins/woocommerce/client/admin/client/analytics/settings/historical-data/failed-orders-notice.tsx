/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { getAdminLink } from '@woocommerce/settings';

const LOG_URL_PATH =
	'admin.php?page=wc-status&tab=logs&source=wc-analytics-order-import';

interface FailedImportsStatus {
	failed_count: number;
	failed_overflow_count: number;
}

interface RetryFailedResponse {
	success: boolean;
	message: string;
	retried_count: number;
	pruned_count: number;
	already_scheduled_count: number;
	error_count: number;
}

/**
 * Extract a user-facing message from a caught request error.
 *
 * `@wordpress/api-fetch` rejects with the parsed REST error object
 * (`{ code, message }`), which is a plain object — not an `Error` instance —
 * so narrow on the `message` property instead of the constructor.
 */
function getErrorMessage( err: unknown, fallback: string ): string {
	if (
		typeof err === 'object' &&
		err !== null &&
		'message' in err &&
		typeof err.message === 'string' &&
		err.message !== ''
	) {
		return err.message;
	}
	return fallback;
}

/**
 * Shows a warning when some orders failed to import into analytics, with a
 * button to schedule a re-import of just those orders.
 *
 * Renders nothing when there are no recorded failures or when the status
 * request fails (the notice is an auxiliary affordance).
 */
function FailedOrdersNotice() {
	const [ status, setStatus ] = useState< FailedImportsStatus | null >(
		null
	);
	const [ isRetrying, setIsRetrying ] = useState( false );
	const { createNotice } = useDispatch( 'core/notices' );

	const fetchStatus = useCallback( async () => {
		try {
			const data = await apiFetch< FailedImportsStatus >( {
				path: '/wc-analytics/imports/status',
			} );
			setStatus( data );
		} catch ( err ) {
			// Fail silently — the notice is an auxiliary affordance.
		}
	}, [] );

	useEffect( () => {
		void fetchStatus();
	}, [ fetchStatus ] );

	const failedCount = status?.failed_count ?? 0;
	const overflowCount = status?.failed_overflow_count ?? 0;

	if ( failedCount === 0 ) {
		return null;
	}

	const handleRetry = async () => {
		setIsRetrying( true );
		try {
			const response = await apiFetch< RetryFailedResponse >( {
				path: '/wc-analytics/imports/retry-failed',
				method: 'POST',
			} );
			createNotice( 'success', response.message );
			await fetchStatus();
		} catch ( err ) {
			createNotice(
				'error',
				getErrorMessage(
					err,
					__( 'Failed to retry order imports.', 'woocommerce' )
				)
			);
		} finally {
			setIsRetrying( false );
		}
	};

	const logLink = (
		<a
			href={ getAdminLink( LOG_URL_PATH ) }
			aria-label={ __( 'View the order import log', 'woocommerce' ) }
		/>
	);

	const template =
		overflowCount > 0
			? /* translators: %d: number of failed orders currently stored (additional failures were dropped past the storage limit). <link> is a link to the order import log. */
			  __(
					'More than %d orders failed to import. To recover all missed orders, run the import above with "Skip previously imported customers and orders" checked. <link>View the log</link> for details.',
					'woocommerce'
			  )
			: /* translators: %d: number of failed orders. <link> is a link to the order import log. */
			  _n(
					'%d order failed to import. <link>View the log</link> for details.',
					'%d orders failed to import. <link>View the log</link> for details.',
					failedCount,
					'woocommerce'
			  );

	const message = createInterpolateElement(
		sprintf( template, failedCount ),
		{ link: logLink }
	);

	return (
		<Notice
			className="woocommerce-settings-historical-data__failed-orders"
			status="warning"
			isDismissible={ false }
		>
			<p>{ message }</p>
			<Button
				variant="secondary"
				isBusy={ isRetrying }
				disabled={ isRetrying }
				aria-disabled={ isRetrying }
				onClick={ handleRetry }
			>
				{ __( 'Retry failed imports', 'woocommerce' ) }
			</Button>
		</Notice>
	);
}

export default FailedOrdersNotice;
