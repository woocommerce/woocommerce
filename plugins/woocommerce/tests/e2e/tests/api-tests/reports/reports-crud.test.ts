/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';

test.describe( 'Reports API tests', () => {
	test( 'can list reports through authenticated installed V3 HTTP', async ( {
		request,
	} ) => {
		const response = await request.get( './wp-json/wc/v3/reports' );
		const reports = await response.json();
		const expectedSlugs = [
			'sales',
			'top_sellers',
			'orders/totals',
			'products/totals',
			'customers/totals',
			'coupons/totals',
			'reviews/totals',
			'categories/totals',
			'tags/totals',
			'attributes/totals',
		];

		expect( response.status() ).toBe( 200 );
		expect( reports.map( ( report ) => report.slug ) ).toEqual(
			expectedSlugs
		);
		expect( reports ).toHaveLength( expectedSlugs.length );

		for ( const report of reports ) {
			const { _links: reportLinks } = report;

			expect( report.description ).toEqual( expect.any( String ) );
			expect( report.description ).not.toBe( '' );
			expect( reportLinks.self[ 0 ].href ).toContain(
				`/wp-json/wc/v3/reports/${ report.slug }`
			);
			expect( reportLinks.collection[ 0 ].href ).toContain(
				'/wp-json/wc/v3/reports'
			);
		}
	} );
} );
