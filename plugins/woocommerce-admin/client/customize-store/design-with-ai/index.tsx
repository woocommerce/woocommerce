/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';

export type events = { type: 'THEME_SUGGESTED' };
export const DesignWithAi: CustomizeStoreComponent = ( { sendEvent } ) => {
	return (
		<>
			<h1>Design with AI</h1>
			<button onClick={ () => sendEvent( { type: 'THEME_SUGGESTED' } ) }>
				Assembler Hub
			</button>
		</>
	);
};
