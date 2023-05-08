/**
 * Internal dependencies
 */
import { BusinessLocationEvent } from '../index';

export const BusinessLocation = ( {
	sendEvent,
}: {
	sendEvent: ( event: BusinessLocationEvent ) => void;
} ) => {
	return (
		<>
			<div>Business Location</div>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'BUSINESS_LOCATION_COMPLETED',
						payload: { businessInfo: { location: 'Zanarkand' } },
					} )
				}
			>
				Next
			</button>
		</>
	);
};
