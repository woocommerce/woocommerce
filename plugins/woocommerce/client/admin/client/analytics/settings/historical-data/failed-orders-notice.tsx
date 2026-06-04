/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';

interface FailedImportsStatus {
	failed_count: number;
	failed_overflow_count: number;
}

interface RetryFailedResponse {
	success: boolean;
	message: string;
	retried_count: number;
	pruned_count: number;
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
		fetchStatus();
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
				err instanceof Error
					? err.message
					: __( 'Failed to retry order imports.', 'woocommerce' )
			);
		} finally {
			setIsRetrying( false );
		}
	};

	const message =
		overflowCount > 0
			? sprintf(
					/* translators: %d: number of stored failed orders */
					__(
						'More than %d orders failed to import. To recover all missed orders, run the import above with "Skip previously imported customers and orders" checked.',
						'woocommerce'
					),
					failedCount
			  )
			: sprintf(
					/* translators: %d: number of failed orders */
					_n(
						'%d order failed to import. Check the wc-analytics-order-import log for details.',
						'%d orders failed to import. Check the wc-analytics-order-import log for details.',
						failedCount,
						'woocommerce'
					),
					failedCount
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
				onClick={ handleRetry }
			>
				{ __( 'Retry failed imports', 'woocommerce' ) }
			</Button>
		</Notice>
	);
}

export default FailedOrdersNotice;
