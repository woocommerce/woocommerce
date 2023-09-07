/**
 * Internal dependencies
 */
import { designWithAiStateMachineContext } from '../types';

export const ApiCallLoader = ( {
	context,
}: {
	context: designWithAiStateMachineContext;
} ) => {
	return (
		<div>
			<h1>Loader</h1>
			<div>{ JSON.stringify( context ) }</div>
		</div>
	);
};
