/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

export class PerformanceUtils {
	page: Page;

	constructor( page: Page ) {
		this.page = page;
	}

	reportDirectory = 'reports'; // Directory for the report
	reportFilename = 'e2e-performance.json'; // Filename for the report
	PERFORMANCE_REPORT_FILENAME = path.join(
		this.reportDirectory,
		this.reportFilename
	);

	async getLoadingDurations(): Promise< ReturnType< Page[ 'evaluate' ] > > {
		return await this.page.evaluate( () => {
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
				// Server side metric.
				serverResponse: responseStart - requestStart,
				// For client side metrics, consider the end of the response (the
				// browser receives the HTML) as the start time (0).
				firstPaint:
					paintTimings.find( ( { name } ) => name === 'first-paint' )
						.startTime - responseEnd,
				domContentLoaded: domContentLoadedEventEnd - responseEnd,
				loaded: loadEventEnd - responseEnd,
				firstContentfulPaint:
					paintTimings.find(
						( { name } ) => name === 'first-contentful-paint'
					).startTime - responseEnd,
				// This is evaluated right after Puppeteer found the block selector.
				firstBlock: performance.now() - responseEnd,
			};
		} );
	}
	/**
	 * Takes an average value of all items in an array.
	 *
	 * @param {Array} array An array of numbers to take an average from.
	 * @return {number} The average value of all members of the array.
	 */
	average = ( array ) => array.reduce( ( a, b ) => a + b ) / array.length;
	/**
	 * Writes a line to the e2e performance result for the current test containing longest, shortest, and average run times.
	 *
	 * @param {string} description Message to describe what you're logging the performance of.
	 * @param {Array}  times       array of times to record.
	 */
	logPerformanceResult = ( description, times ) => {
		const roundedTimes = times.map(
			( time ) => Math.round( time + Number.EPSILON * 100 ) / 100
		);
		if ( ! fs.existsSync( this.reportDirectory ) ) {
			fs.mkdirSync( this.reportDirectory, { recursive: true } );
		}

		fs.appendFileSync(
			this.PERFORMANCE_REPORT_FILENAME,
			JSON.stringify( {
				description,
				longest: Math.max( ...roundedTimes ),
				shortest: Math.min( ...roundedTimes ),
				average: this.average( roundedTimes ),
			} ) + '\n',
			{ flag: 'a' }
		);
	};
}
