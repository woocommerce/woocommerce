/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../../fixtures/api-tests-fixtures';
import { admin } from '../../../test-data/data';
import { wpCLI } from '../../../utils/cli';

const requirePositiveCustomerId = (
	value: unknown,
	operation: string
): number => {
	if (
		typeof value !== 'number' ||
		! Number.isSafeInteger( value ) ||
		value <= 0
	) {
		throw new Error(
			`${ operation } returned an invalid customer ID: ${ String(
				value
			) }`
		);
	}

	return value;
};

const listUserIdsThroughWpCLI = async ( customerId: number ) => {
	const { stdout } = await wpCLI(
		`wp user list --include=${ customerId } --field=ID`
	);

	return ( stdout.match( /^\d+\r?$/gm ) ?? [] ).map( Number );
};

const deleteNetworkUser = async ( customerId: number ) => {
	if (
		! ( await listUserIdsThroughWpCLI( customerId ) ).includes( customerId )
	) {
		return;
	}

	await wpCLI( `wp user delete ${ customerId } --yes --network` );

	if (
		( await listUserIdsThroughWpCLI( customerId ) ).includes( customerId )
	) {
		throw new Error(
			`Network customer ${ customerId } still exists after WP-CLI cleanup.`
		);
	}
};

const deleteSingleSiteUser = async (
	request: APIRequestContext,
	customerId: number
) => {
	const deleteResponse = await request.delete(
		`./wp-json/wp/v2/users/${ customerId }`,
		{ data: { force: true, reassign: 1 } }
	);

	if ( deleteResponse.status() === 404 ) {
		return;
	}

	if ( deleteResponse.status() !== 200 ) {
		throw new Error(
			`Customer ${ customerId } cleanup returned ${ deleteResponse.status() }.`
		);
	}

	const readResponse = await request.get(
		`./wp-json/wp/v2/users/${ customerId }`
	);
	if ( readResponse.status() !== 404 ) {
		throw new Error(
			`Customer ${ customerId } remained readable after cleanup (status ${ readResponse.status() }).`
		);
	}
};

const cleanupCustomers = async (
	request: APIRequestContext,
	customerIds: number[]
) => {
	const cleanupErrors: unknown[] = [];

	for ( const customerId of customerIds ) {
		try {
			requirePositiveCustomerId( customerId, 'Cleanup' );

			if ( process.env.IS_MULTISITE ) {
				await deleteNetworkUser( customerId );
			} else {
				await deleteSingleSiteUser( request, customerId );
			}
		} catch ( error ) {
			cleanupErrors.push( error );
		}
	}

	return cleanupErrors;
};

const runWithCustomerCleanup = async (
	request: APIRequestContext,
	getCustomerIds: () => number[],
	runLifecycle: () => Promise< void >
) => {
	let primaryFailure: { error: unknown } | undefined;

	try {
		await runLifecycle();
	} catch ( error ) {
		primaryFailure = { error };
	}

	const cleanupErrors = await cleanupCustomers( request, getCustomerIds() );

	if ( primaryFailure && cleanupErrors.length > 0 ) {
		throw new AggregateError(
			[ primaryFailure.error, ...cleanupErrors ],
			'Customer lifecycle and cleanup both failed.'
		);
	}

	if ( primaryFailure ) {
		throw primaryFailure.error;
	}

	if ( cleanupErrors.length > 0 ) {
		throw new AggregateError(
			cleanupErrors,
			'Customer lifecycle cleanup failed.'
		);
	}
};

const deleteCustomerThroughWoo = async (
	request: APIRequestContext,
	customerId: number
) => {
	// WooCommerce customer deletion is unsupported on multisite. The final
	// network-aware WP-CLI cleanup owns deletion there.
	if ( process.env.IS_MULTISITE ) {
		return;
	}

	const deleteResponse = await request.delete(
		`./wp-json/wc/v3/customers/${ customerId }`,
		{ data: { force: true } }
	);
	expect( deleteResponse.status() ).toEqual( 200 );

	const deletedReadResponse = await request.get(
		`./wp-json/wc/v3/customers/${ customerId }`
	);
	expect( deletedReadResponse.status() ).toEqual( 404 );
};

const deleteCustomersThroughWooBatch = async (
	request: APIRequestContext,
	customerIds: number[]
) => {
	// Preserve create/read/update coverage on multisite without claiming the
	// unsupported WooCommerce customer-deletion behavior.
	if ( process.env.IS_MULTISITE ) {
		return;
	}

	const deleteResponse = await request.post(
		'./wp-json/wc/v3/customers/batch',
		{ data: { delete: customerIds } }
	);
	const deleteResult = await deleteResponse.json();
	expect( deleteResponse.status() ).toEqual( 200 );
	expect( deleteResult.delete.map( ( { id } ) => id ) ).toEqual(
		customerIds
	);

	for ( const customerId of customerIds ) {
		const deletedReadResponse = await request.get(
			`./wp-json/wc/v3/customers/${ customerId }`
		);
		expect( deletedReadResponse.status() ).toEqual( 404 );
	}
};

test.describe( 'Customers API tests: CRUD', () => {
	test( 'can retrieve admin user', async ( { request } ) => {
		const customersResponse = await request.get(
			'./wp-json/wc/v3/customers',
			{ params: { role: 'all', per_page: 100 } }
		);
		const customers = await customersResponse.json();

		expect( customersResponse.status() ).toEqual( 200 );
		const adminCustomer = customers.find(
			( { username } ) => username === admin.username
		);
		const adminId = requirePositiveCustomerId(
			adminCustomer?.id,
			'Admin customer lookup'
		);

		const response = await request.get(
			`./wp-json/wc/v3/customers/${ adminId }`
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.username ).toEqual( admin.username );
		expect( responseJSON.role ).toEqual( 'administrator' );
		expect( responseJSON.is_paying_customer ).toEqual( false );
	} );

	test(
		'can round-trip a customer through authenticated installed V3 HTTP',
		{
			tag: [ tags.SKIP_ON_EXTERNAL_ENV ],
		},
		async ( { request } ) => {
			const unique = Date.now();
			const email = `customer.roundtrip.${ unique }@example.com`;
			const billingCity = 'San Francisco';
			const customerIds: number[] = [];

			await runWithCustomerCleanup(
				request,
				() => customerIds,
				async () => {
					const createResponse = await request.post(
						'./wp-json/wc/v3/customers',
						{
							data: {
								email,
								username: `customer.roundtrip.${ unique }`,
								billing: { city: billingCity },
							},
						}
					);
					const createdCustomer = await createResponse.json();
					const customerId = requirePositiveCustomerId(
						createdCustomer.id,
						'Customer create'
					);
					customerIds.push( customerId );

					expect( createResponse.status() ).toEqual( 201 );
					expect( createdCustomer.email ).toEqual( email );
					expect( createdCustomer.billing.city ).toEqual(
						billingCity
					);

					const createdReadResponse = await request.get(
						`./wp-json/wc/v3/customers/${ customerId }`
					);
					const createdReadCustomer =
						await createdReadResponse.json();
					expect( createdReadResponse.status() ).toEqual( 200 );
					expect( createdReadCustomer.id ).toEqual( customerId );
					expect( createdReadCustomer.email ).toEqual( email );
					expect( createdReadCustomer.billing.city ).toEqual(
						billingCity
					);

					const updatedFirstName = 'Jack';
					const updateResponse = await request.put(
						`./wp-json/wc/v3/customers/${ customerId }`,
						{ data: { first_name: updatedFirstName } }
					);
					expect( updateResponse.status() ).toEqual( 200 );

					const updatedReadResponse = await request.get(
						`./wp-json/wc/v3/customers/${ customerId }`
					);
					const updatedReadCustomer =
						await updatedReadResponse.json();
					expect( updatedReadResponse.status() ).toEqual( 200 );
					expect( updatedReadCustomer.first_name ).toEqual(
						updatedFirstName
					);

					await deleteCustomerThroughWoo( request, customerId );
				}
			);
		}
	);

	test(
		'can batch round-trip customers through authenticated installed V3 HTTP',
		{
			tag: [ tags.SKIP_ON_EXTERNAL_ENV ],
		},
		async ( { request } ) => {
			const unique = Date.now();
			const customers = [
				{
					email: `customer.batch.one.${ unique }@example.com`,
					username: `customer.batch.one.${ unique }`,
				},
				{
					email: `customer.batch.two.${ unique }@example.com`,
					username: `customer.batch.two.${ unique }`,
				},
			];
			const customerIds: number[] = [];

			await runWithCustomerCleanup(
				request,
				() => customerIds,
				async () => {
					const createResponse = await request.post(
						'./wp-json/wc/v3/customers/batch',
						{ data: { create: customers } }
					);
					const createResult = await createResponse.json();
					const createdCustomers = createResult.create ?? [];

					for ( const [
						index,
						createdCustomer,
					] of createdCustomers.entries() ) {
						customerIds.push(
							requirePositiveCustomerId(
								createdCustomer.id,
								`Batch create item ${ index + 1 }`
							)
						);
					}

					expect( createResponse.status() ).toEqual( 200 );
					expect( createdCustomers ).toHaveLength( customers.length );
					expect( new Set( customerIds ).size ).toEqual(
						customers.length
					);
					expect(
						createdCustomers.map( ( { email } ) => email )
					).toEqual( customers.map( ( { email } ) => email ) );

					const updatedFirstNames = [ 'Jack', 'José' ];
					const updateResponse = await request.post(
						'./wp-json/wc/v3/customers/batch',
						{
							data: {
								update: customerIds.map( ( id, index ) => ( {
									id,
									first_name: updatedFirstNames[ index ],
								} ) ),
							},
						}
					);
					const updateResult = await updateResponse.json();
					expect( updateResponse.status() ).toEqual( 200 );
					expect(
						updateResult.update.map( ( { id } ) => id )
					).toEqual( customerIds );

					for ( const [
						index,
						customerId,
					] of customerIds.entries() ) {
						const readResponse = await request.get(
							`./wp-json/wc/v3/customers/${ customerId }`
						);
						const readCustomer = await readResponse.json();
						expect( readResponse.status() ).toEqual( 200 );
						expect( readCustomer.id ).toEqual( customerId );
						expect( readCustomer.email ).toEqual(
							customers[ index ].email
						);
						expect( readCustomer.first_name ).toEqual(
							updatedFirstNames[ index ]
						);
					}

					await deleteCustomersThroughWooBatch(
						request,
						customerIds
					);
				}
			);
		}
	);
} );
