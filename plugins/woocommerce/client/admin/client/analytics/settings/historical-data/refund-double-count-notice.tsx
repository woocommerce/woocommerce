/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ImportWarningNotice from './import-warning-notice';

interface RefundDoubleCountStatus {
	refund_double_count: number;
	refund_double_count_scan_complete: boolean;
	refund_double_count_fix_in_progress: boolean;
}

interface FixResponse {
	success: boolean;
	message: string;
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
 * Warns when historical orders have refunds that are double-counted in Analytics
 * (the rows written by the bug fixed in PR #66320), with a button to re-import
 * just those orders and correct the totals.
 *
 * Renders nothing until the one-time detection scan has finished, when there are
 * no affected orders, while a fix is running, or when the status request fails —
 * the notice is an auxiliary affordance.
 */
function RefundDoubleCountNotice() {
	const [ status, setStatus ] = useState< RefundDoubleCountStatus | null >(
		null
	);
	const [ isFixing, setIsFixing ] = useState( false );
	const { createNotice } = useDispatch( 'core/notices' );

	const fetchStatus = useCallback( async () => {
		try {
			const data = await apiFetch< RefundDoubleCountStatus >( {
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

	const scanComplete = status?.refund_double_count_scan_complete ?? false;
	const count = status?.refund_double_count ?? 0;
	const fixInProgress = status?.refund_double_count_fix_in_progress ?? false;

	if ( ! scanComplete || count === 0 || fixInProgress ) {
		return null;
	}

	const handleFix = async () => {
		setIsFixing( true );
		try {
			const response = await apiFetch< FixResponse >( {
				path: '/wc-analytics/imports/fix-refund-double-counting',
				method: 'POST',
			} );
			createNotice( 'success', response.message );
			await fetchStatus();
		} catch ( err ) {
			createNotice(
				'error',
				getErrorMessage(
					err,
					__( 'Failed to re-import affected orders.', 'woocommerce' )
				)
			);
		} finally {
			setIsFixing( false );
		}
	};

	const message = sprintf(
		/* translators: %d: number of orders whose refunds are double-counted in Analytics. */
		_n(
			'%d order has refunds that are counted twice in your Analytics reports, overstating your returns. Re-import it to correct the totals.',
			'%d orders have refunds that are counted twice in your Analytics reports, overstating your returns. Re-import them to correct the totals.',
			count,
			'woocommerce'
		),
		count
	);

	return (
		<ImportWarningNotice
			className="woocommerce-settings-historical-data__refund-double-count"
			message={ message }
			buttonLabel={ __( 'Re-import affected orders', 'woocommerce' ) }
			busyLabel={ __( 'Re-importing affected orders…', 'woocommerce' ) }
			isBusy={ isFixing }
			onAction={ handleFix }
		/>
	);
}

export default RefundDoubleCountNotice;
