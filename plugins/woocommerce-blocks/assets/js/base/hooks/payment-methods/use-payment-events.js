/**
 * External dependencies
 */
import { useState, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STATUS } from './constants';
const { STARTED, ERROR, FAILED, SUCCESS, PRISTINE } = STATUS;

const usePaymentEvents = () => {
	const { paymentStatus, setPaymentStatus } = useState( PRISTINE );
	const dispatch = useMemo(
		() => ( {
			started: () => setPaymentStatus( STARTED ),
			error: () => setPaymentStatus( ERROR ),
			failed: () => setPaymentStatus( FAILED ),
			success: () => setPaymentStatus( SUCCESS ),
		} ),
		[ setPaymentStatus ]
	);
	const select = useMemo(
		() => ( {
			isPristine: () => paymentStatus === PRISTINE,
			isStarted: () => paymentStatus === STARTED,
			isFinished: () =>
				[ ERROR, FAILED, SUCCESS ].includes( paymentStatus ),
			hasError: () => paymentStatus === ERROR,
			hasFailed: () => paymentStatus === FAILED,
			isSuccessful: () => paymentStatus === SUCCESS,
		} ),
		[ paymentStatus ]
	);
	return { dispatch, select };
};

export default usePaymentEvents;
