/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';

export function median( array ) {
	if ( ! array || ! array.length ) return undefined;

	const numbers = [ ...array ].sort( ( a, b ) => a - b );
	const middleIndex = Math.floor( numbers.length / 2 );

	if ( numbers.length % 2 === 0 ) {
		return ( numbers[ middleIndex - 1 ] + numbers[ middleIndex ] ) / 2;
	}
	return numbers[ middleIndex ];
}

export function readFile( filePath ) {
	if ( ! existsSync( filePath ) ) {
		throw new Error( `File does not exist: ${ filePath }` );
	}

	return readFileSync( filePath, 'utf8' ).trim();
}

export async function getTotalBlockingTime( page, idleWait ) {
	const totalBlockingTime = await page.evaluate( async ( waitTime ) => {
		return new Promise( ( resolve ) => {
			const longTaskEntries = [];
			// Create a performance observer to observe long task entries
			new PerformanceObserver( ( list ) => {
				const entries = list.getEntries();
				// Store each long task entry in the longTaskEntries array
				entries.forEach( ( entry ) => longTaskEntries.push( entry ) );
			} ).observe( { type: 'longtask', buffered: true } );

			// Give some time to collect entries
			setTimeout( () => {
				// Calculate the total blocking time by summing the durations of all long tasks
				const tbt = longTaskEntries.reduce(
					( acc, entry ) => acc + entry.duration,
					0
				);
				resolve( tbt );
			}, waitTime );
		} );
	}, idleWait );
	return totalBlockingTime;
}
