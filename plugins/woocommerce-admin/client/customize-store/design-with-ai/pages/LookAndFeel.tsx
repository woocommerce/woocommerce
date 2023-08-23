/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';

export const LookAndFeel = ( {
	sendEvent,
	context,
}: {
	sendEvent: ( event: { type: 'LOOK_AND_FEEL_COMPLETE' } ) => void;
	context: designWithAiStateMachineContext;
} ) => {
	return (
		<div>
			<h1>Look and Feel</h1>
			<div>{ JSON.stringify( context ) }</div>
			<button
				onClick={ () =>
					sendEvent( { type: 'LOOK_AND_FEEL_COMPLETE' } )
				}
			>
				complete
			</button>
		</div>
	);
};
