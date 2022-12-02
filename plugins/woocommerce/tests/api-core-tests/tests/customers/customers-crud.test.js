const {
	test,
	expect
} = require('@playwright/test');
const {
	customer
} = require('../../data');
const {
	USER_KEY,
} = process.env;
const userKey = USER_KEY ?? 'admin';

/**
 * Tests for the WooCommerce Customers API.
 *
 * @group api
 * @group customers
 *
 */
test.describe('Customers API tests: CRUD', () => {
	let customerId;
	let subscriberUserId;
	let subscriberUserCreatedDuringTests = false;

	test.beforeAll(async ({
		request
	}) => {
		// Call the API to return all users and determine if a 
		// subscriber user has been created
		const customersResponse = await request.get('/wp-json/wc/v3/customers', {
			params: {
				role: 'all'
			}
		});
		const customersResponseJSON = await customersResponse.json();

		for (const element of customersResponseJSON) {
			if (element.role === 'subscriber') {
				subscriberUserId = element.id;
				break;
			}
		}

		// If a subscriber user has not been created then create one
		if (!subscriberUserId) {
			const userResponse = await request.post('/wp-json/wp/v2/users', {
				data: {
					username: "customer",
					email: "customer@woocommercecoretestsuite.com",
					first_name: "Jane",
					last_name: "Smith",
					roles: ["subscriber"],
					password: "password",
					name: "Jane",
				}
			});
			const userResponseJSON = await userResponse.json();
			// set subscriber user id to newly created user
			subscriberUserId = userResponseJSON.id;
			subscriberUserCreatedDuringTests = true;
		}

		// Verify the subscriber user has been created
		const response = await request.get(`/wp-json/wc/v3/customers/${subscriberUserId}`);
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(responseJSON.role).toEqual('subscriber');
	});

	test.afterAll(async ({
		request
	}) => {
		// delete subscriber user if one was created during the execution of these tests
		if (subscriberUserCreatedDuringTests) {
			await request.delete(`/wp-json/wc/v3/customers/${subscriberUserId}`, {
				data: {
					force: true
				}
			});
		}
	});

	test.describe('Retrieve after env setup', () => {
		/** when the environment is created,
		 * (https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e-pw#woocommerce-playwright-end-to-end-tests),
		 * we have an admin user and a subscriber user that can both be 
		 * accessed through their ids
		 * admin user will have id 1 and subscriber user will have id 2
		 * neither of these are returned as part of the get all customers call
		 * unless the role 'all' is passed as a search param 
		 * but they can be accessed by specific id reference
		 */
		test('can retrieve admin user', async ({
			request
		}) => {
			// call API to retrieve the previously saved customer
			const response = await request.get('/wp-json/wc/v3/customers/1');
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.is_paying_customer).toEqual(false);
			expect(responseJSON.role).toEqual('administrator');
			// this test was updated to allow for local test setup and other test sites.
			expect(responseJSON.username).toEqual(userKey);
		});

		test('can retrieve subscriber user', async ({
			request
		}) => {
			// if environment was created with subscriber user
			// call API to retrieve the customer with id 2
			const response = await request.get(`/wp-json/wc/v3/customers/${subscriberUserId}`);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.is_paying_customer).toEqual(false);
			expect(responseJSON.role).toEqual('subscriber');
		});

		test('retrieve user with id 0 is invalid', async ({
			request
		}) => {
			// call API to retrieve the previously saved customer
			const response = await request.get('/wp-json/wc/v3/customers/0');
			const responseJSON = await response.json();
			expect(response.status()).toEqual(404);
			expect(responseJSON.code).toEqual('woocommerce_rest_invalid_id');
			expect(responseJSON.message).toEqual('Invalid resource ID.');
		});

		test('can retrieve customers', async ({
			request
		}) => {
			// call API to retrieve all customers should initially return empty array
			const response = await request.get('/wp-json/wc/v3/customers');
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(Array.isArray(responseJSON)).toBe(true);
			expect(responseJSON.length).toEqual(0);
		});

		// however, if we pass in the search string for role 'all' then all users are returned
		test('can retrieve all customers', async ({
			request
		}) => {
			// call API to retrieve all customers should initially return empty array
			// unless the role 'all' is passed as a search string, in which case the admin
			// and subscriber users will be returned
			const response = await request.get('/wp-json/wc/v3/customers', {
				params: {
					role: 'all'
				}
			});
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(Array.isArray(responseJSON)).toBe(true);
			expect(responseJSON.length).toBeGreaterThanOrEqual(1);
		});
	});

	test.describe('Create a customer', () => {
		test('can create a customer', async ({
			request,
		}) => {
			// call API to create a customer
			const response = await request.post('/wp-json/wc/v3/customers', {
				data: customer,
			});
			const responseJSON = await response.json();

			// Save the customer ID. It will be used by the retrieve, update, and delete tests.
			customerId = responseJSON.id;

			expect(response.status()).toEqual(201);
			expect(typeof responseJSON.id).toEqual('number');
			// Verify that the customer role is 'customer'
			expect(responseJSON.role).toEqual('customer');
		});
	});

	test.describe('Retrieve after create', () => {
		test('can retrieve a customer', async ({
			request
		}) => {
			// call API to retrieve the previously saved customer
			const response = await request.get(
				`/wp-json/wc/v3/customers/${customerId}`
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.id).toEqual(customerId);
			expect(responseJSON.is_paying_customer).toEqual(false);
			expect(responseJSON.role).toEqual('customer');
		});

		test('can retrieve all customers', async ({
			request
		}) => {
			// call API to retrieve all customers
			const response = await request.get('/wp-json/wc/v3/customers');
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(Array.isArray(responseJSON)).toBe(true);
			expect(responseJSON.length).toBeGreaterThan(0);
		});
	});

	test.describe('Update a customer', () => {

		test(`can update the admin user/customer`, async ({
			request,
		}) => {
			/**
			 * update customer names (regular, billing and shipping) to admin
			 * (these were initialised blank when the environment is created,
			 * (https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e-pw#woocommerce-playwright-end-to-end-tests
			 */
			const response = await request.put(
				`/wp-json/wc/v3/customers/1`, {
					data: {
						first_name: 'admin',
						billing: {
							first_name: 'admin'
						},
						shipping: {
							first_name: 'admin'
						}
					}
				}
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.first_name).toEqual('admin');
			expect(responseJSON.billing.first_name).toEqual('admin');
			expect(responseJSON.shipping.first_name).toEqual('admin');
		});

		test('retrieve after update admin', async ({
			request
		}) => {
			// call API to retrieve the admin customer we updated above
			const response = await request.get('/wp-json/wc/v3/customers/1');
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.first_name).toEqual('admin');
			expect(responseJSON.billing.first_name).toEqual('admin');
			expect(responseJSON.shipping.first_name).toEqual('admin');
		});

		test(`can update the subscriber user/customer`, async ({
			request,
		}) => {
			// update customer names (billing and shipping) to Jane
			// (these were initialised blank, only regular first_name was populated)
			const response = await request.put(
				`/wp-json/wc/v3/customers/${subscriberUserId}`, {
					data: {
						billing: {
							first_name: 'Jane'
						},
						shipping: {
							first_name: 'Jane'
						}
					}
				}
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.first_name).toEqual('Jane');
			expect(responseJSON.billing.first_name).toEqual('Jane');
			expect(responseJSON.shipping.first_name).toEqual('Jane');
		});

		test('retrieve after update subscriber', async ({
			request
		}) => {
			// call API to retrieve the subscriber customer we updated above
			const response = await request.get(`/wp-json/wc/v3/customers/${subscriberUserId}`);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.first_name).toEqual('Jane');
			expect(responseJSON.billing.first_name).toEqual('Jane');
			expect(responseJSON.shipping.first_name).toEqual('Jane');
		});

		test(`can update a customer`, async ({
			request,
		}) => {
			// update customer names (regular, billing and shipping) from John to Jack
			const response = await request.put(
				`/wp-json/wc/v3/customers/${ customerId }`, {
					data: {
						first_name: 'Jack',
						billing: {
							first_name: 'Jack'
						},
						shipping: {
							first_name: 'Jack'
						}
					}
				}
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.first_name).toEqual('Jack');
			expect(responseJSON.billing.first_name).toEqual('Jack');
			expect(responseJSON.shipping.first_name).toEqual('Jack');
		});

		test('retrieve after update customer', async ({
			request
		}) => {
			// call API to retrieve the updated customer we created above
			const response = await request.get(`/wp-json/wc/v3/customers/${ customerId }`);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.first_name).toEqual('Jack');
			expect(responseJSON.billing.first_name).toEqual('Jack');
			expect(responseJSON.shipping.first_name).toEqual('Jack');
		});
	});

	test.describe('Delete a customer', () => {
		test('can permanently delete an customer', async ({
			request
		}) => {
			// Delete the customer.
			const response = await request.delete(
				`/wp-json/wc/v3/customers/${ customerId }`, {
					data: {
						force: true,
					},
				}
			);
			expect(response.status()).toEqual(200);

			// Verify that the customer can no longer be retrieved.
			const getDeletedCustomerResponse = await request.get(
				`/wp-json/wc/v3/customers/${ customer }`
			);
			expect(getDeletedCustomerResponse.status()).toEqual(404);
		});
	});


	test.describe('Batch update customers', () => {
		/**
		 * 2 Customers to be created in one batch.
		 */
		const expectedCustomers = [{
				email: "john.doe2@example.com",
				first_name: "John",
				last_name: "Doe",
				username: "john.doe2",
				billing: {
					first_name: "John",
					last_name: "Doe",
					company: "",
					address_1: "969 Market",
					address_2: "",
					city: "San Francisco",
					state: "CA",
					postcode: "94103",
					country: "US",
					email: "john.doe2@example.com",
					phone: "(555) 555-5555"
				},
				shipping: {
					first_name: "John",
					last_name: "Doe",
					company: "",
					address_1: "969 Market",
					address_2: "",
					city: "San Francisco",
					state: "CA",
					postcode: "94103",
					country: "US"
				}
			},
			{
				email: "joao.silva2@example.com",
				first_name: "João",
				last_name: "Silva",
				username: "joao.silva2",
				billing: {
					first_name: "João",
					last_name: "Silva",
					company: "",
					address_1: "Av. Brasil, 432",
					address_2: "",
					city: "Rio de Janeiro",
					state: "RJ",
					postcode: "12345-000",
					country: "BR",
					email: "joao.silva2@example.com",
					phone: "(55) 5555-5555"
				},
				shipping: {
					first_name: "João",
					last_name: "Silva",
					company: "",
					address_1: "Av. Brasil, 432",
					address_2: "",
					city: "Rio de Janeiro",
					state: "RJ",
					postcode: "12345-000",
					country: "BR"
				}
			}
		];

		// set payload to use batch create: action
		const batchCreate2CustomersPayload = {
			create: expectedCustomers
		};

		test('can batch create customers', async ({
			request
		}) => {
			// Batch create 2 new customers.
			// call API to batch create customers
			const response = await request.post('wp-json/wc/v3/customers/batch', {
				data: batchCreate2CustomersPayload,
			});
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);

			// Verify that the 2 new customers were created
			const actualCustomers = responseJSON.create;
			expect(actualCustomers).toHaveLength(expectedCustomers.length);

			for (let i = 0; i < actualCustomers.length; i++) {
				const {
					id,
					first_name
				} = actualCustomers[i];
				const expectedCustomerName = expectedCustomers[i].first_name;

				expect(id).toBeDefined();
				expect(first_name).toEqual(expectedCustomerName);

				// Save the customer id
				expectedCustomers[i].id = id;
			}
		});

		test('can batch update customers', async ({
			request
		}) => {
			// set payload to use batch update: action
			const batchUpdatePayload = {
				update: [{
						id: expectedCustomers[0].id,
						email: 'emailupdated@example.com',
					},
					{
						id: expectedCustomers[1].id,
						billing: {
							address_1: "123 Addressupdate Street"
						}
					},
				],
			};

			// Call API to update the customers
			const response = await request.post(
				'wp-json/wc/v3/customers/batch', {
					data: batchUpdatePayload,
				}
			);
			const responseJSON = await response.json();

			// Verify the response code and the number of customers that were updated.
			const updatedCustomers = responseJSON.update;
			expect(response.status()).toEqual(200);
			expect(updatedCustomers).toHaveLength(2);

			// Verify that the 1st customer was updated to have a new email address.
			expect(updatedCustomers[0].id).toEqual(expectedCustomers[0].id);
			expect(updatedCustomers[0].email).toEqual('emailupdated@example.com');

			// Verify that the amount of the 2nd customer was updated to have a new billing address.
			expect(updatedCustomers[1].id).toEqual(expectedCustomers[1].id);
			expect(updatedCustomers[1].billing.address_1).toEqual('123 Addressupdate Street');
		});

		test('can batch delete customers', async ({
			request
		}) => {
			// Batch delete the 2 customers.
			const customerIdsToDelete = expectedCustomers.map(({
				id
			}) => id);
			const batchDeletePayload = {
				delete: customerIdsToDelete,
			};

			//Call API to batch delete the customers
			const response = await request.post(
				'wp-json/wc/v3/customers/batch', {
					data: batchDeletePayload,
				}
			);
			const responseJSON = await response.json();

			// Verify that the response shows the 2 customers.
			const deletedCustomerIds = responseJSON.delete.map(
				({
					id
				}) => id);
			expect(response.status()).toEqual(200);
			expect(deletedCustomerIds).toEqual(customerIdsToDelete);

			// Verify that the 2 deleted customers cannot be retrieved.
			for (const customerId of customerIdsToDelete) {
				//Call the API to attempte to retrieve the customers
				const response = await request.get(
					`wp-json/wc/v3/customers/${ customerId }`
				);
				expect(response.status()).toEqual(404);
			}
		});
	});

});
