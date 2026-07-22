/**
 * External dependencies
 */
import { Request } from '@playwright/test';
import {
	test as base,
	expect,
	wpCLI,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, {
	BLOCK_LABELS,
	SELECTORS,
} from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

type TemplateSlug =
	| 'taxonomy-product_cat'
	| 'taxonomy-product_tag'
	| 'archive-product'
	| 'product-search-results';

function assertTemplateResponse(
	templateResponse: unknown,
	slug: TemplateSlug,
	templateContent: string
): asserts templateResponse is {
	wp_id: number;
	id: string;
	slug: TemplateSlug;
	type: 'wp_template';
	status: 'publish';
	content: { raw: string };
} {
	if (
		typeof templateResponse !== 'object' ||
		templateResponse === null ||
		Array.isArray( templateResponse ) ||
		! ( 'wp_id' in templateResponse ) ||
		typeof templateResponse.wp_id !== 'number' ||
		! Number.isInteger( templateResponse.wp_id ) ||
		templateResponse.wp_id <= 0 ||
		! ( 'id' in templateResponse ) ||
		templateResponse.id !== `${ BLOCK_THEME_SLUG }//${ slug }` ||
		! ( 'slug' in templateResponse ) ||
		templateResponse.slug !== slug ||
		! ( 'type' in templateResponse ) ||
		templateResponse.type !== 'wp_template' ||
		! ( 'status' in templateResponse ) ||
		templateResponse.status !== 'publish' ||
		! ( 'content' in templateResponse ) ||
		typeof templateResponse.content !== 'object' ||
		templateResponse.content === null ||
		Array.isArray( templateResponse.content ) ||
		! ( 'raw' in templateResponse.content ) ||
		templateResponse.content.raw !== templateContent
	) {
		throw new Error(
			`Unexpected POST /wp/v2/templates response for ${ slug }: ${ JSON.stringify(
				templateResponse,
				null,
				2
			) }`
		);
	}
}

test.describe( 'Product Collection', () => {
	test( 'Renders product collection block correctly with 9 items', async ( {
		pageObject,
	} ) => {
		await pageObject.createNewPostAndInsertBlock();
		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );

		await pageObject.publishAndGoToFrontend();

		await expect( pageObject.products ).toHaveCount( 9 );
		await expect( pageObject.productImages ).toHaveCount( 9 );
		await expect( pageObject.productTitles ).toHaveCount( 9 );
		await expect( pageObject.productPrices ).toHaveCount( 9 );
		await expect( pageObject.addToCartButtons ).toHaveCount( 9 );
	} );

	test( 'Can be migrated to from Products (Deprecated) block', async ( {
		page,
		editor,
		admin,
	} ) => {
		await admin.createNewPost();

		await editor.insertBlock( {
			name: 'core/query',
			attributes: {
				namespace: 'woocommerce/product-query',
			},
		} );

		await expect(
			editor.canvas.getByLabel( 'Block: Products (Deprecated)' )
		).toBeVisible();

		await editor.canvas
			.getByRole( 'button', { name: 'Start blank' } )
			.click();
		await editor.canvas.getByLabel( 'Title & Date' ).click();

		await page
			.getByRole( 'button', { name: 'Upgrade to Product Collection' } )
			.click();

		await expect(
			editor.canvas.getByLabel( 'Block: Products (Deprecated)' )
		).toBeHidden();
		await expect(
			editor.canvas.getByLabel( 'Block: Product Collection' ).first()
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Choose collection' } )
		).toBeVisible();
	} );

	test( 'Handles Product Collection no-results rendering cases', async ( {
		admin,
		page,
		editor,
		pageObject,
	} ) => {
		await test.step( 'does not render', async () => {
			await admin.createNewPost();

			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'featured' );
			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				max: '1',
			} );

			const featuredBlock = editor.canvas.getByLabel( 'Block: Featured' );

			await expect(
				featuredBlock.getByText( 'Featured products' )
			).toBeVisible();
			// The "No results found" info is rendered in editor for all collections.
			await expect(
				featuredBlock.getByText( 'No products to display' )
			).toBeVisible();

			await pageObject.publishAndGoToFrontend();

			const content = page.locator( 'main' );

			await expect( content ).not.toContainText( 'Featured products' );
			await expect( content ).not.toContainText(
				'No products to display'
			);
		} );

		// This test ensures the runtime render state is correctly reset for
		// each block.
		await test.step( 'does not prevent subsequent blocks from render', async () => {
			await admin.createNewPost();

			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'featured' );
			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				max: '1',
			} );

			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'topRated' );

			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.products ).toHaveCount( 5 );

			await pageObject.publishAndGoToFrontend();

			await pageObject.refreshLocators( 'frontend' );
			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( page.locator( 'main' ) ).not.toContainText(
				'Featured products'
			);
		} );

		await test.step( 'renders if No Results block is present', async () => {
			await admin.createNewPost();

			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'productCatalog' );
			await pageObject.addFilter( 'Price Range' );
			await pageObject.setPriceRange( {
				max: '1',
			} );

			await expect(
				editor.canvas.getByText( 'No results found' )
			).toBeVisible();

			await pageObject.publishAndGoToFrontend();

			await expect( page.getByText( 'No results found' ) ).toBeVisible();
		} );
	} );

	test.describe( 'Renders correctly with all Product Elements', () => {
		const expectedProductContent = [
			'Beanie', // core/post-title
			'$20.00 Original price was: $20.00.$18.00Current price is: $18.00.', // woocommerce/product-price
			'woo-beanie', // woocommerce/product-sku
			'This is a simple product.', // core/post-excerpt
			'Accessories', // core/post-terms - product_cat
			'Recommended', // core/post-terms - product_tag
			'SaleProduct on sale', // woocommerce/product-sale-badge
			'Add to cart', // woocommerce/product-button
		];

		test( 'In a post, Product Archive, and Home Page', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await test.step( 'In a post', async () => {
				await pageObject.createNewPostAndInsertBlock();

				await expect(
					editor.canvas.locator(
						'[data-testid="product-image"]:visible'
					)
				).toHaveCount( 9 );

				await pageObject.insertProductElements();
				await pageObject.publishAndGoToFrontend();

				for ( const content of expectedProductContent ) {
					await expect(
						page.locator( '.wc-block-product-template' )
					).toContainText( content );
				}
			} );

			await test.step( 'In a Product Archive (Product Catalog)', async () => {
				await pageObject.goToEditorTemplate();

				await expect(
					editor.canvas.locator(
						'[data-testid="product-image"]:visible'
					)
				).toHaveCount( 16 );

				await pageObject.insertProductElements();
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );
				await pageObject.goToProductCatalogFrontend();

				// Workaround for the issue with the product change not being
				// reflected in the frontend yet.
				try {
					await page.getByText( 'woo-beanie' ).waitFor();
				} catch ( _error ) {
					await page.reload();
				}

				for ( const content of expectedProductContent ) {
					await expect(
						page.locator( '.wc-block-product-template' )
					).toContainText( content );
				}
			} );

			await test.step( 'On a Home Page', async () => {
				await pageObject.goToHomePageAndInsertCollection();

				await expect(
					editor.canvas.locator(
						'[data-testid="product-image"]:visible'
					)
				).toHaveCount( 9 );

				await pageObject.insertProductElements();
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );
				await pageObject.goToHomePageFrontend();

				for ( const content of expectedProductContent ) {
					await expect(
						page.locator( '.wc-block-product-template' )
					).toContainText( content );
				}
			} );
		} );
	} );

	test.describe( 'Responsive', () => {
		test.beforeEach( async ( { pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock();
		} );
		test( 'Block with shrink columns ENABLED correctly displays as grid', async ( {
			pageObject,
		} ) => {
			await pageObject.publishAndGoToFrontend();
			const productTemplate = pageObject.productTemplate;

			await expect( productTemplate ).toHaveCSS( 'display', 'grid' );
			// By default there should be 3 columns, so grid-template-columns
			// should be compiled to three values
			await expect( productTemplate ).toHaveCSS(
				'grid-template-columns',
				/^\d+(\.\d+)?px \d+(\.\d+)?px \d+(\.\d+)?px$/
			);

			await pageObject.setViewportSize( {
				height: 667,
				width: 390, // iPhone 12 Pro
			} );

			// Verifies grid-template-columns compiles to two numbers,
			// which means there are two columns on mobile.
			await expect( productTemplate ).toHaveCSS(
				'grid-template-columns',
				/^\d+(\.\d+)?px \d+(\.\d+)?px$/
			);
		} );

		test( 'Block with shrink columns DISABLED collapses to single column on small screens', async ( {
			pageObject,
		} ) => {
			await pageObject.setShrinkColumnsToFit( false );
			await pageObject.publishAndGoToFrontend();

			const productTemplate = pageObject.productTemplate;

			await expect( productTemplate ).not.toHaveCSS( 'display', 'grid' );

			const firstProduct = pageObject.products.first();

			// In the original viewport size, we expect the product width to be less than the parent width
			// because we will have more than 1 column
			let productSize = await firstProduct.boundingBox();
			let parentSize = await firstProduct
				.locator( 'xpath=..' )
				.boundingBox();
			expect( productSize?.width ).toBeLessThan(
				parentSize?.width as number
			);

			await pageObject.setViewportSize( {
				height: 667,
				width: 390, // iPhone 12 Pro
			} );

			// In the smaller viewport size, we expect the product width to be (approximately) the same as the parent width
			// because we will have only 1 column
			productSize = await firstProduct.boundingBox();
			parentSize = await firstProduct.locator( 'xpath=..' ).boundingBox();
			expect( productSize?.width ).toBeCloseTo(
				parentSize?.width as number
			);
		} );
	} );

	test.describe( 'With other blocks', () => {
		test( 'covers Single Product context and multiple Pagination blocks', async ( {
			admin,
			editor,
			pageObject,
		} ) => {
			await test.step( 'In Single Product block', async () => {
				await admin.createNewPost();
				await pageObject.insertProductCollectionInSingleProductBlock();
				await pageObject.chooseCollectionInPost( 'featured' );
				await pageObject.refreshLocators( 'editor' );

				const featuredProducts = [
					'Cap',
					'Hoodie with Zipper',
					'Sunglasses',
					'V-Neck T-Shirt',
				];
				const featuredProductsPrices = [
					'Previous price:$18.00Discounted price:$16.00',
					'$45.00',
					'$90.00',
					'Price between $15.00 and $20.00$15.00 — $20.00',
				];

				await expect( pageObject.products ).toHaveCount( 4 );
				// This verifies if Core's block context is provided
				await expect( pageObject.productTitles ).toHaveText(
					featuredProducts
				);
				// This verifies if Blocks's product context is provided
				await expect( pageObject.productPrices ).toHaveText(
					featuredProductsPrices
				);
			} );

			await test.step( 'With multiple Pagination blocks', async () => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( 'productCatalog' );
				const paginations = editor.canvas.getByLabel(
					BLOCK_LABELS.pagination
				);

				await expect( paginations ).toHaveCount( 1 );

				const siblingBlock = await editor.getBlockByName(
					'woocommerce/product-template'
				);
				await editor.selectBlocks( siblingBlock );
				await editor.insertBlockUsingGlobalInserter( 'Pagination' );

				await expect( paginations ).toHaveCount( 2 );
				await pageObject.refreshLocators( 'editor' );
				await expect( pageObject.products ).toHaveCount( 9 );
			} );
		} );
	} );

	test.describe( 'Location is recognized', () => {
		const filterRequest = ( request: Request ) => {
			const url = request.url();
			return (
				url.includes( 'wp/v2/product' ) &&
				url.includes( 'isProductCollectionBlock=true' )
			);
		};

		const filterProductRequest = ( request: Request ) => {
			const url = request.url();
			const searchParams = new URLSearchParams( request.url() );

			return (
				url.includes( 'wp/v2/product' ) &&
				searchParams.get( 'isProductCollectionBlock' ) === 'true' &&
				!! searchParams.get(
					`productCollectionLocation[sourceData][productId]`
				)
			);
		};

		const getLocationDetailsFromRequest = (
			request: Request,
			locationType?: string
		) => {
			const searchParams = new URLSearchParams( request.url() );

			if ( locationType === 'product' ) {
				return {
					type: searchParams.get( 'productCollectionLocation[type]' ),
					productId: searchParams.get(
						`productCollectionLocation[sourceData][productId]`
					),
				};
			}

			if ( locationType === 'archive' ) {
				return {
					type: searchParams.get( 'productCollectionLocation[type]' ),
					taxonomy: searchParams.get(
						`productCollectionLocation[sourceData][taxonomy]`
					),
					termId: searchParams.get(
						`productCollectionLocation[sourceData][termId]`
					),
				};
			}

			return {
				type: searchParams.get( 'productCollectionLocation[type]' ),
				sourceData: searchParams.get(
					`productCollectionLocation[sourceData]`
				),
			};
		};

		test( 'as product in specific Single Product template', async ( {
			admin,
			page,
			pageObject,
			editor,
			wpCoreVersion,
		} ) => {
			await admin.visitSiteEditor( { path: '/wp_template' } );

			await page
				.getByRole( 'button', {
					name:
						wpCoreVersion >= 6.8
							? 'Add Template'
							: 'Add New Template',
				} )
				.click();

			await page
				.getByRole( 'button', { name: 'Single Item: Product' } )
				.click();

			await page
				.getByRole( 'option', {
					name: `Cap http://localhost:${
						process.env.WP_ENV_TESTS_PORT || '8086'
					}/product/cap/`,
				} )
				.click();
			await page
				.getByRole( 'button', {
					name: 'Skip',
				} )
				.click();

			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			await editor.closeGlobalBlockInserter();

			const locationRequestPromise =
				page.waitForRequest( filterProductRequest );
			await pageObject.chooseCollectionInTemplate( 'featured' );
			const locationRequest = await locationRequestPromise;

			const { type, productId } = getLocationDetailsFromRequest(
				locationRequest,
				'product'
			);

			expect( type ).toBe( 'product' );
			expect( productId ).toBeTruthy();
		} );
		test( 'as category and tag in generic archive templates', async ( {
			admin,
			editor,
			pageObject,
			page,
		} ) => {
			await test.step( 'as category in Products by Category template', async () => {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );
				await editor.createTemplate( {
					templateName: 'Products by Category',
				} );
				await editor.insertBlockUsingGlobalInserter(
					pageObject.BLOCK_NAME
				);

				const locationRequestPromise =
					page.waitForRequest( filterRequest );
				await pageObject.chooseCollectionInTemplate( 'featured' );
				const locationRequest = await locationRequestPromise;
				const { type, taxonomy, termId } =
					getLocationDetailsFromRequest( locationRequest, 'archive' );

				expect( type ).toBe( 'archive' );
				expect( taxonomy ).toBe( 'product_cat' );
				// Field is sent as a null but browser converts it to empty string
				expect( termId ).toBe( '' );
			} );

			await test.step( 'as tag in Products by Tag template', async () => {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );
				await editor.createTemplate( {
					templateName: 'Products by Tag',
				} );
				await editor.insertBlockUsingGlobalInserter(
					pageObject.BLOCK_NAME
				);

				const locationRequestPromise =
					page.waitForRequest( filterRequest );
				await pageObject.chooseCollectionInTemplate( 'featured' );
				const locationRequest = await locationRequestPromise;
				const { type, taxonomy, termId } =
					getLocationDetailsFromRequest( locationRequest, 'archive' );

				expect( type ).toBe( 'archive' );
				expect( taxonomy ).toBe( 'product_tag' );
				// Field is sent as a null but browser converts it to empty string
				expect( termId ).toBe( '' );
			} );
		} );

		test( 'as site in post', async ( {
			admin,
			editor,
			pageObject,
			page,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			const locationRequestPromise = page.waitForRequest( filterRequest );
			await pageObject.chooseCollectionInPost( 'featured' );
			const locationRequest = await locationRequestPromise;
			const { type, sourceData } =
				getLocationDetailsFromRequest( locationRequest );

			expect( type ).toBe( 'site' );
			// Field is not sent at all. URLSearchParams get method returns a null
			// if field is not available.
			expect( sourceData ).toBe( null );
		} );

		test( 'as product in Single Product block in post', async ( {
			admin,
			pageObject,
			page,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollectionInSingleProductBlock();
			const locationRequestPromise =
				page.waitForRequest( filterProductRequest );
			await pageObject.chooseCollectionInPost( 'featured' );
			const locationRequest = await locationRequestPromise;
			const { type, productId } = getLocationDetailsFromRequest(
				locationRequest,
				'product'
			);

			expect( type ).toBe( 'product' );
			expect( productId ).toBeTruthy();
		} );
	} );

	test.describe( 'Query Context in Editor', () => {
		test( 'Collections: collection should be present in query context', async ( {
			pageObject,
		} ) => {
			const url = await pageObject.setupAndFetchQueryContextURL( {
				collection: 'onSale',
			} );

			const collectionName = url.searchParams.get(
				'productCollectionQueryContext[collection]'
			);
			expect( collectionName ).toBeTruthy();
			expect( collectionName ).toBe(
				'woocommerce/product-collection/on-sale'
			);
		} );
	} );

	test.describe( 'Preview mode in generic archive templates', () => {
		const genericArchiveTemplates = [
			{
				name: 'Products by Tag',
				path: `${ BLOCK_THEME_SLUG }//taxonomy-product_tag`,
				needsCreation: true,
			},
			{
				name: 'Products by Category',
				path: `${ BLOCK_THEME_SLUG }//taxonomy-product_cat`,
				needsCreation: true,
			},
			{
				name: 'Products by Attribute',
				path: `${ BLOCK_THEME_SLUG }//taxonomy-product_attribute`,
			},
		];

		test( 'preserves preview visibility across generic archive templates', async ( {
			admin,
			editor,
			pageObject,
		} ) => {
			for ( const {
				name,
				path,
				needsCreation,
			} of genericArchiveTemplates ) {
				await test.step( `${ name } template`, async () => {
					if ( needsCreation ) {
						await admin.visitSiteEditor( {
							postType: 'wp_template',
						} );
						await editor.createTemplate( {
							templateName: name,
						} );
					} else {
						await pageObject.goToEditorTemplate( path );
					}
					await pageObject.focusProductCollection();

					const previewButtonLocator = editor.canvas.getByTestId(
						SELECTORS.previewButtonTestID
					);

					// The preview button should be visible
					await expect( previewButtonLocator ).toBeVisible();

					// The preview button should be hidden when the block is not selected.
					// Changing focus.
					const otherBlockSelector = editor.canvas.getByLabel(
						'Block: Archive Title'
					);
					await editor.selectBlocks( otherBlockSelector );
					await expect( previewButtonLocator ).toBeHidden();

					// Preview button should be visible again when the block is selected.
					await pageObject.focusProductCollection();
					await expect( previewButtonLocator ).toBeVisible();
				} );
			}
		} );
	} );

	// Tests for regressions of https://github.com/woocommerce/woocommerce/pull/47994
	test.describe( 'Product Collection should be visible after Refresh', () => {
		test( 'Template collections should be visible after Refresh', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await test.step( 'Product Collection should be visible after Refresh in a Template', async () => {
				await pageObject.goToEditorTemplate();
				const productTemplate = editor.canvas.getByLabel(
					BLOCK_LABELS.productTemplate
				);
				await expect( productTemplate ).toBeVisible();

				// Refresh the template and verify the block is still visible
				await page.reload();
				await expect( productTemplate ).toBeVisible();
			} );

			await test.step( 'On Sale Products collection should be visible after Refresh', async () => {
				await pageObject.goToEditorTemplate();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInTemplate( 'onSale' );

				const productTemplate = editor.canvas.getByLabel(
					BLOCK_LABELS.productTemplate
				);

				await expect( productTemplate ).toHaveCount( 2 );

				// Refresh the template and verify "On Sale Products" collection is still visible
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );
				await page.reload();
				await expect( productTemplate ).toHaveCount( 2 );
			} );
		} );

		test( 'Post collections should be visible after Refresh', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await test.step( 'Product Collection should be visible after Refresh in a Post', async () => {
				await pageObject.createNewPostAndInsertBlock();
				await expect( pageObject.productTemplate ).toBeVisible();

				// Refresh the post and verify the block is still visible
				await editor.publishPost();
				await page.reload();
				await expect( pageObject.productTemplate ).toBeVisible();
			} );

			await test.step( 'On Sale Products collection should be visible after Refresh in a Post', async () => {
				await pageObject.createNewPostAndInsertBlock( 'onSale' );
				await expect( pageObject.productTemplate ).toBeVisible();

				// Refresh the post and verify "On Sale Products" collection is still visible
				await editor.saveDraft();
				await page.reload();
				await expect( pageObject.productTemplate ).toBeVisible();
			} );
		} );
	} );

	const templates = [
		// This test is disabled because archives are disabled for attributes by default. This can be uncommented when this is toggled on.
		//'taxonomy-product_attribute': {
		//	templateTitle: 'Product Attribute',
		//	slug: 'taxonomy-product_attribute',
		//	frontendPage: '/product-attribute/color/',
		//	legacyBlockName: 'woocommerce/legacy-template',
		//},
		{
			templateTitle: 'Product Category',
			slug: 'taxonomy-product_cat',
			frontendPage: '/product-category/music/',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 2,
		},
		{
			templateTitle: 'Product Tag',
			slug: 'taxonomy-product_tag',
			frontendPage: '/product-tag/recommended/',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 2,
		},
		{
			templateTitle: 'Product Catalog',
			slug: 'archive-product',
			frontendPage: '/shop/',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 16,
		},
		{
			templateTitle: 'Product Search Results',
			slug: 'product-search-results',
			frontendPage: '/?s=shirt&post_type=product',
			legacyBlockName: 'woocommerce/legacy-template',
			expectedProductsCount: 3,
		},
	] as const;

	test( 'Product Collection block matches with classic template blocks', async ( {
		pageObject,
		requestUtils,
		admin,
		editor,
		page,
	} ) => {
		for ( const {
			templateTitle,
			slug,
			frontendPage,
			legacyBlockName,
			expectedProductsCount,
		} of templates ) {
			await test.step( `${ templateTitle } template › Product Collection block matches with classic template block`, async () => {
				await pageObject.refreshLocators( 'frontend' );

				await page.goto( frontendPage );

				const productCollectionProductNames =
					await pageObject.getProductNames();

				const createRestTemplate = async () => {
					const templateContent = `placeholder\n\n<!-- wp:woocommerce/legacy-template {"template":"${ slug }"} /-->`;
					const templateResponse: unknown =
						await requestUtils.createTemplate( 'wp_template', {
							slug,
							title: 'classic template test',
							content: templateContent,
						} );

					assertTemplateResponse(
						templateResponse,
						slug,
						templateContent
					);
				};
				const createEditorTemplate = async () => {
					const template = await requestUtils.createTemplate(
						'wp_template',
						{
							slug,
							title: 'classic template test',
							content: 'placeholder',
						}
					);

					await admin.visitSiteEditor( {
						postId: template.id,
						postType: 'wp_template',
						canvas: 'edit',
					} );

					await expect(
						editor.getCustomHtmlBlockContentLocator( 'placeholder' )
					).toBeVisible();

					await editor.insertBlock( { name: legacyBlockName } );

					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );
				};
				const createTemplateBySlug: Record<
					TemplateSlug,
					() => Promise< void >
				> = {
					'taxonomy-product_cat': createRestTemplate,
					'taxonomy-product_tag': createEditorTemplate,
					'archive-product': createRestTemplate,
					'product-search-results': createRestTemplate,
				};

				await createTemplateBySlug[ slug ]();

				await page.goto( frontendPage );

				const classicProducts = page.locator(
					'.woocommerce-loop-product__title'
				);

				await expect( classicProducts ).toHaveCount(
					expectedProductsCount
				);

				const classicProductsNames =
					await classicProducts.allTextContents();

				expect( classicProductsNames ).toEqual(
					productCollectionProductNames
				);
			} );
		}
	} );

	test.describe( 'default query can be modified', () => {
		test( 'default query can be modified', async ( {
			page,
			pageObject,
			editor,
		} ) => {
			await wpCLI(
				'option update woocommerce_default_catalog_orderby price'
			);

			await pageObject.goToEditorTemplate();

			await pageObject.focusProductCollection();

			// Verify the default order matches the option in the database.
			const sidebarSettings = pageObject.locateSidebarSettings();
			const orderBySelect = sidebarSettings.getByRole( 'combobox', {
				name: 'Default sort by',
			} );
			const editorProductTitle = editor.canvas
				.locator( SELECTORS.productTitle )
				.first();

			await expect( orderBySelect ).toHaveValue( 'price' );
			await expect( editorProductTitle ).toHaveText( 'Single' );

			await orderBySelect.selectOption( 'price-desc' );

			await expect( editorProductTitle ).toHaveText( 'Sunglasses' );

			await editor.saveSiteEditorEntities();
			await pageObject.goToProductCatalogFrontend();

			const frontendProductTitle = page
				.locator( SELECTORS.productTitle )
				.first();
			await expect( frontendProductTitle ).toContainText( 'Sunglasses' );

			await wpCLI(
				'option update woocommerce_default_catalog_orderby menu_order'
			);
		} );
	} );

	test.describe( 'Editor: In taxonomies templates', () => {
		test( 'Products by specific category and tag templates display matching products', async ( {
			admin,
			page,
			editor,
			wpCoreVersion,
		} ) => {
			await test.step( 'Products by specific category template displays products from this category', async () => {
				await wpCLI(
					'option update woocommerce_default_catalog_orderby price'
				);

				const expectedProducts = [
					'Hoodie',
					'Hoodie with Logo',
					'Hoodie with Zipper',
				];

				await admin.visitSiteEditor( {
					path: '/wp_template',
				} );

				// We need to wait for Product categories to load. Otherwise clicking
				// on Products by Category might direct the user to the generic
				// template.
				await Promise.all( [
					admin.page.waitForResponse( ( response ) => {
						return response
							.url()
							.includes( 'wp-json/wp/v2/product_cat' );
					} ),
					page
						.getByRole( 'button', {
							name:
								wpCoreVersion >= 6.8
									? 'Add Template'
									: 'Add New Template',
						} )
						.click(),
				] );

				await page
					.getByRole( 'button', {
						name: 'Products by Category',
					} )
					.click();
				await page
					.getByRole( 'button', {
						name: 'For a specific item',
					} )
					.click();
				await page
					.getByRole( 'option', {
						name: `Hoodies`,
					} )
					.click();
				await page
					.getByRole( 'option', {
						name: 'Fallback content',
					} )
					.click();

				const products = editor.canvas.getByLabel( 'Block: Title' );

				await expect( products ).toHaveText( expectedProducts );

				await wpCLI(
					'option update woocommerce_default_catalog_orderby menu_order'
				);
			} );
			await test.step( 'Products by specific tag template displays products from this tag', async () => {
				await wpCLI(
					'option update woocommerce_default_catalog_orderby price'
				);

				const expectedProducts = [ 'Beanie', 'Hoodie' ];

				await admin.visitSiteEditor( {
					path: '/wp_template',
				} );

				// We need to wait for Product tags to load. Otherwise clicking
				// on Products by Tag might direct the user to the generic template.
				await Promise.all( [
					admin.page.waitForResponse( ( response ) => {
						return response
							.url()
							.includes( 'wp-json/wp/v2/product_tag' );
					} ),
					page
						.getByRole( 'button', {
							name:
								wpCoreVersion >= 6.8
									? 'Add Template'
									: 'Add New Template',
						} )
						.click(),
				] );

				await page
					.getByRole( 'button', {
						name: 'Products by Tag',
					} )
					.click();
				await page
					.getByRole( 'button', {
						name: 'For a specific item',
					} )
					.click();
				await page
					.getByRole( 'option', {
						name: `Recommended`,
					} )
					.click();
				await page
					.getByRole( 'option', {
						name: 'Fallback content',
					} )
					.click();

				const products = editor.canvas.getByLabel( 'Block: Title' );

				await expect( products ).toHaveText( expectedProducts );

				await wpCLI(
					'option update woocommerce_default_catalog_orderby menu_order'
				);
			} );
		} );
	} );
} );
