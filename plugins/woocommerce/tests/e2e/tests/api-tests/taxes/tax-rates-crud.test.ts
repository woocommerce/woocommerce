import { expect, test } from '../../../fixtures/api-tests-fixtures';

/* eslint-disable playwright/no-conditional-in-test, playwright/no-conditional-expect -- Failure-safe cleanup must preserve primary and cleanup errors independently. */

const { BASE_URL } = process.env;
const shouldSkipDeletedRateRead = ! /^https?:\/\/localhost/.test(
	BASE_URL ?? ''
);

test.describe( 'Tax Classes and Rates API tests', () => {
	test( 'can round-trip a tax class and rate through authenticated installed V3 HTTP', async ( {
		request,
	} ) => {
		const className = `Slice 42 HTTP Tax Class ${ Date.now() }`;
		let classSlug: string | undefined;
		let rateId: number | undefined;
		let classDeleted = false;
		let rateDeleted = false;
		let primaryError: unknown;
		const cleanupErrors: unknown[] = [];

		try {
			await test.step( 'Create and retrieve a unique tax class', async () => {
				const createClassResponse = await request.post(
					'./wp-json/wc/v3/taxes/classes',
					{
						data: { name: className },
					}
				);
				const createdClass = await createClassResponse.json();
				classSlug = createdClass.slug;

				expect( createClassResponse.status() ).toEqual( 201 );
				expect( createdClass.name ).toEqual( className );
				expect( classSlug ).toMatch( /^slice-42-http-tax-class-\d+$/ );

				const getClassResponse = await request.get(
					`./wp-json/wc/v3/taxes/classes/${ classSlug }`
				);
				expect( getClassResponse.status() ).toEqual( 200 );
				expect( await getClassResponse.json() ).toEqual( [
					expect.objectContaining( {
						name: className,
						slug: classSlug,
					} ),
				] );
			} );

			await test.step( 'Create and retrieve a rate assigned to that class', async () => {
				const createRateResponse = await request.post(
					'./wp-json/wc/v3/taxes',
					{
						data: {
							country: 'RO',
							state: 'B',
							rate: '19',
							name: 'Slice 42 HTTP Rate',
							class: classSlug,
						},
					}
				);
				const createdRate = await createRateResponse.json();
				rateId = createdRate.id;

				expect( createRateResponse.status() ).toEqual( 201 );
				expect( Number.isSafeInteger( rateId ) && rateId > 0 ).toBe(
					true
				);
				expect( createdRate ).toEqual(
					expect.objectContaining( {
						id: rateId,
						country: 'RO',
						state: 'B',
						rate: '19.0000',
						name: 'Slice 42 HTTP Rate',
						class: classSlug,
					} )
				);

				const getRateResponse = await request.get(
					`./wp-json/wc/v3/taxes/${ rateId }`
				);
				expect( getRateResponse.status() ).toEqual( 200 );
				expect( await getRateResponse.json() ).toEqual(
					expect.objectContaining( {
						id: rateId,
						rate: '19.0000',
						class: classSlug,
					} )
				);
			} );

			await test.step( 'Delete the rate and class', async () => {
				const deleteRateResponse = await request.delete(
					`./wp-json/wc/v3/taxes/${ rateId }`,
					{
						data: { force: true },
					}
				);
				expect( deleteRateResponse.status() ).toEqual( 200 );
				expect( ( await deleteRateResponse.json() ).id ).toEqual(
					rateId
				);
				rateDeleted = true;

				if ( ! shouldSkipDeletedRateRead ) {
					const deletedRateResponse = await request.get(
						`./wp-json/wc/v3/taxes/${ rateId }`
					);
					expect( deletedRateResponse.status() ).toEqual( 404 );
				}

				const deleteClassResponse = await request.delete(
					`./wp-json/wc/v3/taxes/classes/${ classSlug }`,
					{
						data: { force: true },
					}
				);
				expect( deleteClassResponse.status() ).toEqual( 200 );
				expect( ( await deleteClassResponse.json() ).slug ).toEqual(
					classSlug
				);
				classDeleted = true;

				const deletedClassResponse = await request.get(
					`./wp-json/wc/v3/taxes/classes/${ classSlug }`
				);
				expect( deletedClassResponse.status() ).toEqual( 404 );
			} );
		} catch ( error ) {
			primaryError = error;
		} finally {
			if ( rateId && ! rateDeleted ) {
				try {
					const cleanupRateResponse = await request.delete(
						`./wp-json/wc/v3/taxes/${ rateId }`,
						{
							data: { force: true },
						}
					);
					const cleanupRateStatus = cleanupRateResponse.status();
					expect( [ 200, 400 ] ).toContain( cleanupRateStatus );
					if ( cleanupRateStatus === 400 ) {
						expect(
							( await cleanupRateResponse.json() ).code
						).toEqual( 'woocommerce_rest_invalid_id' );
					}
				} catch ( error ) {
					cleanupErrors.push( error );
				}
			}

			if ( classSlug && ! classDeleted ) {
				try {
					const cleanupClassResponse = await request.delete(
						`./wp-json/wc/v3/taxes/classes/${ classSlug }`,
						{
							data: { force: true },
						}
					);
					const cleanupClassStatus = cleanupClassResponse.status();
					expect( [ 200, 404 ] ).toContain( cleanupClassStatus );
					if ( cleanupClassStatus === 404 ) {
						expect(
							( await cleanupClassResponse.json() ).code
						).toEqual( 'woocommerce_rest_tax_class_invalid_slug' );
					}
				} catch ( error ) {
					cleanupErrors.push( error );
				}
			}
		}

		if ( primaryError && cleanupErrors.length ) {
			throw new AggregateError(
				[ primaryError, ...cleanupErrors ],
				'Tax class/rate lifecycle and cleanup both failed.'
			);
		}
		if ( primaryError ) {
			throw primaryError;
		}
		if ( cleanupErrors.length ) {
			throw new AggregateError(
				cleanupErrors,
				'Tax class/rate cleanup failed.'
			);
		}
	} );
} );
