/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';

export type events =
	| { type: 'DESIGN_WITH_AI' }
	| { type: 'CLICKED_ON_BREADCRUMB' }
	| { type: 'SELECTED_BROWSE_ALL_THEMES' }
	| { type: 'SELECTED_ACTIVE_THEME' }
	| { type: 'SELECTED_NEW_THEME'; payload: { theme: string } };

export * as actions from './actions';
export * as services from './services';

export const Intro: CustomizeStoreComponent = ( { sendEvent, context } ) => {
	const {
		intro: { themeCards, activeTheme },
	} = context;
	return (
		<>
			<h1>Intro</h1>
			<div>Active theme: { activeTheme }</div>
			{ themeCards?.map( ( themeCard ) => (
				<button
					key={ themeCard.name }
					onClick={ () =>
						sendEvent( {
							type: 'SELECTED_NEW_THEME',
							payload: { theme: themeCard.name },
						} )
					}
				>
					{ themeCard.name }
				</button>
			) ) }
			<button onClick={ () => sendEvent( { type: 'DESIGN_WITH_AI' } ) }>
				Design with AI
			</button>
			<button
				onClick={ () => sendEvent( { type: 'SELECTED_ACTIVE_THEME' } ) }
			>
				Assembler Hub
			</button>
		</>
	);
};
