/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';

export type businessInfoDescriptionCompleteEvent = {
	type: 'BUSINESS_INFO_DESCRIPTION_COMPLETE';
	payload: string;
};
export const BusinessInfoDescription = ( {
	sendEvent,
	context,
}: {
	sendEvent: ( event: businessInfoDescriptionCompleteEvent ) => void;
	context: designWithAiStateMachineContext;
} ) => {
	const [ businessInfoDescription, setBusinessInfoDescription ] = useState(
		context.businessInfoDescription.descriptionText
	);

	return (
		<div>
			<h1>Business Info Description</h1>
			<div>{ JSON.stringify( context ) }</div>
			{ /* add a controlled text area that saves to state */ }
			<input
				type="text"
				value={ businessInfoDescription }
				onChange={ ( e ) =>
					setBusinessInfoDescription( e.target.value )
				}
			/>
			<button
				onClick={ () =>
					sendEvent( {
						type: 'BUSINESS_INFO_DESCRIPTION_COMPLETE',
						payload: businessInfoDescription,
					} )
				}
			>
				complete
			</button>
		</div>
	);
};
