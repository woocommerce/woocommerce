/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

let processingOrderId: number | undefined;
let failedOrderId: number | undefined;

test.describe(
	'WooCommerce Orders > Filter Order by Status',
	{ tag: [ tags.HPOS ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test.beforeAll( async ( { restApi } ) => {
			const processingResponse = await restApi.post(
				`${ WC_API_PATH }/orders`,
				{ status: 'processing' }
			);
			expect( processingResponse.data.id ).toBeGreaterThan( 0 );
			processingOrderId = processingResponse.data.id;

			const failedResponse = await restApi.post(
				`${ WC_API_PATH }/orders`,
				{ status: 'failed' }
			);
			expect( failedResponse.data.id ).toBeGreaterThan( 0 );
			failedOrderId = failedResponse.data.id;
		} );

		test.afterAll( async ( { restApi } ) => {
			const orderIds = [ processingOrderId, failedOrderId ].filter(
				( orderId ): orderId is number => orderId !== undefined
			);

			if ( orderIds.length > 0 ) {
				await restApi.post( `${ WC_API_PATH }/orders/batch`, {
					delete: orderIds,
				} );
			}
		} );

		test( 'should filter by Processing', async ( { page } ) => {
			expect( processingOrderId ).toBeDefined();
			expect( failedOrderId ).toBeDefined();

			await page.goto( 'wp-admin/admin.php?page=wc-orders' );
			await page.getByRole( 'link', { name: /^Processing \(/ } ).click();

			await expect( page ).toHaveURL(
				/[?&](?:status|post_status)=wc-processing(?:&|$)/
			);
			const currentProcessingLink = page.locator(
				'li.wc-processing > a.current'
			);
			await expect( currentProcessingLink ).toBeVisible();
			await expect(
				page.locator(
					`#order-${ processingOrderId }, #post-${ processingOrderId }`
				)
			).toBeVisible();
			await expect(
				page.locator(
					`#order-${ failedOrderId }, #post-${ failedOrderId }`
				)
			).toHaveCount( 0 );

			const statusMarks = page.locator( 'mark.order-status:visible' );
			const visibleRowCount = await statusMarks.count();
			expect( visibleRowCount ).toBeGreaterThan( 0 );

			for ( let index = 0; index < visibleRowCount; index++ ) {
				await expect( statusMarks.nth( index ) ).toHaveClass(
					/status-processing/
				);
				await expect(
					statusMarks.nth( index ).locator( 'span' )
				).toHaveText( 'Processing' );
			}

			const processingCountText = await currentProcessingLink
				.locator( 'span.count' )
				.innerText();
			const processingCount = Number.parseInt(
				processingCountText.replace( /\D/g, '' ),
				10
			);
			expect( Number.isInteger( processingCount ) ).toBe( true );
			expect( processingCount ).toBeGreaterThan( 0 );
			expect( processingCount ).toBeGreaterThanOrEqual( visibleRowCount );
		} );
	}
);
