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
			const [
				{
					requestStart,
					responseStart,
					responseEnd,
					domContentLoadedEventEnd,
					loadEventEnd,
				},
			] = performance.getEntriesByType(
				'navigation'
			) as PerformanceNavigationTiming[];

			const paintTimings = performance.getEntriesByType( 'paint' );

			return {
				serverResponse: responseStart - requestStart,
				firstPaint:
					paintTimings.find( ( { name } ) => name === 'first-paint' )
						.startTime - responseEnd,
				domContentLoaded: domContentLoadedEventEnd - responseEnd,
				loaded: loadEventEnd - responseEnd,
				firstContentfulPaint:
					paintTimings.find(
						( { name } ) => name === 'first-contentful-paint'
					).startTime - responseEnd,
				firstBlock: performance.now() - responseEnd,
			};
		} );
	}

	logPerformanceResult( description: string, times: number[] ) {
		const roundedTimes = times.map(
			( time ) => Math.round( time + Number.EPSILON * 100 ) / 100
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
			fs.mkdirSync( PerformanceUtils.PERFORMANCE_REPORT_DIRECTORY );
		}
	}

	private appendToFile( filePath: string, data: string ) {
		try {
			fs.appendFileSync( filePath, data );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Error writing to file:', error );
		}
	}
}
