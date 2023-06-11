/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '..';

export type ComponentMeta = {
	/** React component that is rendered when state matches the location this meta key is defined */
	component: ( arg0: ComponentProps ) => JSX.Element;
	/** number between 0 - 100 */
	progress: number;
};

export type ComponentProps = {
	navigationProgress: number | undefined;
	sendEvent: unknown;
	context: CoreProfilerStateMachineContext;
};

/**
 * Does a depth-first search of a meta object to find the first instance of a component.
 */
export function findComponentMeta(
	obj: Record< string, unknown >
): ComponentMeta | undefined {
	for ( const key in obj ) {
		if ( key === 'component' ) {
			return obj as ComponentMeta;
		} else if ( typeof obj[ key ] === 'object' && obj[ key ] !== null ) {
			const found = findComponentMeta(
				obj[ key ] as Record< string, unknown >
			);
			if ( found !== undefined ) {
				return found;
			}
		}
	}

	return undefined;
}
