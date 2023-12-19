/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

export class PerformanceUtils {
	private page: Page;
	private static readonly PERFORMANCE_REPORT_DIRECTORY = path.join(
		__dirname,
		'reports'
	);
	private static readonly PERFORMANCE_REPORT_FILENAME =
		'e2e-cart-performance.json';

	constructor( page: Page ) {
		this.page = page;
	}

	async getLoadingDurations(): Promise< ReturnType< Page[ 'evaluate' ] > > {
		return this.page.evaluate( () => {
			const [ navigationEntry ] = performance.getEntriesByType(
				'navigation'
			) as PerformanceNavigationTiming[];
			const paintTimings = performance.getEntriesByType( 'paint' );

			const calculateTiming = ( name: string ) => {
				const paint = paintTimings.find(
					( entry ) => entry.name === name
				);
				return paint
					? paint.startTime - navigationEntry.responseEnd
					: 0;
			};

			return {
				serverResponse:
					navigationEntry.responseStart -
					navigationEntry.requestStart,
				firstPaint: calculateTiming( 'first-paint' ),
				domContentLoaded:
					navigationEntry.domContentLoadedEventEnd -
					navigationEntry.responseEnd,
				loaded:
					navigationEntry.loadEventEnd - navigationEntry.responseEnd,
				firstContentfulPaint: calculateTiming(
					'first-contentful-paint'
				),
				firstBlock: performance.now() - navigationEntry.responseEnd,
			};
		} );
	}

	logPerformanceResult( description: string, times: number[] ) {
		const roundedTimes = times.map(
			( time ) => Math.round( time * 100 ) / 100
		);
		const average =
			roundedTimes.reduce( ( a, b ) => a + b, 0 ) / roundedTimes.length;

		this.createReportDirectory();

		const performanceReportPath = path.join(
			PerformanceUtils.PERFORMANCE_REPORT_DIRECTORY,
			PerformanceUtils.PERFORMANCE_REPORT_FILENAME
		);
		const performanceData =
			JSON.stringify( {
				description,
				longest: Math.max( ...roundedTimes ),
				shortest: Math.min( ...roundedTimes ),
				average,
			} ) + '\n';

		this.appendToFile( performanceReportPath, performanceData );
	}

	private createReportDirectory() {
		if (
			! fs.existsSync( PerformanceUtils.PERFORMANCE_REPORT_DIRECTORY )
		) {
			fs.mkdirSync( PerformanceUtils.PERFORMANCE_REPORT_FILENAME );
		}
	}

	private appendToFile( filePath: string, data: string ) {
		try {
			fs.appendFileSync( filePath, data );
		} catch ( error ) {
			console.error( 'Error writing to file:', error );
		}
	}
}
