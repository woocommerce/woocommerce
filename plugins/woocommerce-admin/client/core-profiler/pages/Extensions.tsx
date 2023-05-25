/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext, ExtensionsEvent } from '../index';

export const Extensions = ( {
	context,
	sendEvent,
}: {
	context: CoreProfilerStateMachineContext;
	sendEvent: ( payload: ExtensionsEvent ) => void;
} ) => {
	return (
		<>
			<div>Extensions</div>
			<div>{ JSON.stringify( context.extensionsAvailable ) }</div>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'EXTENSIONS_COMPLETED',
						payload: {
							extensionsSelected: [ 'woocommerce-payments' ],
						},
					} )
				}
			>
				Next
			</button>
		</>
	);
};
