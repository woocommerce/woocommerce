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

export function getMetricUnit( metric ) {
	if ( metric.endsWith( 'Size' ) ) {
		return 'KB';
	}

	if ( metric.endsWith( 'Count' ) ) {
		return 'count';
	}

	return 'ms';
}

export function formatMetricValue( metric, value ) {
	return `${ value } ${ getMetricUnit( metric ) }`;
}

export async function getWooEditorAssetMetrics( page ) {
	return await page.evaluate( () => {
		const WOO_BLOCKS_ASSETS_PATH =
			'/wp-content/plugins/woocommerce/assets/client/blocks/';
		const KB = 1024;
		const assetUrls = new Set();
		const metrics = {
			wooEditorAssetSize: 0,
			wooEditorScriptSize: 0,
			wooEditorStyleSize: 0,
			wooEditorEncodedAssetSize: 0,
			wooEditorEncodedScriptSize: 0,
			wooEditorEncodedStyleSize: 0,
			wooEditorTransferAssetSize: 0,
			wooEditorTransferScriptSize: 0,
			wooEditorTransferStyleSize: 0,
			wooEditorAssetCount: 0,
		};

		const roundSize = ( value ) => Math.round( value * 100 ) / 100;

		performance.getEntriesByType( 'resource' ).forEach( ( entry ) => {
			const url = new URL( entry.name, window.location.href );
			const normalizedUrl = url.origin + url.pathname;

			if (
				! url.pathname.includes( WOO_BLOCKS_ASSETS_PATH ) ||
				! /\.(js|css)$/.test( url.pathname ) ||
				assetUrls.has( normalizedUrl )
			) {
				return;
			}

			assetUrls.add( normalizedUrl );

			const decodedSize = entry.decodedBodySize || 0;
			const encodedSize = entry.encodedBodySize || 0;
			const transferSize = entry.transferSize || 0;

			metrics.wooEditorAssetSize += decodedSize;
			metrics.wooEditorEncodedAssetSize += encodedSize;
			metrics.wooEditorTransferAssetSize += transferSize;
			metrics.wooEditorAssetCount += 1;

			if ( url.pathname.endsWith( '.js' ) ) {
				metrics.wooEditorScriptSize += decodedSize;
				metrics.wooEditorEncodedScriptSize += encodedSize;
				metrics.wooEditorTransferScriptSize += transferSize;
			}

			if ( url.pathname.endsWith( '.css' ) ) {
				metrics.wooEditorStyleSize += decodedSize;
				metrics.wooEditorEncodedStyleSize += encodedSize;
				metrics.wooEditorTransferStyleSize += transferSize;
			}
		} );

		return {
			wooEditorAssetSize: roundSize( metrics.wooEditorAssetSize / KB ),
			wooEditorScriptSize: roundSize( metrics.wooEditorScriptSize / KB ),
			wooEditorStyleSize: roundSize( metrics.wooEditorStyleSize / KB ),
			wooEditorEncodedAssetSize: roundSize(
				metrics.wooEditorEncodedAssetSize / KB
			),
			wooEditorEncodedScriptSize: roundSize(
				metrics.wooEditorEncodedScriptSize / KB
			),
			wooEditorEncodedStyleSize: roundSize(
				metrics.wooEditorEncodedStyleSize / KB
			),
			wooEditorTransferAssetSize: roundSize(
				metrics.wooEditorTransferAssetSize / KB
			),
			wooEditorTransferScriptSize: roundSize(
				metrics.wooEditorTransferScriptSize / KB
			),
			wooEditorTransferStyleSize: roundSize(
				metrics.wooEditorTransferStyleSize / KB
			),
			wooEditorAssetCount: metrics.wooEditorAssetCount,
		};
	} );
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
