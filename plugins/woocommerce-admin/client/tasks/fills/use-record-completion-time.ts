/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

const useRecordCompletionTime = ( taskName: string, startTime?: number ) => {
	const _startTime = useRef( startTime || window.performance.now() );

	const recordCompletionTime = () => {
		recordEvent( 'task_completion_time', {
			task_name: taskName,
			time: window.performance.now() - _startTime.current,
		} );
	};

	return {
		recordCompletionTime,
	};
};

export default useRecordCompletionTime;
