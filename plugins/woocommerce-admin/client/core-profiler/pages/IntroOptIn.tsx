/**
 * Internal dependencies
 */
import { IntroOptInEvent } from '../index';

export const IntroOptIn = ( {
	sendEvent,
}: {
	sendEvent: ( event: IntroOptInEvent ) => void;
} ) => {
	return (
		<>
			<div>Intro Opt In</div>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'INTRO_COMPLETED',
						payload: { optInDataSharing: true },
					} )
				}
			>
				Next
			</button>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'INTRO_SKIPPED',
						payload: { optInDataSharing: false },
					} )
				}
			>
				Skip
			</button>
		</>
	);
};
