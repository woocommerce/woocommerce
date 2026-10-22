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
const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Red (4)',
	'Green (3)',
	'Gray (2)',
	'Yellow (1)',
];

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
			const { colorAttributeId } = await getProductAttributeIds();
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

	test( 'Dropdown updates results on each checkbox selection and supports keyboard dismissal', async ( {
		templateCompiler,
		page,
	} ) => {
		const { colorAttributeId } = await getProductAttributeIds();
		await templateCompiler.compile( {
			dropdown: true,
			attributes: {
				attributeId: colorAttributeId,
				displayStyle: 'woocommerce/dropdown',
				showCounts: true,
				sortOrder: 'name-asc',
			},
		} );
		await page.goto( '/shop' );
		const trigger = page.getByRole( 'button', {
			name: 'Color: Choose options',
		} );
		const productTitles = page.locator(
			'.wp-block-woocommerce-product-template .wp-block-post-title'
		);
		await trigger.focus();
		await page.keyboard.press( 'Enter' );
		await page.keyboard.press( 'Tab' );
		const blue = page.getByRole( 'checkbox', { name: 'Blue (4)' } );
		await expect( blue ).toBeFocused();
		await page.keyboard.press( 'Space' );
		await expect( productTitles ).toHaveText( [
			'Hoodie',
			'Hoodie with Logo',
			'Polo',
			'V-Neck T-Shirt',
		] );
		await expect( blue ).toBeFocused();
		await page.getByRole( 'checkbox', { name: 'Gray (2)' } ).check();
		await expect( productTitles ).toHaveText( [
			'Hoodie',
			'Hoodie with Logo',
			'Polo',
			...GRAY_PRODUCT_TITLES,
			'V-Neck T-Shirt',
		] );
		const selectedTrigger = page.getByRole( 'button', {
			name: 'Color: Blue, Gray',
			exact: true,
		} );
		await expect( selectedTrigger ).toHaveAttribute(
			'aria-expanded',
			'true'
		);
		await page.keyboard.press( 'Escape' );
		await expect( selectedTrigger ).toBeFocused();
		await expect( selectedTrigger ).toHaveAttribute(
			'aria-expanded',
			'false'
		);
		await page.waitForURL(
			( url ) =>
				url.searchParams.get( 'filter_color' )?.includes( 'gray' ) ??
				false
		);
		await page.reload();
		await expect( selectedTrigger ).toBeVisible();
		await selectedTrigger.click();
		await expect( blue ).toBeChecked();
		await expect(
			page.getByRole( 'checkbox', { name: 'Gray (2)' } )
		).toBeChecked();
		await page.getByRole( 'checkbox', { name: 'Gray (2)' } ).uncheck();
		await expect( productTitles ).toHaveText( [
			'Hoodie',
			'Hoodie with Logo',
			'Polo',
			'V-Neck T-Shirt',
		] );
	} );

	test( 'Escape dismisses the Dropdown before the filter overlay', async ( {
		templateCompiler,
		page,
	} ) => {
		const { colorAttributeId } = await getProductAttributeIds();
		await templateCompiler.compile( {
			dropdown: true,
			attributes: {
				attributeId: colorAttributeId,
				displayStyle: 'woocommerce/dropdown',
			},
		} );
		await page.setViewportSize( { width: 390, height: 844 } );
		await page.goto( '/shop' );
		await page.getByRole( 'button', { name: 'Filter products' } ).click();
		const overlay = page.getByRole( 'dialog', { name: 'Product Filters' } );
		const trigger = overlay.getByRole( 'button', {
			name: 'Color: Choose options',
		} );
		await trigger.click();
		await page.keyboard.press( 'Tab' );
		await expect(
			overlay.getByRole( 'checkbox', { name: 'Blue', exact: true } )
		).toBeFocused();
		await page.keyboard.down( 'Escape' );
		await page.keyboard.up( 'Escape' );
		await expect( trigger ).toHaveAttribute( 'aria-expanded', 'false' );
		await page.evaluate(
			() =>
				new Promise( ( resolve ) =>
					requestAnimationFrame( () =>
						requestAnimationFrame( resolve )
					)
				)
		);
		await expect(
			page.locator( '.wp-block-woocommerce-product-filters' )
		).toHaveClass( /\bis-overlay-opened\b/ );
		await expect( overlay ).toBeVisible();
		await expect( trigger ).toBeFocused();
		await page.keyboard.press( 'Escape' );
		await expect( overlay ).toHaveCount( 0 );
	} );

	test( 'Dropdown summary retains active selections hidden by another filter', async ( {
		templateCompiler,
		page,
		browser,
	} ) => {
		const { colorAttributeId } = await getProductAttributeIds();
		await templateCompiler.compile( {
			dropdown: true,
			taxonomy: true,
			attributes: {
				attributeId: colorAttributeId,
				displayStyle: 'woocommerce/dropdown',
				hideEmpty: true,
			},
		} );
		await page.goto( '/shop' );
		const color = page.locator(
			'.wp-block-woocommerce-product-filter-attribute'
		);
		const trigger = color.getByRole( 'button' );
		await trigger.click();
		await color
			.getByRole( 'checkbox', { name: 'Blue', exact: true } )
			.check();
		await color
			.getByRole( 'checkbox', { name: 'Yellow', exact: true } )
			.check();
		await page.keyboard.press( 'Escape' );
		const category = page.locator(
			'.wp-block-woocommerce-product-filter-taxonomy'
		);
		await category.getByRole( 'button' ).click();
		await category
			.getByRole( 'checkbox', { name: 'Hoodies', exact: true } )
			.check();
		await expect(
			page.locator(
				'.wp-block-woocommerce-product-template .wp-block-post-title'
			)
		).toHaveText( [ 'Hoodie', 'Hoodie with Logo' ] );
		await expect( trigger ).toHaveText( 'Color: Blue, Yellow' );
		await page.reload();
		await expect( trigger ).toHaveText( 'Color: Blue, Yellow' );
		await trigger.click();
		await expect(
			color.getByRole( 'checkbox', { name: 'Yellow', exact: true } )
		).toHaveCount( 0 );
		await color
			.getByRole( 'checkbox', { name: 'Green', exact: true } )
			.check();
		await expect( trigger ).toHaveText( 'Color: Blue, Green +1' );
		await page.waitForURL(
			( url ) =>
				url.searchParams.get( 'filter_color' )?.includes( 'green' ) ??
				false
		);
		const noScript = await browser.newContext( {
			javaScriptEnabled: false,
		} );
		try {
			const serverPage = await noScript.newPage();
			await serverPage.goto( page.url() );
			await expect(
				serverPage.getByRole( 'button', {
					name: 'Color: Blue, Green +1',
					exact: true,
				} )
			).toBeVisible();
		} finally {
			await noScript.close();
		}
	} );

	test( 'Dropdown defaults allow theme CSS overrides in either load order', async ( {
		templateCompiler,
		page,
	} ) => {
		const { colorAttributeId } = await getProductAttributeIds();
		const attributes = {
			attributeId: colorAttributeId,
			displayStyle: 'woocommerce/dropdown',
		};
		await templateCompiler.compile( { dropdown: true, attributes } );
		await page.goto( '/shop' );
		const trigger = page.getByRole( 'button', {
			name: 'Color: Choose options',
		} );
		const panel = page.locator( '.wc-block-dropdown__panel' );
		const themeStyle = await page.addStyleTag( {
			content:
				'.wc-block-dropdown__trigger, .wc-block-dropdown__panel { border: 3px dashed purple; border-radius: 10px; }',
		} );
		await expect( trigger ).toHaveCSS( 'border-top-width', '3px' );
		await expect( trigger ).toHaveCSS( 'border-top-style', 'dashed' );
		await expect( trigger ).toHaveCSS( 'border-top-left-radius', '10px' );
		await trigger.click();
		await expect( trigger ).toHaveCSS( 'border-bottom-left-radius', '0px' );
		await expect( panel ).toHaveCSS( 'border-bottom-left-radius', '10px' );
		await expect( panel ).toHaveCSS( 'border-top-width', '0px' );
		await page.keyboard.press( 'Escape' );
		await expect( trigger ).toHaveCSS(
			'border-bottom-left-radius',
			'10px'
		);
		await themeStyle.evaluate( ( style ) =>
			document.head.prepend( style )
		);
		await expect( trigger ).toHaveCSS( 'border-top-width', '3px' );
		await expect( trigger ).toHaveCSS( 'border-top-left-radius', '10px' );
	} );

	test( 'Dropdown Core colors and borders override theme CSS and preserve separate corners', async ( {
		templateCompiler,
		page,
	} ) => {
		const { colorAttributeId } = await getProductAttributeIds();
		await templateCompiler.compile( {
			dropdown: true,
			attributes: {
				attributeId: colorAttributeId,
				displayStyle: 'woocommerce/dropdown',
			},
			dropdownAttributes: {
				style: {
					color: { text: '#f8fafc', background: '#172554' },
					border: {
						color: '#15803d',
						width: '0px',
						style: 'solid',
						radius: {
							topLeft: '3px',
							topRight: '7px',
							bottomRight: '11px',
							bottomLeft: '17px',
						},
					},
				},
			},
		} );
		await page.goto( '/shop' );
		const trigger = page.getByRole( 'button', {
			name: /^Color:/,
		} );
		const panel = page.locator( '.wc-block-dropdown__panel' );
		await page.addStyleTag( {
			content:
				'.wc-block-dropdown__trigger, .wc-block-dropdown__panel { border: 3px dashed purple; border-radius: 10px; }',
		} );
		await expect( trigger ).toHaveCSS( 'border-top-width', '0px' );
		await expect( trigger ).toHaveCSS(
			'border-top-color',
			'rgb(21, 128, 61)'
		);
		await expect( trigger ).toHaveCSS( 'color', 'rgb(248, 250, 252)' );
		await expect( trigger ).toHaveCSS(
			'background-color',
			'rgb(23, 37, 84)'
		);
		await expect( trigger ).toHaveCSS( 'border-top-left-radius', '3px' );
		await expect( trigger ).toHaveCSS( 'border-top-right-radius', '7px' );
		await expect( trigger ).toHaveCSS(
			'border-bottom-right-radius',
			'11px'
		);
		await expect( trigger ).toHaveCSS(
			'border-bottom-left-radius',
			'17px'
		);
		await trigger.click();
		const listStyle = await page.addStyleTag( {
			path: 'assets/client/blocks/woocommerce/product-filter-checkbox-list-style.css',
		} );
		const blue = panel.getByRole( 'checkbox', {
			name: 'Blue',
			exact: true,
		} );
		await expect( blue ).toHaveCSS(
			'border-top-color',
			'rgb(248, 250, 252)'
		);
		await listStyle.evaluate( ( style ) => document.head.prepend( style ) );
		await expect( blue ).toHaveCSS(
			'border-top-color',
			'rgb(248, 250, 252)'
		);
		await expect( panel ).toHaveCSS( 'color', 'rgb(248, 250, 252)' );
		await expect( panel ).toHaveCSS(
			'background-color',
			'rgb(23, 37, 84)'
		);
		await expect(
			panel.getByRole( 'checkbox', { name: 'Blue', exact: true } )
		).toHaveCSS( 'border-top-color', 'rgb(248, 250, 252)' );
		await page
			.getByRole( 'checkbox', { name: 'Blue', exact: true } )
			.check();
		await expect( trigger ).toHaveText( 'Color: Blue' );
		await expect( trigger ).toHaveCSS(
			'background-color',
			'rgb(23, 37, 84)'
		);
		await expect( panel ).toHaveCSS( 'border-bottom-left-radius', '17px' );
		await expect( panel ).toHaveCSS( 'border-bottom-right-radius', '11px' );
		await page.keyboard.press( 'Escape' );
		await expect( trigger ).toHaveCSS(
			'border-bottom-left-radius',
			'17px'
		);
	} );

	test.describe( 'With show counts enabled', () => {
		test.beforeEach( async ( { templateCompiler } ) => {
			const { colorAttributeId } = await getProductAttributeIds();
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
