/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';

export const ToneOfVoice = ( {
	sendEvent,
	context,
}: {
	sendEvent: ( event: { type: 'TONE_OF_VOICE_COMPLETE' } ) => void;
	context: designWithAiStateMachineContext;
} ) => {
	return (
		<div>
			<h1>Tone of Voice</h1>
			<div>{ JSON.stringify( context ) }</div>
			<button
				onClick={ () =>
					sendEvent( { type: 'TONE_OF_VOICE_COMPLETE' } )
				}
			>
				complete
			</button>
		</div>
	);
};
