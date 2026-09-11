/**
 * External dependencies
 */
import {
	TemplateCompiler,
	test as base,
	expect,
	wpCLI,
} from '@woocommerce/e2e-utils';

const GRAY_PRODUCT_TITLES = [ 'T-Shirt', 'T-Shirt with Logo' ];
const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Red (4)',
	'Green (3)',
	'Gray (2)',
	'Yellow (1)',
];

const getColorAttributeId = async () => {
	const { stdout } = await wpCLI(
		'wc product_attribute list --format=json --user=1'
	);
	const firstBracket = stdout.indexOf( '[' );
	const lastBracket = stdout.lastIndexOf( ']' );

	if ( firstBracket < 0 || lastBracket <= firstBracket ) {
		throw new Error( 'Product attribute CLI output did not contain JSON.' );
	}

	const attributes = JSON.parse(
		stdout.slice( firstBracket, lastBracket + 1 )
	) as Array< { id: number | string; name: string; slug: string } >;
	const colorAttributes = attributes.filter(
		( attribute ) =>
			attribute.name === 'Color' && attribute.slug === 'pa_color'
	);

	expect( colorAttributes ).toHaveLength( 1 );
	const attributeId = Number( colorAttributes[ 0 ].id );
	expect( Number.isSafeInteger( attributeId ) && attributeId > 0 ).toBe(
		true
	);

	return attributeId;
};

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filter-attribute - Frontend', () => {
	test.describe( 'With default display style', () => {
		test.beforeEach( async ( { templateCompiler, page } ) => {
			const colorAttributeId = await getColorAttributeId();
			await templateCompiler.compile( {
				attributes: {
					attributeId: colorAttributeId,
				},
			} );

			await page.addInitScript( () => {
				// Mock the wc global variable.
				if ( typeof window.wc === 'undefined' ) {
					window.wc = {
						wcSettings: {
							getSetting() {
								return true;
							},
						},
					};
				}
			} );
		} );

		test( 'selects and clears an attribute while keeping URL, products, and controls in sync', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const grayCheckbox = page.getByRole( 'checkbox', { name: 'Gray' } );
			const clearButton = page.getByRole( 'button', {
				name: 'Clear filters',
			} );
			const clearButtonIncludingHidden = page.getByRole( 'button', {
				name: 'Clear filters',
				includeHidden: true,
			} );
			const productTitles = page.locator(
				'.wp-block-woocommerce-product-template .wp-block-post-title'
			);
			const expectFilterParams = async (
				expectedValue: string | null
			) => {
				await expect
					.poll( () => {
						const params = new URL( page.url() ).searchParams;
						return {
							filterColor: params.get( 'filter_color' ),
							queryTypeColor: params.get( 'query_type_color' ),
						};
					} )
					.toEqual( {
						filterColor: expectedValue,
						queryTypeColor: expectedValue ? 'or' : null,
					} );
			};
			await expect( productTitles.first() ).toBeVisible();
			const initialProductTitles = (
				await productTitles.allTextContents()
			).map( ( title ) => title.trim() );
			expect( initialProductTitles ).not.toHaveLength( 0 );

			await expect( clearButton ).toBeHidden();
			await expect( grayCheckbox ).not.toBeChecked();

			await grayCheckbox.click();
			await expectFilterParams( 'gray' );
			await expect( productTitles ).toHaveText( GRAY_PRODUCT_TITLES );
			await expect( grayCheckbox ).toBeChecked();
			await expect( clearButton ).toBeVisible();

			await grayCheckbox.click();
			await expectFilterParams( null );
			await expect( productTitles ).toHaveText( initialProductTitles );
			await expect( grayCheckbox ).not.toBeChecked();
			await expect( clearButtonIncludingHidden ).toHaveCount( 0 );

			await grayCheckbox.click();
			await expectFilterParams( 'gray' );
			await expect( productTitles ).toHaveText( GRAY_PRODUCT_TITLES );
			await expect( clearButton ).toBeVisible();
			await clearButton.click();
			await expectFilterParams( null );
			await expect( productTitles ).toHaveText( initialProductTitles );
			await expect( grayCheckbox ).not.toBeChecked();
			await expect( clearButtonIncludingHidden ).toHaveCount( 0 );
		} );
	} );

	test.describe( 'With show counts enabled', () => {
		test.beforeEach( async ( { templateCompiler } ) => {
			const colorAttributeId = await getColorAttributeId();
			await templateCompiler.compile( {
				attributes: {
					attributeId: colorAttributeId,
					showCounts: true,
				},
			} );
		} );

		test( 'renders current product counts for every attribute', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const listItems = page
				.getByRole( 'heading', {
					name: 'Attribute',
				} )
				.locator( '..' )
				.locator( '..' )
				.locator( 'label' );

			await expect( listItems ).toHaveText(
				COLOR_ATTRIBUTES_WITH_COUNTS
			);
		} );
	} );
} );
