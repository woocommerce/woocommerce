/**
 * External dependencies
 */
import type { Locator, Page, Request } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

type RestApi = {
	get: ( path: string ) => Promise< { data: Record< string, unknown > } >;
	post: (
		path: string,
		data?: Record< string, unknown >
	) => Promise< { data: Record< string, unknown > } >;
};

type Term = {
	id: number;
	name: string;
	slug: string;
};

type GlobalAttribute = {
	id: number;
	name: string;
	taxonomy: string;
	terms: Term[];
};

type Product = {
	id: number;
	name: string;
	slug: string;
};

type BoundingBox = {
	x: number;
	y: number;
	width: number;
	height: number;
};

const createdProductIds: number[] = [];
const createdAttributeIds: number[] = [];

let sizeAttribute: GlobalAttribute;
let styleAttribute: GlobalAttribute;
let emptyProduct: Product;
let addAttributeProduct: Product;
let singleAttributeProduct: Product;
let simpleProduct: Product;
let manySelectedProduct: Product;
let variableProduct: Product;

async function createGlobalAttribute(
	restApi: RestApi,
	name: string,
	termNames: string[]
): Promise< GlobalAttribute > {
	const attribute = (
		await restApi.post( `${ WC_API_PATH }/products/attributes`, {
			name,
		} )
	 ).data as { id: number; name: string; slug: string };

	createdAttributeIds.push( attribute.id );

	const terms: Term[] = [];
	for ( const termName of termNames ) {
		const term = (
			await restApi.post(
				`${ WC_API_PATH }/products/attributes/${ attribute.id }/terms`,
				{
					name: termName,
				}
			)
		 ).data as Term;

		terms.push( term );
	}

	return {
		id: attribute.id,
		name: attribute.name,
		taxonomy: attribute.slug.startsWith( 'pa_' )
			? attribute.slug
			: `pa_${ attribute.slug }`,
		terms,
	};
}

async function createProduct(
	restApi: RestApi,
	data: Record< string, unknown >
): Promise< Product > {
	const product = ( await restApi.post( `${ WC_API_PATH }/products`, data ) )
		.data as Product;

	createdProductIds.push( product.id );

	return product;
}

function productAttributeOptions(
	product: Record< string, unknown >,
	attributeId: number
): string[] {
	const attributes = product.attributes as
		| Array< { id: number; options?: string[] } >
		| undefined;
	const attribute = attributes?.find(
		( currentAttribute ) => currentAttribute.id === attributeId
	);

	return [ ...( attribute?.options || [] ) ].sort();
}

async function expectProductAttributeOptions(
	restApi: RestApi,
	productId: number,
	attributeId: number,
	expectedOptions: string[]
) {
	await expect
		.poll( async () => {
			const product = (
				await restApi.get( `${ WC_API_PATH }/products/${ productId }` )
			 ).data;

			return productAttributeOptions( product, attributeId );
		} )
		.toEqual( [ ...expectedOptions ].sort() );
}

async function goToProductList( page: Page, product: Product ) {
	await page.goto(
		`wp-admin/edit.php?post_type=product&s=${ encodeURIComponent(
			product.name
		) }`
	);
	await expect( page.locator( `#post-${ product.id }` ) ).toBeVisible();
}

async function openQuickEdit( page: Page, product: Product ) {
	const productRow = page.locator( `#post-${ product.id }` );

	await productRow.hover();
	await productRow.getByRole( 'button', { name: 'Quick Edit' } ).click();

	const quickEditRow = page.locator( `#edit-${ product.id }` );
	await expect( quickEditRow ).toBeVisible();
	await expect
		.poll( async () =>
			quickEditRow
				.locator( 'select.wc-product-attribute-values.enhanced' )
				.count()
		)
		.toBeGreaterThanOrEqual( 2 );

	return quickEditRow;
}

function getAttributeField( quickEditRow: Locator, attributeName: string ) {
	return quickEditRow
		.locator( '.product_attributes_fields label' )
		.filter( { hasText: attributeName } )
		.first();
}

function getAddAttributeField( quickEditRow: Locator ) {
	return quickEditRow.locator( '.wc-product-attribute-add-field' );
}

async function expectAttributeChips(
	quickEditRow: Locator,
	attributeName: string,
	expectedTerms: string[]
) {
	const field = getAttributeField( quickEditRow, attributeName );
	const chips = field.locator( '.select2-selection__choice' );

	await expect( field ).toBeVisible();
	await expect( chips ).toHaveCount( expectedTerms.length );

	for ( const termName of expectedTerms ) {
		await expect( chips.filter( { hasText: termName } ) ).toHaveCount( 1 );
	}
}

async function addAttributeField(
	page: Page,
	quickEditRow: Locator,
	attribute: GlobalAttribute
) {
	const addField = getAddAttributeField( quickEditRow );
	const searchField = page.locator(
		'.select2-container--open .select2-search__field'
	);

	await expect( addField ).toBeVisible();
	await addField.locator( '.select2-selection' ).click();
	await expect( searchField ).toBeVisible();
	await searchField.fill( attribute.name );
	await page
		.getByRole( 'option', { name: attribute.name, exact: true } )
		.click();
	await expect(
		getAttributeField( quickEditRow, attribute.name )
	).toBeVisible();
}

async function openAttributeSearch( page: Page, field: Locator ) {
	const searchField = page.locator(
		'.select2-container--open .select2-search__field'
	);

	await field.locator( '.select2-selection' ).click();

	try {
		await expect( searchField ).toBeVisible( { timeout: 1000 } );
	} catch {
		await field.locator( '.select2-selection' ).click();
		await expect( searchField ).toBeVisible();
	}

	return searchField;
}

async function selectAttributeTerms(
	page: Page,
	quickEditRow: Locator,
	attributeName: string,
	termNames: string[]
) {
	const field = getAttributeField( quickEditRow, attributeName );

	for ( const termName of termNames ) {
		const searchField = await openAttributeSearch( page, field );

		await searchField.fill( termName );
		await page
			.getByRole( 'option', { name: termName, exact: true } )
			.click();
	}
}

async function removeAttributeTerm(
	quickEditRow: Locator,
	attributeName: string,
	termName: string
) {
	const field = getAttributeField( quickEditRow, attributeName );
	const chip = field
		.locator( '.select2-selection__choice' )
		.filter( { hasText: termName } );

	await chip.click( { position: { x: 8, y: 12 } } );
}

async function removeAllAttributeTerms(
	quickEditRow: Locator,
	attributeName: string
) {
	const field = getAttributeField( quickEditRow, attributeName );
	const chips = field.locator( '.select2-selection__choice' );
	let remainingChips = await chips.count();

	while ( remainingChips ) {
		await chips.first().click( { position: { x: 8, y: 12 } } );
		await expect( chips ).toHaveCount( remainingChips - 1 );
		remainingChips = await chips.count();
	}
}

async function saveQuickEdit( page: Page, quickEditRow: Locator ) {
	await quickEditRow.locator( 'input[name="post_title"]' ).click();
	await expect( page.locator( '.select2-container--open' ) ).toHaveCount( 0 );

	await Promise.all( [
		page.waitForResponse(
			( response ) =>
				response.url().includes( 'admin-ajax.php' ) &&
				( response.request().postData() || '' ).includes(
					'inline-save'
				)
		),
		quickEditRow.locator( '.button.save' ).click(),
	] );

	await expect( quickEditRow ).toBeHidden();
}

async function cancelQuickEdit( quickEditRow: Locator ) {
	await quickEditRow.locator( '.button.cancel' ).click();
	await expect( quickEditRow ).toBeHidden();
}

async function expectBoundingBox( locator: Locator ): Promise< BoundingBox > {
	const box = await locator.boundingBox();

	if ( ! box ) {
		throw new Error( 'Expected visible bounding box.' );
	}

	return box;
}

function isTaxonomyTermSearchRequest( request: Request, taxonomy: string ) {
	const url = new URL( request.url() );
	const postData = request.postData() || '';
	const postParams = new URLSearchParams( postData );

	return (
		url.pathname.endsWith( 'admin-ajax.php' ) &&
		( url.searchParams.get( 'action' ) ===
			'woocommerce_json_search_taxonomy_terms' ||
			postParams.get( 'action' ) ===
				'woocommerce_json_search_taxonomy_terms' ) &&
		( url.searchParams.get( 'taxonomy' ) === taxonomy ||
			postParams.get( 'taxonomy' ) === taxonomy )
	);
}

function isProductAttributeSearchRequest( request: Request ) {
	const url = new URL( request.url() );
	const postData = request.postData() || '';
	const postParams = new URLSearchParams( postData );

	return (
		url.pathname.endsWith( 'admin-ajax.php' ) &&
		( url.searchParams.get( 'action' ) ===
			'woocommerce_json_search_product_attributes' ||
			postParams.get( 'action' ) ===
				'woocommerce_json_search_product_attributes' )
	);
}

test.describe( 'Product Quick Edit attributes', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { restApi } ) => {
		const suffix = Date.now().toString( 36 );

		sizeAttribute = await createGlobalAttribute(
			restApi,
			`QE Size ${ suffix }`,
			[ 'Small', 'Medium', 'Large' ]
		);
		styleAttribute = await createGlobalAttribute(
			restApi,
			`QE Style ${ suffix }`,
			[ 'Logo', 'Polka dot', 'Regular', 'Pockets', 'Stripped' ]
		);

		emptyProduct = await createProduct( restApi, {
			name: `Quick Edit Attributes Empty ${ suffix }`,
			type: 'simple',
			regular_price: '10',
		} );
		addAttributeProduct = await createProduct( restApi, {
			name: `Quick Edit Attributes Add ${ suffix }`,
			type: 'simple',
			regular_price: '11',
		} );
		singleAttributeProduct = await createProduct( restApi, {
			name: `Quick Edit Attributes Single ${ suffix }`,
			type: 'simple',
			regular_price: '11.50',
			attributes: [
				{
					id: sizeAttribute.id,
					visible: true,
					options: [ 'Medium' ],
				},
			],
		} );
		simpleProduct = await createProduct( restApi, {
			name: `Quick Edit Attributes Simple ${ suffix }`,
			type: 'simple',
			regular_price: '12',
			attributes: [
				{
					id: sizeAttribute.id,
					visible: true,
					options: [ 'Medium' ],
				},
				{
					id: styleAttribute.id,
					visible: true,
					options: [ 'Regular' ],
				},
			],
		} );
		manySelectedProduct = await createProduct( restApi, {
			name: `Quick Edit Attributes Many ${ suffix }`,
			type: 'simple',
			regular_price: '14',
			attributes: [
				{
					id: sizeAttribute.id,
					visible: true,
					options: sizeAttribute.terms.map( ( term ) => term.name ),
				},
				{
					id: styleAttribute.id,
					visible: true,
					options: styleAttribute.terms.map( ( term ) => term.name ),
				},
			],
		} );
		variableProduct = await createProduct( restApi, {
			name: `Quick Edit Attributes Variable ${ suffix }`,
			type: 'variable',
			attributes: [
				{
					id: sizeAttribute.id,
					visible: true,
					variation: true,
					options: sizeAttribute.terms.map( ( term ) => term.name ),
				},
			],
		} );

		for ( const [ index, term ] of sizeAttribute.terms.entries() ) {
			await restApi.post(
				`${ WC_API_PATH }/products/${ variableProduct.id }/variations`,
				{
					regular_price: String( 20 + index ),
					attributes: [
						{
							id: sizeAttribute.id,
							option: term.name,
						},
					],
				}
			);
		}
	} );

	test.afterAll( async ( { restApi } ) => {
		if ( createdProductIds.length ) {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: createdProductIds,
			} );
		}

		if ( createdAttributeIds.length ) {
			await restApi.post( `${ WC_API_PATH }/products/attributes/batch`, {
				delete: createdAttributeIds,
			} );
		}
	} );

	test( 'hydrates no, one, and all selected global attribute states', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 900, height: 900 } );
		await page.route( '**/wp-admin/admin-ajax.php', async ( route ) => {
			if ( isProductAttributeSearchRequest( route.request() ) ) {
				await new Promise( ( resolve ) => setTimeout( resolve, 100 ) );
			}

			await route.continue();
		} );

		await goToProductList( page, emptyProduct );
		let quickEditRow = await openQuickEdit( page, emptyProduct );
		await expect(
			quickEditRow.getByRole( 'heading', { name: 'Attributes' } )
		).toBeVisible();
		await expect(
			getAttributeField( quickEditRow, sizeAttribute.name )
		).toBeHidden();
		await expect(
			getAttributeField( quickEditRow, styleAttribute.name )
		).toBeHidden();
		await expect( getAddAttributeField( quickEditRow ) ).toBeVisible();
		await getAddAttributeField( quickEditRow )
			.locator( '.select2-selection' )
			.click();
		await expect(
			page.locator( '.select2-container--open .loading-results' )
		).toContainText( 'Loading' );
		await expect(
			page.getByRole( 'option', {
				name: sizeAttribute.name,
				exact: true,
			} )
		).toBeVisible();
		await quickEditRow.locator( 'input[name="post_title"]' ).click();
		await expect( page.locator( '.select2-container--open' ) ).toHaveCount(
			0
		);
		await cancelQuickEdit( quickEditRow );

		await goToProductList( page, singleAttributeProduct );
		quickEditRow = await openQuickEdit( page, singleAttributeProduct );
		await expectAttributeChips( quickEditRow, sizeAttribute.name, [
			'Medium',
		] );
		await expect(
			getAttributeField( quickEditRow, styleAttribute.name )
		).toBeHidden();
		await expect( getAddAttributeField( quickEditRow ) ).toBeVisible();
		await cancelQuickEdit( quickEditRow );

		await goToProductList( page, manySelectedProduct );
		quickEditRow = await openQuickEdit( page, manySelectedProduct );
		await expectAttributeChips(
			quickEditRow,
			sizeAttribute.name,
			sizeAttribute.terms.map( ( term ) => term.name )
		);
		await expectAttributeChips(
			quickEditRow,
			styleAttribute.name,
			styleAttribute.terms.map( ( term ) => term.name )
		);
		const allAttributesAddedField = getAddAttributeField( quickEditRow );
		await expect( allAttributesAddedField ).toBeVisible();
		await expect(
			allAttributesAddedField.locator( '.select2-container' )
		).toBeHidden();
		await expect(
			allAttributesAddedField.locator( '.title' )
		).toBeHidden();
		await expect(
			allAttributesAddedField.locator(
				'.wc-product-attribute-add-message'
			)
		).toHaveText( 'All available attributes have been added.' );

		const styleField = getAttributeField(
			quickEditRow,
			styleAttribute.name
		);
		await expect(
			styleField.locator( '.select2-search__field' )
		).toHaveCSS( 'height', '0px' );

		await cancelQuickEdit( quickEditRow );
	} );

	test( 'cancels, changes, clears, and preserves unrelated attributes', async ( {
		page,
		restApi,
	} ) => {
		await goToProductList( page, addAttributeProduct );
		let quickEditRow = await openQuickEdit( page, addAttributeProduct );
		await expect( quickEditRow ).toBeVisible();

		await addAttributeField( page, quickEditRow, sizeAttribute );
		await selectAttributeTerms( page, quickEditRow, sizeAttribute.name, [
			'Medium',
		] );
		await cancelQuickEdit( quickEditRow );
		await expectProductAttributeOptions(
			restApi,
			addAttributeProduct.id,
			sizeAttribute.id,
			[]
		);

		await goToProductList( page, addAttributeProduct );
		quickEditRow = await openQuickEdit( page, addAttributeProduct );
		await addAttributeField( page, quickEditRow, sizeAttribute );
		await selectAttributeTerms( page, quickEditRow, sizeAttribute.name, [
			'Medium',
		] );
		await saveQuickEdit( page, quickEditRow );
		await expectProductAttributeOptions(
			restApi,
			addAttributeProduct.id,
			sizeAttribute.id,
			[ 'Medium' ]
		);

		await goToProductList( page, simpleProduct );
		quickEditRow = await openQuickEdit( page, simpleProduct );

		await removeAttributeTerm( quickEditRow, sizeAttribute.name, 'Medium' );
		await selectAttributeTerms( page, quickEditRow, sizeAttribute.name, [
			'Large',
			'Small',
		] );
		await saveQuickEdit( page, quickEditRow );
		await expectProductAttributeOptions(
			restApi,
			simpleProduct.id,
			sizeAttribute.id,
			[ 'Large', 'Small' ]
		);
		await expectProductAttributeOptions(
			restApi,
			simpleProduct.id,
			styleAttribute.id,
			[ 'Regular' ]
		);

		await goToProductList( page, simpleProduct );
		quickEditRow = await openQuickEdit( page, simpleProduct );
		await removeAllAttributeTerms( quickEditRow, sizeAttribute.name );
		await saveQuickEdit( page, quickEditRow );
		await expectProductAttributeOptions(
			restApi,
			simpleProduct.id,
			sizeAttribute.id,
			[]
		);
		await expectProductAttributeOptions(
			restApi,
			simpleProduct.id,
			styleAttribute.id,
			[ 'Regular' ]
		);
	} );

	test( 'keeps dropdown results spaced and caches term searches per page session', async ( {
		page,
	} ) => {
		let sizeTermSearchRequests = 0;

		await page.route( '**/wp-admin/admin-ajax.php', async ( route ) => {
			if (
				isTaxonomyTermSearchRequest(
					route.request(),
					sizeAttribute.taxonomy
				)
			) {
				await new Promise( ( resolve ) => setTimeout( resolve, 100 ) );
			}

			await route.continue();
		} );

		page.on( 'request', ( request ) => {
			if (
				isTaxonomyTermSearchRequest( request, sizeAttribute.taxonomy )
			) {
				sizeTermSearchRequests++;
			}
		} );

		await goToProductList( page, manySelectedProduct );
		const quickEditRow = await openQuickEdit( page, manySelectedProduct );
		const sizeField = getAttributeField( quickEditRow, sizeAttribute.name );
		const styleField = getAttributeField(
			quickEditRow,
			styleAttribute.name
		);
		const closedSizeSelectionBox = await expectBoundingBox(
			sizeField.locator( '.select2-selection' )
		);

		await sizeField.locator( '.select2-selection' ).click();
		await expect(
			page.locator( '.select2-container--open .loading-results' )
		).toContainText( 'Loading' );
		await expect(
			page.getByRole( 'option', { name: 'Small', exact: true } )
		).toBeVisible();

		const dropdownBox = await expectBoundingBox(
			page.locator( '.select2-container--open .select2-dropdown' )
		);
		const openSizeSelectionBox = await expectBoundingBox(
			sizeField.locator( '.select2-selection' )
		);
		const styleFieldBox = await expectBoundingBox( styleField );

		expect( Math.round( openSizeSelectionBox.width ) ).toBe(
			Math.round( closedSizeSelectionBox.width )
		);
		expect( styleFieldBox.y ).toBeGreaterThanOrEqual(
			dropdownBox.y + dropdownBox.height
		);
		expect( sizeTermSearchRequests ).toBe( 1 );

		await quickEditRow.locator( 'input[name="post_title"]' ).click();
		await expect( page.locator( '.select2-container--open' ) ).toHaveCount(
			0
		);
		const secondRequest = page
			.waitForRequest(
				( request ) =>
					isTaxonomyTermSearchRequest(
						request,
						sizeAttribute.taxonomy
					),
				{ timeout: 500 }
			)
			.then( () => true )
			.catch( () => false );

		await sizeField.locator( '.select2-selection' ).click();
		await expect(
			page.getByRole( 'option', { name: 'Small', exact: true } )
		).toBeVisible();

		await expect( secondRequest ).resolves.toBe( false );
		expect( sizeTermSearchRequests ).toBe( 1 );

		await quickEditRow.locator( 'input[name="post_title"]' ).click();
		await expect( page.locator( '.select2-container--open' ) ).toHaveCount(
			0
		);
		await cancelQuickEdit( quickEditRow );
	} );

	test( 'keeps variable product variations selectable after quick edit save', async ( {
		page,
	} ) => {
		await goToProductList( page, variableProduct );
		const quickEditRow = await openQuickEdit( page, variableProduct );

		await saveQuickEdit( page, quickEditRow );

		await page.goto( `product/${ variableProduct.slug }` );
		await page
			.locator( '.variations_form select' )
			.first()
			.selectOption( { label: 'Medium' } );

		await expect(
			page.locator(
				'.single_variation_wrap .woocommerce-variation-price'
			)
		).toBeVisible();
		await expect(
			page.getByRole( 'button', { name: 'Add to cart', exact: true } )
		).toBeEnabled();
	} );
} );
