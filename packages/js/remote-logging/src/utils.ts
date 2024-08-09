/**
 * Internal dependencies
 */
import { LogData } from './types';

/**
 * Deeply merges two LogData objects.
 *
 * @param target - The target LogData object.
 * @param source - The source LogData object to merge into the target.
 * @return The merged LogData object.
 */
export function mergeLogData( target: LogData, source: Partial< LogData > ) {
	const result = { ...target };
	for ( const key in source ) {
		if ( Object.prototype.hasOwnProperty.call( source, key ) ) {
			const typedKey = key as keyof LogData;

			if ( typedKey === 'extra' || typedKey === 'properties' ) {
				result[ typedKey ] = {
					...( target[ typedKey ] as object ),
					...( source[ typedKey ] as object ),
				};
			} else if (
				typedKey === 'tags' &&
				Array.isArray( source[ typedKey ] )
			) {
				result[ typedKey ] = [
					...( Array.isArray( target[ typedKey ] )
						? ( target[ typedKey ] as string[] )
						: [] ),
					...( source[ typedKey ] as string[] ),
				];
			} else {
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				result[ typedKey ] = source[ typedKey ] as any;
			}
		}
	}
	return result;
}

export const isDevelopmentEnvironment = process.env.NODE_ENV === 'development';
