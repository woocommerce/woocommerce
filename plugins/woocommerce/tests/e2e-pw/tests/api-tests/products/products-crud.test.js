const {
	test: baseTest,
	expect,
} = require( '../../../fixtures/api-tests-fixtures' );
const { BASE_URL } = process.env;
const { admin } = require( '../../../test-data/data' );
const shouldSkip = BASE_URL !== undefined;

/**
 * Internal dependencies
 */
const {
	virtualProduct,
	variableProduct,
} = require( '../../../data/products-crud' );
const { batch } = require( '../../../data/shared/batch-update' );

const test = baseTest.extend( {
	extraHTTPHeaders: {
		// Add authorization token to all requests.
		Authorization: `Basic ${ btoa(
			`${ admin.username }:${ admin.password }`
		) }`,
	},
	simpleTestProduct: async ( { request }, use ) => {
		let simpleTestProduct = {
			name: 'A Simple Product',
			regular_price: '25',
			description: 'Description for this simple product.',
			short_description: 'Shorter description.',
		};
		const response = await request.post( 'wp-json/wc/v3/products', {
			data: simpleTestProduct,
		} );
		simpleTestProduct = await response.json();

		await use( simpleTestProduct );

		// Cleanup
		await request.delete(
			`wp-json/wc/v3/products/${ simpleTestProduct.id }`,
			{
				force: true,
			}
		);
	},
} );
test.describe( 'Products API tests: CRUD', () => {
	test( 'can add a simple product', async ( { request } ) => {
		const productData = {
			name: 'A Simple Product',
			regular_price: '25',
			description: 'Description for this simple product.',
			short_description: 'Shorter description.',
		};
		const response = await request.post( 'wp-json/wc/v3/products', {
			data: productData,
		} );
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 201 );
		expect( typeof responseJSON.id ).toEqual( 'number' );
		expect( responseJSON ).toMatchObject( productData );
		expect( responseJSON.type ).toEqual( 'simple' );
		expect( responseJSON.status ).toEqual( 'publish' );
		expect( responseJSON.virtual ).toEqual( false );
		expect( responseJSON.downloadable ).toEqual( false );
		expect( responseJSON.shipping_required ).toEqual( true );
	} );

	test.describe( 'Product attributes tests: CRUD', () => {
		let productAttributeId;

		test( 'can add a product attribute', async ( { request } ) => {
			const response = await request.post(
				'wp-json/wc/v3/products/attributes',
				{
					data: {
						name: 'Shape',
						slug: 'pa_shape',
						type: 'select',
						order_by: 'menu_order',
						has_archives: true,
					},
				}
			);
			const responseJSON = await response.json();
			productAttributeId = responseJSON.id;

			expect( response.status() ).toEqual( 201 );
			expect( typeof productAttributeId ).toEqual( 'number' );
			expect( responseJSON.name ).toEqual( 'Shape' );
			expect( responseJSON.slug ).toEqual( 'pa_shape' );
			expect( responseJSON.type ).toEqual( 'select' );
			expect( responseJSON.order_by ).toEqual( 'menu_order' );
			expect( responseJSON.has_archives ).toEqual( true );
		} );

		test.describe( 'Product attribute terms tests: CRUD', () => {
			let productAttributeTermId;

			test( 'can add a product attribute term', async ( { request } ) => {
				const response = await request.post(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms`,
					{
						data: {
							name: 'Circle',
						},
					}
				);
				const responseJSON = await response.json();
				productAttributeTermId = responseJSON.id;

				expect( response.status() ).toEqual( 201 );
				expect( typeof productAttributeTermId ).toEqual( 'number' );
				expect( responseJSON.name ).toEqual( 'Circle' );
				expect( responseJSON.slug ).toEqual( 'circle' );
				expect( responseJSON.menu_order ).toEqual( 0 );
				expect( responseJSON.count ).toEqual( 0 );
			} );

			test( 'can retrieve a product attribute term', async ( {
				request,
			} ) => {
				const response = await request.get(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/${ productAttributeTermId }`
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.id ).toEqual( productAttributeTermId );
				expect( responseJSON.name ).toEqual( 'Circle' );
				expect( responseJSON.slug ).toEqual( 'circle' );
				expect( responseJSON.menu_order ).toEqual( 0 );
				expect( responseJSON.count ).toEqual( 0 );
			} );

			test( 'can retrieve all product attribute terms', async ( {
				request,
			} ) => {
				// call API to retrieve all product attributes
				const response = await request.get(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms`
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( Array.isArray( responseJSON ) ).toBe( true );
				expect( responseJSON.length ).toBeGreaterThan( 0 );
			} );

			test( 'can update a product attribute term', async ( {
				request,
			} ) => {
				// call API to update a product attribute term
				const response = await request.put(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/${ productAttributeTermId }`,
					{
						data: {
							name: 'Square',
						},
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.id ).toEqual( productAttributeTermId );
				expect( responseJSON.name ).toEqual( 'Square' );
				expect( responseJSON.slug ).toEqual( 'circle' );
				expect( responseJSON.menu_order ).toEqual( 0 );
				expect( responseJSON.count ).toEqual( 0 );
			} );

			test( 'can permanently delete a product attribute term', async ( {
				request,
			} ) => {
				// Delete the product attribute term.
				const response = await request.delete(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/${ productAttributeTermId }`,
					{
						data: {
							force: true,
						},
					}
				);
				expect( response.status() ).toEqual( 200 );

				// Verify that the product attribute term can no longer be retrieved.
				const getDeletedProductAttributeTermResponse =
					await request.get(
						`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/${ productAttributeTermId }`
					);
				expect(
					getDeletedProductAttributeTermResponse.status()
				).toEqual( 404 );
			} );

			test( 'can batch update product attribute terms', async ( {
				request,
			} ) => {
				// Batch create 2 product attribute terms
				const response = await request.post(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/batch`,
					{
						data: {
							create: [
								{
									name: 'Rectangle',
								},
								{
									name: 'Oblong',
								},
							],
						},
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.create[ 0 ].name ).toEqual( 'Rectangle' );
				expect( responseJSON.create[ 1 ].name ).toEqual( 'Oblong' );
				const termId1 = responseJSON.create[ 0 ].id;
				const termId2 = responseJSON.create[ 1 ].id;

				// Batch create a new attribute term, update a term and delete another.
				const responseBatchUpdate = await request.post(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/batch`,
					{
						data: {
							create: [
								{
									name: 'Triangle',
								},
							],
							update: [
								{
									id: termId1,
									description: 'description for a Rectangle',
								},
							],
							delete: [ termId2 ],
						},
					}
				);
				const responseBatchUpdateJSON =
					await responseBatchUpdate.json();
				const termId3 = responseBatchUpdateJSON.create[ 0 ].id;
				expect( responseBatchUpdate.status() ).toEqual( 200 );

				const responseUpdatedTerm1 = await request.get(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/${ termId1 }`
				);
				const responseUpdatedTerm1JSON =
					await responseUpdatedTerm1.json();
				expect( responseUpdatedTerm1JSON.description ).toEqual(
					'description for a Rectangle'
				);

				// Verify that the deleted product attribute can no longer be retrieved.
				const getDeletedTermResponse = await request.get(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/${ termId2 }`
				);
				expect( getDeletedTermResponse.status() ).toEqual( 404 );

				// Batch delete the created terms
				await request.post(
					`wp-json/wc/v3/products/attributes/${ productAttributeId }/terms/batch`,
					{
						data: {
							delete: [ termId1, termId3 ],
						},
					}
				);
			} );
		} );

		test( 'can retrieve a product attribute', async ( { request } ) => {
			const response = await request.get(
				`wp-json/wc/v3/products/attributes/${ productAttributeId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productAttributeId );
			expect( responseJSON.name ).toEqual( 'Shape' );
			expect( responseJSON.slug ).toEqual( 'pa_shape' );
			expect( responseJSON.type ).toEqual( 'select' );
			expect( responseJSON.order_by ).toEqual( 'menu_order' );
			expect( responseJSON.has_archives ).toEqual( true );
		} );

		test( 'can retrieve all product attribute', async ( { request } ) => {
			// call API to retrieve all product attributes
			const response = await request.get(
				'/wp-json/wc/v3/products/attributes'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );

		test( 'can update a product attribute', async ( { request } ) => {
			// call API to update a product attribute
			const response = await request.put(
				`wp-json/wc/v3/products/attributes/${ productAttributeId }`,
				{
					data: {
						order_by: 'name',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productAttributeId );
			expect( responseJSON.name ).toEqual( 'Shape' );
			expect( responseJSON.slug ).toEqual( 'pa_shape' );
			expect( responseJSON.type ).toEqual( 'select' );
			expect( responseJSON.order_by ).toEqual( 'name' );
			// the below has_archives test is currently not working as expected
			// an issue (https://github.com/woocommerce/woocommerce/issues/34991)
			// has been raised and this test can be
			// updated as appropriate after triage
			// expect(responseJSON.has_archives).toEqual(true);
		} );

		test( 'can permanently delete a product attribute', async ( {
			request,
		} ) => {
			// Delete the product attribute.
			const response = await request.delete(
				`wp-json/wc/v3/products/attributes/${ productAttributeId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the product attribute can no longer be retrieved.
			const getDeletedProductAttributeResponse = await request.get(
				`wp-json/wc/v3/products/attributes/${ productAttributeId }`
			);
			expect( getDeletedProductAttributeResponse.status() ).toEqual(
				404
			);
		} );

		test( 'can batch update product attributes', async ( { request } ) => {
			// Batch create 2 product attributes
			const response = await request.post(
				`wp-json/wc/v3/products/attributes/batch`,
				{
					data: {
						create: [
							{
								name: 'Smell',
							},
							{
								name: 'Weight',
							},
						],
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.create[ 0 ].name ).toEqual( 'Smell' );
			expect( responseJSON.create[ 1 ].name ).toEqual( 'Weight' );
			const attributeId1 = responseJSON.create[ 0 ].id;
			const attributeId2 = responseJSON.create[ 1 ].id;

			// Batch create a new attribute, update an attribute and delete another.
			const responseBatchUpdate = await request.post(
				`wp-json/wc/v3/products/attributes/batch`,
				{
					data: {
						create: [
							{
								name: 'Height',
							},
						],
						update: [
							{
								id: attributeId1,
								order_by: 'name_num',
							},
						],
						delete: [ attributeId2 ],
					},
				}
			);
			const responseBatchUpdateJSON = await responseBatchUpdate.json();
			const attributeId3 = responseBatchUpdateJSON.create[ 0 ].id;
			expect( responseBatchUpdate.status() ).toEqual( 200 );

			const responseUpdatedAttribute1 = await request.get(
				`wp-json/wc/v3/products/attributes/${ attributeId1 }`
			);
			const responseUpdatedAttribute1JSON =
				await responseUpdatedAttribute1.json();
			expect( responseUpdatedAttribute1JSON.order_by ).toEqual(
				'name_num'
			);

			// Verify that the deleted product attribute can no longer be retrieved.
			const getDeletedAttributeResponse = await request.get(
				`wp-json/wc/v3/products/attributes/${ attributeId2 }`
			);
			expect( getDeletedAttributeResponse.status() ).toEqual( 404 );

			// Batch delete the created attribute
			await request.post( `wp-json/wc/v3/products/attributes/batch`, {
				data: {
					delete: [ attributeId1, attributeId3 ],
				},
			} );
		} );
	} );

	test.describe( 'Product categories tests: CRUD', () => {
		let productCategoryId;

		test( 'can add a product category', async ( { request } ) => {
			const response = await request.post(
				'wp-json/wc/v3/products/categories',
				{
					data: {
						name: 'Video Games',
					},
				}
			);
			const responseJSON = await response.json();
			productCategoryId = responseJSON.id;

			expect( response.status() ).toEqual( 201 );
			expect( typeof productCategoryId ).toEqual( 'number' );
			expect( responseJSON.name ).toEqual( 'Video Games' );
			expect( responseJSON.slug ).toEqual( 'video-games' );
			expect( responseJSON.parent ).toEqual( 0 );
			expect( responseJSON.description ).toEqual( '' );
			expect( responseJSON.display ).toEqual( 'default' );
			expect( responseJSON.image ).toEqual( null );
			expect( responseJSON.menu_order ).toEqual( 0 );
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can retrieve a product category', async ( { request } ) => {
			const response = await request.get(
				`wp-json/wc/v3/products/categories/${ productCategoryId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productCategoryId );
			expect( responseJSON.name ).toEqual( 'Video Games' );
			expect( responseJSON.slug ).toEqual( 'video-games' );
			expect( responseJSON.parent ).toEqual( 0 );
			expect( responseJSON.description ).toEqual( '' );
			expect( responseJSON.display ).toEqual( 'default' );
			expect( responseJSON.image ).toEqual( null );
			expect( responseJSON.menu_order ).toEqual( 0 );
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can retrieve all product categories', async ( { request } ) => {
			// call API to retrieve all product categories
			const response = await request.get(
				'/wp-json/wc/v3/products/categories'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			// There will initially be a product category of 'Uncategorized'
			expect( responseJSON.length ).toBeGreaterThan( 1 );
		} );

		test( 'can update a product category', async ( { request } ) => {
			// call API to retrieve all product categories
			const response = await request.put(
				`wp-json/wc/v3/products/categories/${ productCategoryId }`,
				{
					data: {
						description:
							'Games played on a video games console or computer.',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productCategoryId );
			expect( responseJSON.name ).toEqual( 'Video Games' );
			expect( responseJSON.slug ).toEqual( 'video-games' );
			expect( responseJSON.parent ).toEqual( 0 );
			expect( responseJSON.description ).toEqual(
				'Games played on a video games console or computer.'
			);
			expect( responseJSON.display ).toEqual( 'default' );
			expect( responseJSON.image ).toEqual( null );
			expect( responseJSON.menu_order ).toEqual( 0 );
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can permanently delete a product category', async ( {
			request,
		} ) => {
			// Delete the product category.
			const response = await request.delete(
				`wp-json/wc/v3/products/categories/${ productCategoryId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the product category can no longer be retrieved.
			const getDeletedProductCategoryResponse = await request.get(
				`wp-json/wc/v3/products/categories/${ productCategoryId }`
			);
			expect( getDeletedProductCategoryResponse.status() ).toEqual( 404 );
		} );

		test( 'can batch update product categories', async ( { request } ) => {
			// Batch create product categories.
			const response = await request.post(
				`wp-json/wc/v3/products/categories/batch`,
				{
					data: {
						create: [
							{
								name: 'Hats',
							},
							{
								name: 'Sweets',
							},
						],
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.create[ 0 ].name ).toEqual( 'Hats' );
			expect( responseJSON.create[ 1 ].name ).toEqual( 'Sweets' );
			const category1Id = responseJSON.create[ 0 ].id;
			const category2Id = responseJSON.create[ 1 ].id;

			// Batch create a new category, update a category and delete another.
			const responseBatchUpdate = await request.post(
				`wp-json/wc/v3/products/categories/batch`,
				{
					data: {
						create: [
							{
								name: 'Another Category Name',
							},
						],
						update: [
							{
								id: category1Id,
								description: 'Put them on your head.',
							},
						],
						delete: [ category2Id ],
					},
				}
			);
			const responseBatchUpdateJSON = await responseBatchUpdate.json();
			const category3Id = responseBatchUpdateJSON.create[ 0 ].id;
			expect( response.status() ).toEqual( 200 );

			const responseUpdatedCategory = await request.get(
				`wp-json/wc/v3/products/categories/${ category1Id }`
			);
			const responseUpdatedCategoryJSON =
				await responseUpdatedCategory.json();
			expect( responseUpdatedCategoryJSON.description ).toEqual(
				'Put them on your head.'
			);

			// Verify that the product tag can no longer be retrieved.
			const getDeletedProductCategoryResponse = await request.get(
				`wp-json/wc/v3/products/categories/${ category2Id }`
			);
			expect( getDeletedProductCategoryResponse.status() ).toEqual( 404 );

			// Batch delete the created tags
			await request.post( `wp-json/wc/v3/products/categories/batch`, {
				data: {
					delete: [ category1Id, category3Id ],
				},
			} );
		} );
	} );

	test.describe(
		'Product review tests: CRUD',
		{ tag: '@skip-on-default-wpcom' },
		() => {
			let productReviewId;
			let reviewsTestProduct;

			test.beforeAll( async ( { simpleTestProduct } ) => {
				reviewsTestProduct = simpleTestProduct;
			} );

			test( 'can add a product review', async ( { request } ) => {
				const response = await request.post(
					'wp-json/wc/v3/products/reviews',
					{
						data: {
							product_id: reviewsTestProduct.id,
							review: 'Nice simple product!',
							reviewer: 'John Doe',
							reviewer_email: 'john.doe@example.com',
							rating: 5,
						},
					}
				);
				const responseJSON = await response.json();
				productReviewId = responseJSON.id;

				expect( response.status() ).toEqual( 201 );
				expect( typeof productReviewId ).toEqual( 'number' );
				expect( responseJSON.id ).toEqual( productReviewId );
				expect( responseJSON.product_name ).toEqual(
					'A Simple Product'
				);
				expect( responseJSON.status ).toEqual( 'approved' );
				expect( responseJSON.reviewer ).toEqual( 'John Doe' );
				expect( responseJSON.reviewer_email ).toEqual(
					'john.doe@example.com'
				);
				expect( responseJSON.review ).toEqual( 'Nice simple product!' );
				expect( responseJSON.rating ).toEqual( 5 );
				expect( responseJSON.verified ).toEqual( false );
			} );

			test( 'cannot add a product review with invalid product_id', async ( {
				request,
			} ) => {
				const response = await request.post(
					'wp-json/wc/v3/products/reviews',
					{
						data: {
							product_id: 999,
							review: 'A non existent product!',
							reviewer: 'John Do Not',
							reviewer_email: 'john.do.not@example.com',
							rating: 5,
						},
					}
				);
				const responseJSON = await response.json();

				expect( response.status() ).toEqual( 404 );
				expect( responseJSON.code ).toEqual(
					'woocommerce_rest_product_invalid_id'
				);
				expect( responseJSON.message ).toEqual( 'Invalid product ID.' );
			} );

			test( 'cannot add a duplicate product review', async ( {
				request,
			} ) => {
				const response = await request.post(
					'wp-json/wc/v3/products/reviews',
					{
						data: {
							product_id: reviewsTestProduct.id,
							review: 'Nice simple product!',
							reviewer: 'John Doe',
							reviewer_email: 'john.doe@example.com',
							rating: 5,
						},
					}
				);
				const responseJSON = await response.json();

				expect( response.status() ).toEqual( 409 );
				expect( responseJSON.code ).toEqual(
					'woocommerce_rest_comment_duplicate'
				);
				expect( responseJSON.message ).toEqual(
					'Duplicate comment detected; it looks as though you&#8217;ve already said that!'
				);
			} );

			test( 'can retrieve a product review', async ( { request } ) => {
				const response = await request.get(
					`wp-json/wc/v3/products/reviews/${ productReviewId }`
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.id ).toEqual( productReviewId );
				expect( responseJSON.product_id ).toEqual(
					reviewsTestProduct.id
				);
				expect( responseJSON.product_name ).toEqual(
					'A Simple Product'
				);
				expect( responseJSON.status ).toEqual( 'approved' );
				expect( responseJSON.reviewer ).toEqual( 'John Doe' );
				expect( responseJSON.reviewer_email ).toEqual(
					'john.doe@example.com'
				);
				expect( responseJSON.review ).toEqual(
					'<p>Nice simple product!</p>\n'
				);
				expect( responseJSON.rating ).toEqual( 5 );
				expect( responseJSON.verified ).toEqual( false );
			} );

			test( 'can retrieve all product reviews', async ( { request } ) => {
				// call API to retrieve all product reviews
				const response = await request.get(
					'/wp-json/wc/v3/products/reviews'
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( Array.isArray( responseJSON ) ).toBe( true );
				expect( responseJSON.length ).toBeGreaterThan( 0 );
			} );

			test( 'can update a product review', async ( { request } ) => {
				// call API to retrieve all product reviews
				const response = await request.put(
					`wp-json/wc/v3/products/reviews/${ productReviewId }`,
					{
						data: {
							rating: 1,
						},
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.id ).toEqual( productReviewId );
				expect( responseJSON.product_id ).toEqual(
					reviewsTestProduct.id
				);
				expect( responseJSON.product_name ).toEqual(
					'A Simple Product'
				);
				expect( responseJSON.status ).toEqual( 'approved' );
				expect( responseJSON.reviewer ).toEqual( 'John Doe' );
				expect( responseJSON.reviewer_email ).toEqual(
					'john.doe@example.com'
				);
				expect( responseJSON.review ).toEqual( 'Nice simple product!' );
				expect( responseJSON.rating ).toEqual( 1 );
				expect( responseJSON.verified ).toEqual( false );
			} );

			test( 'can permanently delete a product review', async ( {
				request,
			} ) => {
				// Delete the product review.
				const response = await request.delete(
					`wp-json/wc/v3/products/reviews/${ productReviewId }`,
					{
						data: {
							force: true,
						},
					}
				);
				expect( response.status() ).toEqual( 200 );

				// Verify that the product review can no longer be retrieved.
				const getDeletedProductReviewResponse = await request.get(
					`wp-json/wc/v3/products/reviews/${ productReviewId }`
				);
				expect( getDeletedProductReviewResponse.status() ).toEqual(
					404
				);
			} );

			test( 'can batch update product reviews', async ( { request } ) => {
				// Batch create product reviews.
				const response = await request.post(
					`wp-json/wc/v3/products/reviews/batch`,
					{
						data: {
							create: [
								{
									product_id: reviewsTestProduct.id,
									review: 'Nice product!',
									reviewer: 'John Doe',
									reviewer_email: 'john.doe@example.com',
									rating: 4,
								},
								{
									product_id: reviewsTestProduct.id,
									review: 'I love this thing!',
									reviewer: 'Jane Doe',
									reviewer_email: 'Jane.doe@example.com',
									rating: 5,
								},
							],
						},
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.create[ 0 ].product_id ).toEqual(
					reviewsTestProduct.id
				);
				expect( responseJSON.create[ 0 ].review ).toEqual(
					'Nice product!'
				);
				expect( responseJSON.create[ 0 ].reviewer ).toEqual(
					'John Doe'
				);
				expect( responseJSON.create[ 0 ].reviewer_email ).toEqual(
					'john.doe@example.com'
				);
				expect( responseJSON.create[ 0 ].rating ).toEqual( 4 );

				expect( responseJSON.create[ 1 ].product_id ).toEqual(
					reviewsTestProduct.id
				);
				expect( responseJSON.create[ 1 ].review ).toEqual(
					'I love this thing!'
				);
				expect( responseJSON.create[ 1 ].reviewer ).toEqual(
					'Jane Doe'
				);
				expect( responseJSON.create[ 1 ].reviewer_email ).toEqual(
					'Jane.doe@example.com'
				);
				expect( responseJSON.create[ 1 ].rating ).toEqual( 5 );
				const review1Id = responseJSON.create[ 0 ].id;
				const review2Id = responseJSON.create[ 1 ].id;

				// Batch create a new review, update a review and delete another.
				const responseBatchUpdate = await request.post(
					`wp-json/wc/v3/products/reviews/batch`,
					{
						data: {
							create: [
								{
									product_id: reviewsTestProduct.id,
									review: 'Ok product.',
									reviewer: 'Jack Doe',
									reviewer_email: 'jack.doe@example.com',
									rating: 3,
								},
							],
							update: [
								{
									id: review1Id,
									review: 'On reflection, I hate this thing!',
									rating: 1,
								},
							],
							delete: [ review2Id ],
						},
					}
				);
				const responseBatchUpdateJSON =
					await responseBatchUpdate.json();
				const review3Id = responseBatchUpdateJSON.create[ 0 ].id;
				expect( response.status() ).toEqual( 200 );

				const responseUpdatedReview = await request.get(
					`wp-json/wc/v3/products/reviews/${ review1Id }`
				);
				const responseUpdatedReviewJSON =
					await responseUpdatedReview.json();
				expect( responseUpdatedReviewJSON.review ).toEqual(
					'<p>On reflection, I hate this thing!</p>\n'
				);
				expect( responseUpdatedReviewJSON.rating ).toEqual( 1 );

				// Verify that the deleted review can no longer be retrieved.
				const getDeletedProductReviewResponse = await request.get(
					`wp-json/wc/v3/products/reviews/${ review2Id }`
				);
				expect( getDeletedProductReviewResponse.status() ).toEqual(
					404
				);

				// Batch delete the created tags
				await request.post( `wp-json/wc/v3/products/reviews/batch`, {
					data: {
						delete: [ review1Id, review3Id ],
					},
				} );
			} );
		}
	);

	test.describe( 'Product shipping classes tests: CRUD', () => {
		let productShippingClassId;

		test( 'can add a product shipping class', async ( { request } ) => {
			const response = await request.post(
				'wp-json/wc/v3/products/shipping_classes',
				{
					data: {
						name: 'Priority',
					},
				}
			);
			const responseJSON = await response.json();
			productShippingClassId = responseJSON.id;

			expect( response.status() ).toEqual( 201 );
			expect( typeof productShippingClassId ).toEqual( 'number' );
			expect( responseJSON.name ).toEqual( 'Priority' );
			expect( responseJSON.slug ).toEqual( 'priority' );
			expect( responseJSON.description ).toEqual( '' );
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can retrieve a product shipping class', async ( {
			request,
		} ) => {
			const response = await request.get(
				`wp-json/wc/v3/products/shipping_classes/${ productShippingClassId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productShippingClassId );
			expect( responseJSON.name ).toEqual( 'Priority' );
			expect( responseJSON.slug ).toEqual( 'priority' );
			expect( responseJSON.description ).toEqual( '' );
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can retrieve all product shipping classes', async ( {
			request,
		} ) => {
			// call API to retrieve all product shipping classes
			const response = await request.get(
				'/wp-json/wc/v3/products/shipping_classes'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );

		test( 'can update a product shipping class', async ( { request } ) => {
			// call API to retrieve a product shipping class
			const response = await request.put(
				`wp-json/wc/v3/products/shipping_classes/${ productShippingClassId }`,
				{
					data: {
						description:
							'This is a description for the Priority shipping class.',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productShippingClassId );
			expect( responseJSON.name ).toEqual( 'Priority' );
			expect( responseJSON.slug ).toEqual( 'priority' );
			expect( responseJSON.description ).toEqual(
				'This is a description for the Priority shipping class.'
			);
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can permanently delete a product shipping class', async ( {
			request,
		} ) => {
			// Delete the product shipping class.
			const response = await request.delete(
				`wp-json/wc/v3/products/shipping_classes/${ productShippingClassId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the product shipping class can no longer be retrieved.
			const getDeletedProductShippingClassResponse = await request.get(
				`wp-json/wc/v3/products/shipping_classes/${ productShippingClassId }`
			);
			expect( getDeletedProductShippingClassResponse.status() ).toEqual(
				404
			);
		} );

		test(
			'can batch update product shipping classes',
			{ tag: [ '@skip-on-default-pressable', '@skip-on-default-wpcom' ] },
			async ( { request } ) => {
				// Batch create product shipping classes.
				const response = await request.post(
					`wp-json/wc/v3/products/shipping_classes/batch`,
					{
						data: {
							create: [
								{
									name: 'Small Items',
								},
								{
									name: 'Large Items',
								},
							],
						},
					}
				);
				const responseJSON = await response.json();
				expect( response.status() ).toEqual( 200 );
				expect( responseJSON.create[ 0 ].name ).toEqual(
					'Small Items'
				);
				expect( responseJSON.create[ 1 ].name ).toEqual(
					'Large Items'
				);
				const shippingClass1Id = responseJSON.create[ 0 ].id;
				const shippingClass2Id = responseJSON.create[ 1 ].id;

				// Batch create a new shipping class, update a shipping class and delete another.
				const responseBatchUpdate = await request.post(
					`wp-json/wc/v3/products/shipping_classes/batch`,
					{
						data: {
							create: [
								{
									name: 'Express',
								},
							],
							update: [
								{
									id: shippingClass1Id,
									description: 'Priority shipping.',
								},
							],
							delete: [ shippingClass2Id ],
						},
					}
				);
				const responseBatchUpdateJSON =
					await responseBatchUpdate.json();
				const shippingClass3Id = responseBatchUpdateJSON.create[ 0 ].id;
				expect( response.status() ).toEqual( 200 );

				const responseUpdatedShippingClass = await request.get(
					`wp-json/wc/v3/products/shipping_classes/${ shippingClass1Id }`
				);
				const responseUpdatedShippingClassJSON =
					await responseUpdatedShippingClass.json();
				expect( responseUpdatedShippingClassJSON.description ).toEqual(
					'Priority shipping.'
				);

				// Verify that the product tag can no longer be retrieved.
				const getDeletedProductShippingClassResponse =
					await request.get(
						`wp-json/wc/v3/products/shipping_classes/${ shippingClass2Id }`
					);
				expect(
					getDeletedProductShippingClassResponse.status()
				).toEqual( 404 );

				// Batch delete the created tags
				await request.post(
					`wp-json/wc/v3/products/shipping_classes/batch`,
					{
						data: {
							delete: [ shippingClass1Id, shippingClass3Id ],
						},
					}
				);
			}
		);
	} );

	test.describe( 'Product tags tests: CRUD', () => {
		let productTagId;

		test( 'can add a product tag', async ( { request } ) => {
			const response = await request.post(
				'wp-json/wc/v3/products/tags',
				{
					data: {
						name: 'Leather Shoes',
					},
				}
			);
			const responseJSON = await response.json();
			productTagId = responseJSON.id;

			expect( response.status() ).toEqual( 201 );
			expect( typeof productTagId ).toEqual( 'number' );
			expect( responseJSON.name ).toEqual( 'Leather Shoes' );
			expect( responseJSON.slug ).toEqual( 'leather-shoes' );
		} );

		test( 'can retrieve a product tag', async ( { request } ) => {
			const response = await request.get(
				`wp-json/wc/v3/products/tags/${ productTagId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productTagId );
			expect( responseJSON.name ).toEqual( 'Leather Shoes' );
			expect( responseJSON.slug ).toEqual( 'leather-shoes' );
		} );

		test( 'can retrieve all product tags', async ( { request } ) => {
			// call API to retrieve all product tags
			const response = await request.get(
				'/wp-json/wc/v3/products/tags'
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );

		test( 'can update a product tag', async ( { request } ) => {
			// call API to update a product tag
			const response = await request.put(
				`wp-json/wc/v3/products/tags/${ productTagId }`,
				{
					data: {
						description: 'Genuine leather.',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productTagId );
			expect( responseJSON.name ).toEqual( 'Leather Shoes' );
			expect( responseJSON.description ).toEqual( 'Genuine leather.' );
			expect( responseJSON.slug ).toEqual( 'leather-shoes' );
			expect( responseJSON.count ).toEqual( 0 );
		} );

		test( 'can permanently delete a product tag', async ( { request } ) => {
			// Delete the product tag.
			const response = await request.delete(
				`wp-json/wc/v3/products/tags/${ productTagId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// Verify that the product tag can no longer be retrieved.
			const getDeletedProductTagResponse = await request.get(
				`wp-json/wc/v3/products/tags/${ productTagId }`
			);
			expect( getDeletedProductTagResponse.status() ).toEqual( 404 );
		} );

		test( 'can batch update product tags', async ( { request } ) => {
			// Batch create 3 product tags.
			const response = await request.post(
				`wp-json/wc/v3/products/tags/batch`,
				{
					data: {
						create: [
							{
								name: 'Round toe',
							},
							{
								name: 'Flat',
							},
							{
								name: 'High heel',
							},
						],
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.create[ 0 ].name ).toEqual( 'Round toe' );
			expect( responseJSON.create[ 1 ].name ).toEqual( 'Flat' );
			expect( responseJSON.create[ 2 ].name ).toEqual( 'High heel' );
			const tag1Id = responseJSON.create[ 0 ].id;
			const tag2Id = responseJSON.create[ 1 ].id;
			const tag3Id = responseJSON.create[ 2 ].id;

			// Batch create a new tag, update 2 tags and delete another.
			const responseBatchUpdate = await request.post(
				`wp-json/wc/v3/products/tags/batch`,
				{
					data: {
						create: [
							{
								name: 'Football boot',
							},
						],
						update: [
							{
								id: tag1Id,
								description: 'Genuine leather.',
							},
							{
								id: tag2Id,
								description: 'Like a pancake.',
							},
						],
						delete: [ tag3Id ],
					},
				}
			);
			const responseBatchUpdateJSON = await responseBatchUpdate.json();
			const tag4Id = responseBatchUpdateJSON.create[ 0 ].id;
			expect( response.status() ).toEqual( 200 );

			const responseUpdatedTag1 = await request.get(
				`wp-json/wc/v3/products/tags/${ tag1Id }`
			);
			const responseUpdatedTag1JSON = await responseUpdatedTag1.json();
			expect( responseUpdatedTag1JSON.description ).toEqual(
				'Genuine leather.'
			);

			const responseUpdatedTag2 = await request.get(
				`wp-json/wc/v3/products/tags/${ tag2Id }`
			);
			const responseUpdatedTag2JSON = await responseUpdatedTag2.json();
			expect( responseUpdatedTag2JSON.description ).toEqual(
				'Like a pancake.'
			);

			// Verify that the product tag can no longer be retrieved.
			const getDeletedProductTagResponse = await request.get(
				`wp-json/wc/v3/products/tags/${ tag3Id }`
			);
			expect( getDeletedProductTagResponse.status() ).toEqual( 404 );

			// Batch delete the created tags
			await request.post( `wp-json/wc/v3/products/tags/batch`, {
				data: {
					delete: [ tag1Id, tag2Id, tag4Id ],
				},
			} );
		} );
	} );

	test( 'can add a virtual product', async ( { request } ) => {
		const response = await request.post( 'wp-json/wc/v3/products', {
			data: virtualProduct,
		} );
		const responseJSON = await response.json();
		const virtualProductId = responseJSON.id;

		expect( response.status() ).toEqual( 201 );
		expect( typeof virtualProductId ).toEqual( 'number' );
		expect( responseJSON ).toMatchObject( virtualProduct );
		expect( responseJSON.type ).toEqual( 'simple' );
		expect( responseJSON.status ).toEqual( 'publish' );
		expect( responseJSON.shipping_required ).toEqual( false );

		// Cleanup: Delete the virtual product
		await request.delete( `wp-json/wc/v3/products/${ virtualProductId }`, {
			data: {
				force: true,
			},
		} );
	} );

	test.describe( 'Product variation tests: CRUD', () => {
		let variableProductId;
		let productVariationId;

		test( 'can add a variable product', async ( { request } ) => {
			const response = await request.post( 'wp-json/wc/v3/products', {
				data: variableProduct,
			} );
			const responseJSON = await response.json();
			variableProductId = responseJSON.id;
			expect( response.status() ).toEqual( 201 );
			expect( typeof variableProductId ).toEqual( 'number' );
			expect( responseJSON ).toMatchObject( variableProduct );
			expect( responseJSON.status ).toEqual( 'publish' );
		} );

		test( 'can add a product variation', async ( { request } ) => {
			const response = await request.post(
				`wp-json/wc/v3/products/${ variableProductId }/variations`,
				{
					data: {
						regular_price: '29.00',
						attributes: [
							{
								name: 'Colour',
								option: 'Green',
							},
						],
					},
				}
			);
			const responseJSON = await response.json();
			productVariationId = responseJSON.id;
			expect( response.status() ).toEqual( 201 );
			expect( typeof productVariationId ).toEqual( 'number' );
			expect( responseJSON.regular_price ).toEqual( '29.00' );
		} );

		test( 'can retrieve a product variation', async ( { request } ) => {
			const response = await request.get(
				`wp-json/wc/v3/products/${ variableProductId }/variations/${ productVariationId }`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productVariationId );
			expect( responseJSON.regular_price ).toEqual( '29.00' );
		} );

		test( 'can retrieve all product variations', async ( { request } ) => {
			// call API to retrieve all product variations
			const response = await request.get(
				`wp-json/wc/v3/products/${ variableProductId }/variations`
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( Array.isArray( responseJSON ) ).toBe( true );
			expect( responseJSON.length ).toBeGreaterThan( 0 );
		} );

		test( 'can update a product variation', async ( { request } ) => {
			// call API to update the product variation
			const response = await request.put(
				`wp-json/wc/v3/products/${ variableProductId }/variations/${ productVariationId }`,
				{
					data: {
						regular_price: '30.00',
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			expect( responseJSON.id ).toEqual( productVariationId );
			expect( responseJSON.regular_price ).toEqual( '30.00' );
		} );

		test( 'can permanently delete a product variation', async ( {
			request,
		} ) => {
			// Delete the product variation.
			const response = await request.delete(
				`wp-json/wc/v3/products/${ variableProductId }/variations/${ productVariationId }`,
				{
					data: {
						force: true,
					},
				}
			);
			expect( response.status() ).toEqual( 200 );

			// if we're running on CI, then skip -- because objects are cached and they don't disappear instantly.
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( ! shouldSkip ) {
				// Verify that the product variation can no longer be retrieved.
				const getDeletedProductVariationResponse = await request.get(
					`wp-json/wc/v3/products/${ variableProductId }/variations/${ productVariationId }`
				);
				expect( getDeletedProductVariationResponse.status() ).toEqual(
					404
				);
			}
		} );

		test( 'can batch update product variations', async ( { request } ) => {
			// Batch create 2 product variations
			const response = await request.post(
				`wp-json/wc/v3/products/${ variableProductId }/variations/batch`,
				{
					data: {
						create: [
							{
								regular_price: '30.00',
								attributes: [
									{
										name: 'Colour',
										option: 'Green',
									},
								],
							},
							{
								regular_price: '35.00',
								attributes: [
									{
										name: 'Colour',
										option: 'Red',
									},
								],
							},
						],
					},
				}
			);
			const responseJSON = await response.json();
			expect( response.status() ).toEqual( 200 );
			const variation1Id = responseJSON.create[ 0 ].id;
			const variation2Id = responseJSON.create[ 1 ].id;
			expect( typeof variation1Id ).toEqual( 'number' );
			expect( typeof variation2Id ).toEqual( 'number' );
			expect( responseJSON.create[ 0 ].price ).toEqual( '30.00' );
			expect( responseJSON.create[ 1 ].price ).toEqual( '35.00' );

			// Batch create a new variation, update a variation and delete another.
			const responseBatchUpdate = await request.post(
				`wp-json/wc/v3/products/${ variableProductId }/variations/batch`,
				{
					data: {
						create: [
							{
								regular_price: '25.99',
								attributes: [
									{
										name: 'Colour',
										option: 'Blue',
									},
								],
							},
						],
						update: [
							{
								id: variation2Id,
								regular_price: '35.99',
							},
						],
						delete: [ variation1Id ],
					},
				}
			);

			expect( response.status() ).toEqual( 200 );
			const responseBatchUpdateJSON = await responseBatchUpdate.json();
			const variation3Id = responseBatchUpdateJSON.create[ 0 ].id;
			const responseUpdatedVariation = await request.get(
				`wp-json/wc/v3/products/${ variableProductId }/variations/${ variation2Id }`
			);
			const responseUpdatedVariationJSON =
				await responseUpdatedVariation.json();
			expect( responseUpdatedVariationJSON.regular_price ).toEqual(
				'35.99'
			);

			// if we're running on CI, then skip -- because objects are cached and they don't disappear instantly.
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( ! shouldSkip ) {
				// Verify that the deleted product variation can no longer be retrieved.
				const getDeletedProductVariationResponse = await request.get(
					`wp-json/wc/v3/products/${ variableProductId }/variations/${ variation1Id }`
				);

				expect( getDeletedProductVariationResponse.status() ).toEqual(
					404
				);
			}

			// Batch delete the created product variations
			await request.post(
				`wp-json/wc/v3/products/${ variableProductId }/variations/batch`,
				{
					data: {
						delete: [ variation2Id, variation3Id ],
					},
				}
			);

			// Cleanup: Delete the variable product
			await request.delete(
				`wp-json/wc/v3/products/${ variableProductId }`,
				{
					data: {
						force: true,
					},
				}
			);
		} );
	} );

	test( 'can view a single product', async ( {
		request,
		simpleTestProduct,
	} ) => {
		const response = await request.get(
			`wp-json/wc/v3/products/${ simpleTestProduct.id }`
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( simpleTestProduct.id );
	} );

	test( 'can update a single product', async ( {
		request,
		simpleTestProduct,
	} ) => {
		const updatePayload = {
			regular_price: '25.99',
		};

		const response = await request.put(
			`wp-json/wc/v3/products/${ simpleTestProduct.id }`,
			{
				data: updatePayload,
			}
		);
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( simpleTestProduct.id );
		expect( responseJSON.regular_price ).toEqual(
			updatePayload.regular_price
		);
	} );

	test( 'can delete a product', async ( { request } ) => {
		const createProductResponse = await request.post(
			'wp-json/wc/v3/products',
			{
				name: 'A product to be deleted',
				regular_price: '24',
				description:
					'Description for this simple product that will be deleted.',
				short_description:
					'Shorter description for this simple product that will be deleted.',
			}
		);

		const product = await createProductResponse.json();

		const response = await request.delete(
			`wp-json/wc/v3/products/${ product.id }`,
			{
				data: {
					force: true,
				},
			}
		);
		const responseJSON = await response.json();

		expect( response.status() ).toEqual( 200 );
		expect( responseJSON.id ).toEqual( product.id );

		const retrieveDeletedProductResponse = await request.get(
			`wp-json/wc/v3/products/${ product.id }`
		);
		expect( retrieveDeletedProductResponse.status() ).toEqual( 404 );
	} );

	test.describe( 'Batch update products', () => {
		const product1 = {
			name: 'Batch Created Product 1',
			regular_price: '25',
			description: 'Description for this simple product.',
			short_description: 'Shorter description.',
		};
		const product2 = {
			name: 'Batch Created Product 2',
			regular_price: '25',
			description: 'Description for this simple product.',
			short_description: 'Shorter description.',
		};
		const expectedProducts = [ product1, product2 ];

		test( 'can batch create products', async ( { request } ) => {
			// Send request to batch create products
			const batchCreatePayload = batch( 'create', expectedProducts );
			const response = await request.post(
				'wp-json/wc/v3/products/batch',
				{
					data: batchCreatePayload,
				}
			);
			const responseJSON = await response.json();
			const actualBatchCreatedProducts = responseJSON.create;

			expect( response.status() ).toEqual( 200 );
			expect( actualBatchCreatedProducts ).toHaveLength(
				expectedProducts.length
			);

			// Verify id and name of the batch created products
			for ( let i = 0; i < actualBatchCreatedProducts.length; i++ ) {
				const { id, name } = actualBatchCreatedProducts[ i ];

				expect( typeof id ).toEqual( 'number' );
				expect( name ).toEqual( expectedProducts[ i ].name );

				// Save the product ID for use later in the batch update and delete tests
				expectedProducts[ i ].id = id;
			}
		} );

		test( 'can batch update products', async ( { request } ) => {
			// Send request to batch update the regular price
			const newRegularPrice = '12.34';
			for ( let i = 0; i < expectedProducts.length; i++ ) {
				expectedProducts[ i ].regular_price = newRegularPrice;
			}
			const batchUpdatePayload = batch( 'update', expectedProducts );
			const response = await request.put(
				'wp-json/wc/v3/products/batch',
				{
					data: batchUpdatePayload,
				}
			);
			const responseJSON = await response.json();
			const actualUpdatedProducts = responseJSON.update;

			expect( response.status() ).toEqual( 200 );
			expect( actualUpdatedProducts ).toHaveLength(
				expectedProducts.length
			);

			// Verify that the regular price of each product was updated
			for ( let i = 0; i < actualUpdatedProducts.length; i++ ) {
				const { id, regular_price } = actualUpdatedProducts[ i ];

				expect( id ).toEqual( expectedProducts[ i ].id );
				expect( regular_price ).toEqual( newRegularPrice );
			}
		} );

		test( 'can batch delete products', async ( { request } ) => {
			// Send request to batch delete the products created earlier
			const idsToDelete = expectedProducts.map( ( { id } ) => id );
			const batchDeletePayload = batch( 'delete', idsToDelete );
			let response = await request.post( 'wp-json/wc/v3/products/batch', {
				data: batchDeletePayload,
			} );
			const responseJSON = await response.json();
			const actualBatchDeletedProducts = responseJSON.delete;

			expect( response.status() ).toEqual( 200 );
			expect( actualBatchDeletedProducts ).toHaveLength(
				expectedProducts.length
			);

			// Verify that the correct products were deleted
			for ( let i = 0; i < actualBatchDeletedProducts.length; i++ ) {
				const deletedProduct = actualBatchDeletedProducts[ i ];
				expect( deletedProduct ).toMatchObject( expectedProducts[ i ] );
			}

			// Verify that the deleted product ID's can no longer be retrieved
			for ( const id of idsToDelete ) {
				response = await request.get(
					`wp-json/wc/v3/products/${ id }`
				);
				expect( response.status() ).toEqual( 404 );
			}
		} );
	} );
} );
