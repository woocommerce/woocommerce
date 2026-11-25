/**
 * External dependencies
 */
import {
	TemplateCompiler,
	test as base,
	expect,
	getProductAttributeIds,
} from '@woocommerce/e2e-utils';

const GRAY_PRODUCT_TITLES = [ 'T-Shirt', 'T-Shirt with Logo' ];
// Default `count-desc` order. Only serial runs keep the term counts stable.
const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Red (4)',
	'Green (3)',
	'Gray (2)',
	'Yellow (1)',
];

// Filters outside an inherit Product Collection use the main query and plain params.
const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await use( compiler );
	},
} );

test( 'Product Filters filter the shop page and clear the filters', async ( {
	templateCompiler,
	page,
} ) => {
	const { colorAttributeId } = await getProductAttributeIds();
	await templateCompiler.compile( {
		attributes: { attributeId: colorAttributeId, showCounts: true },
	} );

	await page.goto( '/shop' );

	const grayCheckbox = page.getByRole( 'checkbox', { name: 'Gray' } );
	const clearButton = page.getByRole( 'button', { name: 'Clear filters' } );
	const attributeLabels = page
		.locator( '.wp-block-woocommerce-product-filter-attribute' )
		.locator( 'label' );
	const productTitles = page.locator(
		'.wp-block-woocommerce-product-template .wp-block-post-title'
	);
	const expectFilterParams = ( expected: string | null ) =>
		expect
			.poll( () => {
				const params = new URL( page.url() ).searchParams;
				return [
					params.get( 'filter_color' ),
					params.get( 'query_type_color' ),
				];
			} )
			.toEqual( [ expected, expected ? 'or' : null ] );

	await expect( attributeLabels ).toHaveText( COLOR_ATTRIBUTES_WITH_COUNTS );
	const initialProductTitles = await productTitles.allInnerTexts();

	await grayCheckbox.click();
	await expectFilterParams( 'gray' );
	await expect( productTitles ).toHaveText( GRAY_PRODUCT_TITLES );
	// The counts of an OR filter leave out its own selection.
	await expect( attributeLabels ).toHaveText( COLOR_ATTRIBUTES_WITH_COUNTS );

	await clearButton.click();
	await expectFilterParams( null );
	await expect( productTitles ).toHaveText( initialProductTitles );
	await expect( grayCheckbox ).not.toBeChecked();
} );
