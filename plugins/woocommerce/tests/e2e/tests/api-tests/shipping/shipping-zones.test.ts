import { expect, test } from '../../../fixtures/api-tests-fixtures';

/* eslint-disable playwright/no-conditional-in-test, playwright/no-conditional-expect -- Failure-safe cleanup must preserve primary and cleanup errors independently. */

const { BASE_URL } = process.env;
const shouldSkipDeletedRead = ! /^https?:\/\/localhost/.test( BASE_URL ?? '' );

test.describe( 'Shipping zones API tests', () => {
	test( 'can round-trip a shipping zone and its locations through authenticated installed V3 HTTP', async ( {
		request,
	} ) => {
		const zoneName = `Shipping zone round trip ${ Date.now() }`;
		const updatedZoneName = `${ zoneName } updated`;
		let zoneId: number | undefined;
		let deleted = false;
		let primaryError: unknown;
		let cleanupError: unknown;

		try {
			const createResponse = await request.post(
				'./wp-json/wc/v3/shipping/zones',
				{
					data: {
						name: zoneName,
						order: 4,
					},
				}
			);
			const createdZone = await createResponse.json();
			zoneId = createdZone.id;

			expect( createResponse.status() ).toEqual( 201 );
			expect( Number.isSafeInteger( zoneId ) && zoneId > 0 ).toBe( true );
			expect( createdZone ).toEqual(
				expect.objectContaining( {
					id: zoneId,
					name: zoneName,
					order: 4,
				} )
			);

			const retrieveResponse = await request.get(
				`./wp-json/wc/v3/shipping/zones/${ zoneId }`
			);
			expect( retrieveResponse.status() ).toEqual( 200 );
			expect( await retrieveResponse.json() ).toEqual(
				expect.objectContaining( {
					id: zoneId,
					name: zoneName,
					order: 4,
				} )
			);

			const updateResponse = await request.put(
				`./wp-json/wc/v3/shipping/zones/${ zoneId }`,
				{
					data: {
						name: updatedZoneName,
						order: 7,
					},
				}
			);
			expect( updateResponse.status() ).toEqual( 200 );
			expect( await updateResponse.json() ).toEqual(
				expect.objectContaining( {
					id: zoneId,
					name: updatedZoneName,
					order: 7,
				} )
			);

			const locationsResponse = await request.put(
				`./wp-json/wc/v3/shipping/zones/${ zoneId }/locations`,
				{
					data: [
						{
							code: 'BR:SP',
							type: 'state',
						},
					],
				}
			);
			const locations = await locationsResponse.json();
			expect( locationsResponse.status() ).toEqual( 200 );
			expect(
				locations.map( ( { code, type } ) => ( { code, type } ) )
			).toEqual( [ { code: 'BR:SP', type: 'state' } ] );

			const freshLocationsResponse = await request.get(
				`./wp-json/wc/v3/shipping/zones/${ zoneId }/locations`
			);
			const freshLocations = await freshLocationsResponse.json();
			expect( freshLocationsResponse.status() ).toEqual( 200 );
			expect(
				freshLocations.map( ( { code, type } ) => ( { code, type } ) )
			).toEqual( [ { code: 'BR:SP', type: 'state' } ] );

			const deleteResponse = await request.delete(
				`./wp-json/wc/v3/shipping/zones/${ zoneId }`,
				{
					data: { force: true },
				}
			);
			const deletedZone = await deleteResponse.json();
			expect( deleteResponse.status() ).toEqual( 200 );
			expect( deletedZone.id ).toEqual( zoneId );
			deleted = true;

			if ( ! shouldSkipDeletedRead ) {
				const deletedResponse = await request.get(
					`./wp-json/wc/v3/shipping/zones/${ zoneId }`
				);
				expect( deletedResponse.status() ).toEqual( 404 );
			}
		} catch ( error ) {
			primaryError = error;
		} finally {
			if ( zoneId && ! deleted ) {
				try {
					const cleanupResponse = await request.delete(
						`./wp-json/wc/v3/shipping/zones/${ zoneId }`,
						{
							data: { force: true },
						}
					);
					expect( cleanupResponse.status() ).toEqual( 200 );
				} catch ( error ) {
					cleanupError = error;
				}
			}
		}

		if ( primaryError && cleanupError ) {
			throw new AggregateError(
				[ primaryError, cleanupError ],
				'Shipping zone lifecycle and cleanup both failed.'
			);
		}
		if ( primaryError ) {
			throw primaryError;
		}
		if ( cleanupError ) {
			throw cleanupError;
		}
	} );
} );
