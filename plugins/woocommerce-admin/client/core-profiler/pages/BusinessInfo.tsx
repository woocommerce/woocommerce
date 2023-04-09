/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext, BusinessInfoEvent } from '../index';

export const BusinessInfo = ( {
	context,
	sendEvent,
}: {
	context: CoreProfilerStateMachineContext;
	sendEvent: ( event: BusinessInfoEvent ) => void;
} ) => {
	return (
		<>
			<div>Business Info</div>
			<div>{ context.geolocatedLocation.location }</div>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'BUSINESS_INFO_COMPLETED',
						payload: {
							businessInfo: {
								foo: { bar: 'qux' },
								location: 'US:CA',
							},
						},
					} )
				}
			>
				Next
			</button>
		</>
	);
};
