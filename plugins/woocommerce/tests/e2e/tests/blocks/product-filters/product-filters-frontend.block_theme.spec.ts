/**
 * External dependencies
 */
import {
	TemplateCompiler,
	getProductAttributeIds,
	test as base,
	expect,
} from '@woocommerce/e2e-utils';

const test = base.extend< {
	templateCompiler: TemplateCompiler;
	overlayMode: 'off' | 'mobile' | 'always';
} >( {
	overlayMode: [ 'mobile', { option: true } ],
	templateCompiler: async ( { requestUtils }, provide ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await provide( compiler );
	},
} );

test.describe( 'woocommerce/product-filters - Frontend', () => {
	test.describe( 'Overlay', () => {
		test.use( {
			viewport: { width: 400, height: 600 },
			reducedMotion: 'no-preference',
		} );

		test.beforeEach( async ( { templateCompiler, page, overlayMode } ) => {
			const { colorAttributeId } = await getProductAttributeIds();
			await templateCompiler.compile( {
				attributes: {
					attributeId: colorAttributeId,
				},
				productFiltersAttributes: { overlayMode },
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

		test( 'keeps the closed overlay out of the accessibility tree and tab order', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const productFilters = page.locator(
				'.wp-block-woocommerce-product-filters'
			);
			const openButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );

			await expect( openButton ).toBeVisible();
			await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
			await expect( productFilters.getByRole( 'checkbox' ) ).toHaveCount(
				0
			);
			await openButton.focus();
			await page.keyboard.press( 'Tab' );
			await expect( productFilters.locator( ':focus' ) ).toHaveCount( 0 );
		} );

		test( 'focuses the close button on opening and contains keyboard focus', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const openButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );
			const overlay = page.getByRole( 'dialog', {
				name: 'Product Filters',
			} );
			const closeButton = overlay.getByRole( 'button', {
				name: 'Close',
			} );
			const applyButton = overlay.getByRole( 'button', {
				name: 'Apply',
			} );

			await openButton.focus();
			await page.keyboard.press( 'Enter' );

			await expect( overlay ).toBeInViewport();
			await expect( closeButton ).toBeFocused();
			await expect( overlay ).toHaveAttribute( 'aria-modal', 'true' );
			await page.keyboard.press( 'Shift+Tab' );
			await expect( applyButton ).toBeFocused();
			await page.keyboard.press( 'Tab' );
			await expect( closeButton ).toBeFocused();
			await page.keyboard.press( 'Tab' );
			await expect(
				overlay.getByRole( 'checkbox' ).first()
			).toBeFocused();
		} );

		test( 'returns focus to the opener after every dismissal action', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 700, height: 600 } );
			await page.goto( '/shop' );

			const openButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );
			const overlay = page.getByRole( 'dialog', {
				name: 'Product Filters',
			} );
			const dismissActions = {
				Close: () =>
					overlay.getByRole( 'button', { name: 'Close' } ).click(),
				Apply: () =>
					overlay.getByRole( 'button', { name: 'Apply' } ).click(),
				Escape: () => page.keyboard.press( 'Escape' ),
				Backdrop: () =>
					page
						.locator( '.wc-block-product-filters__overlay-wrapper' )
						.click( { position: { x: 680, y: 100 } } ),
			};

			for ( const [ action, dismiss ] of Object.entries(
				dismissActions
			) ) {
				await test.step( action, async () => {
					await openButton.click();
					await expect( overlay ).toBeInViewport();
					await dismiss();
					await expect( overlay ).toHaveCount( 0 );
					await expect(
						page.locator( '.wc-block-product-filters__overlay' )
					).toBeHidden();
					await expect( openButton ).toBeFocused();
					await expect( page.locator( 'body' ) ).not.toHaveCSS(
						'overflow',
						'hidden'
					);
				} );
			}
		} );

		test( 'preserves modal focus while the client router updates filtered products', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );
			const timeOrigin = await page.evaluate(
				() => performance.timeOrigin
			);
			const openButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );
			await openButton.click();

			const overlay = page.getByRole( 'dialog', {
				name: 'Product Filters',
			} );
			const grayCheckbox = overlay.getByRole( 'checkbox', {
				name: 'Gray',
			} );
			await grayCheckbox.focus();
			await page.keyboard.press( 'Space' );
			await expect( page ).toHaveURL( /filter_color=gray/ );
			await expect( page.locator( '.wc-block-product' ) ).toHaveCount(
				2
			);
			await expect( grayCheckbox ).toBeChecked();
			await expect( overlay ).toHaveAttribute( 'aria-modal', 'true' );
			await expect( grayCheckbox ).toBeFocused();
			expect( await page.evaluate( () => performance.timeOrigin ) ).toBe(
				timeOrigin
			);

			const clearButton = overlay.getByRole( 'button', {
				name: 'Clear filters',
			} );
			await clearButton.focus();
			await page.keyboard.press( 'Enter' );
			await expect( page ).not.toHaveURL( /filter_color/ );
			await expect( clearButton ).toBeHidden();
			await expect( grayCheckbox ).not.toBeChecked();
			await expect(
				overlay.getByRole( 'button', { name: 'Close' } )
			).toBeFocused();
			await expect( page.locator( 'body' ) ).toHaveCSS(
				'overflow',
				'hidden'
			);

			await page.keyboard.press( 'Escape' );
			await expect( overlay ).toHaveCount( 0 );
			await expect( openButton ).toBeFocused();
		} );

		test( 'skips non-tabbable controls when wrapping keyboard focus', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByRole( 'button', { name: 'Filter products' } )
				.click();
			const overlay = page.getByRole( 'dialog', {
				name: 'Product Filters',
			} );
			await overlay.evaluate( ( dialog ) => {
				for ( const position of [
					'afterbegin',
					'beforeend',
				] as const ) {
					dialog.insertAdjacentHTML(
						position,
						`<div hidden><button>Hidden control</button></div>
						<div style="visibility: hidden"><button>Invisible control</button></div>
						<div inert><button>Inert control</button></div>
						<fieldset disabled><button>Disabled control</button></fieldset>
						<button tabindex="-2">Negative tabindex control</button>`
					);
				}
			} );

			const closeButton = overlay.getByRole( 'button', {
				name: 'Close',
				exact: true,
			} );
			const applyButton = overlay.getByRole( 'button', {
				name: 'Apply',
				exact: true,
			} );
			await closeButton.focus();
			await page.keyboard.press( 'Shift+Tab' );
			await expect( applyButton ).toBeFocused();
			await page.keyboard.press( 'Tab' );
			await expect( closeButton ).toBeFocused();
		} );

		test( 'respects reduced motion while preserving opening and dismissal focus', async ( {
			page,
		} ) => {
			await page.emulateMedia( { reducedMotion: 'reduce' } );
			await page.goto( '/shop' );
			const openButton = page.getByRole( 'button', {
				name: 'Filter products',
			} );
			const overlay = page.locator(
				'.wc-block-product-filters__overlay'
			);
			const dialog = page.getByRole( 'dialog', {
				name: 'Product Filters',
			} );
			await openButton.click();
			await expect( overlay ).toHaveCSS( 'transition-duration', '0s' );
			await expect( dialog ).toHaveCSS( 'transition-duration', '0s' );
			await expect(
				dialog.getByRole( 'button', { name: 'Close' } )
			).toBeFocused();
			await page.keyboard.press( 'Escape' );
			await expect( overlay ).toBeHidden();
			await expect( openButton ).toBeFocused();
		} );

		for ( const reducedMotion of [ 'no-preference', 'reduce' ] as const ) {
			test( `follows the theme responsive presentation with ${ reducedMotion } motion and closes when returning to mobile`, async ( {
				page,
			} ) => {
				await page.emulateMedia( { reducedMotion } );
				await page.goto( '/shop' );
				// Themes can choose a breakpoint other than the default 782px.
				await page.addStyleTag( {
					content: `@media (min-width: 600px) {
					.wc-block-product-filters .wc-block-product-filters__overlay {
						position: relative !important;
						visibility: visible !important;
						pointer-events: auto !important;
					}
					.wc-block-product-filters .wc-block-product-filters__overlay-dialog {
						position: relative !important;
						transform: none !important;
					}
					.wc-block-product-filters .wc-block-product-filters__open-overlay,
					.wc-block-product-filters .wc-block-product-filters__overlay-header,
					.wc-block-product-filters .wc-block-product-filters__overlay-footer {
						display: none !important;
					}
				}`,
				} );
				const openButton = page.getByRole( 'button', {
					name: 'Filter products',
				} );
				await openButton.click();
				await expect(
					page.getByRole( 'button', { name: 'Close' } )
				).toBeFocused();

				await page.setViewportSize( { width: 700, height: 900 } );
				const firstCheckbox = page.getByRole( 'checkbox' ).first();
				await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
				await expect( openButton ).toBeHidden();
				await expect( firstCheckbox ).toBeFocused();
				await expect( page.locator( 'body' ) ).not.toHaveCSS(
					'overflow',
					'hidden'
				);

				await page.setViewportSize( { width: 400, height: 600 } );
				await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
				await expect( page.getByRole( 'checkbox' ) ).toHaveCount( 0 );
				await expect( openButton ).toBeFocused();
				await page.keyboard.press( 'Enter' );
				await expect( page.getByRole( 'dialog' ) ).toBeInViewport();

				const grayCheckbox = page.getByRole( 'checkbox', {
					name: 'Gray',
				} );
				await grayCheckbox.focus();
				await page.setViewportSize( { width: 700, height: 900 } );
				await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
				await expect( grayCheckbox ).toBeFocused();
			} );
		}

		test( 'shows inline filters without a dialog or focus trap on desktop', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 1200, height: 900 } );
			await page.goto( '/shop' );
			const productFilters = page.locator(
				'.wp-block-woocommerce-product-filters'
			);

			await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
			await expect(
				productFilters.locator( '[aria-modal="true"]' )
			).toHaveCount( 0 );
			await expect(
				page.getByRole( 'checkbox', { name: 'Gray' } )
			).toBeVisible();
			await productFilters.getByRole( 'checkbox' ).last().focus();
			await page.keyboard.press( 'Tab' );
			await expect( productFilters.locator( ':focus' ) ).toHaveCount( 0 );
		} );

		test.describe( 'Before hydration', () => {
			test.use( { javaScriptEnabled: false } );

			test( 'hides closed filters before client scripts run', async ( {
				page,
			} ) => {
				await page.goto( '/shop' );
				await expect(
					page
						.locator(
							'.wp-block-woocommerce-product-filters input[type="checkbox"]'
						)
						.first()
				).toBeAttached();
				await expect(
					page.getByRole( 'button', { name: 'Filter products' } )
				).toBeVisible();
				await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
				await expect( page.getByRole( 'checkbox' ) ).toHaveCount( 0 );
				await page.setViewportSize( { width: 1200, height: 900 } );
				await expect(
					page.getByRole( 'checkbox', { name: 'Gray' } )
				).toBeVisible();
				await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
			} );
		} );

		test.describe( 'Overlay disabled', () => {
			test.use( { overlayMode: 'off' } );

			for ( const width of [ 400, 1200 ] ) {
				test( `keeps inline filters accessible at ${ width }px`, async ( {
					page,
				} ) => {
					await page.setViewportSize( { width, height: 900 } );
					await page.goto( '/shop' );
					const productFilters = page.locator(
						'.wp-block-woocommerce-product-filters'
					);

					await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
					await expect(
						productFilters.locator( '[aria-modal="true"]' )
					).toHaveCount( 0 );
					await expect(
						page.getByRole( 'button', { name: 'Filter products' } )
					).toHaveCount( 0 );
					await expect(
						page.getByRole( 'checkbox', { name: 'Gray' } )
					).toBeVisible();
					await productFilters.getByRole( 'checkbox' ).last().focus();
					await page.keyboard.press( 'Tab' );
					await expect(
						productFilters.locator( ':focus' )
					).toHaveCount( 0 );
				} );
			}
		} );

		test.describe( 'Overlay always enabled', () => {
			test.use( { overlayMode: 'always' } );

			test( 'stays modal on desktop and across viewport changes', async ( {
				page,
			} ) => {
				await page.setViewportSize( { width: 1200, height: 900 } );
				await page.goto( '/shop' );
				const openButton = page.getByRole( 'button', {
					name: 'Filter products',
				} );
				const overlay = page.getByRole( 'dialog', {
					name: 'Product Filters',
				} );
				await expect( overlay ).toHaveCount( 0 );
				await openButton.click();
				await expect(
					overlay.getByRole( 'button', { name: 'Close' } )
				).toBeFocused();

				for ( const width of [ 400, 1200 ] ) {
					await page.setViewportSize( { width, height: 900 } );
					await expect( overlay ).toHaveAttribute(
						'aria-modal',
						'true'
					);
					await expect( overlay ).toBeInViewport();
					await page.keyboard.press( 'Shift+Tab' );
					await expect(
						overlay.getByRole( 'button', { name: 'Apply' } )
					).toBeFocused();
					await page.keyboard.press( 'Tab' );
					await expect(
						overlay.getByRole( 'button', { name: 'Close' } )
					).toBeFocused();
				}

				await page.keyboard.press( 'Escape' );
				await expect( overlay ).toHaveCount( 0 );
				await expect( openButton ).toBeFocused();
			} );
		} );
	} );

	test.describe( 'Multiple instances', () => {
		test.beforeEach( async ( { page, requestUtils } ) => {
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

			const templateCompiler = await requestUtils.createTemplateFromFile(
				'archive-product_multiple-product-filters'
			);
			const { colorAttributeId } = await getProductAttributeIds();
			await templateCompiler.compile( {
				attributes: {
					attributeId: colorAttributeId,
				},
			} );
		} );

		test( 'syncs active filters between Product Filters blocks', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const productFilters = page.locator(
				'.wp-block-woocommerce-product-filters'
			);
			await expect( productFilters ).toHaveCount( 2 );

			const activeFiltersBlock = productFilters.first();
			const filterOptionsBlock = productFilters.nth( 1 );
			const grayCheckbox = filterOptionsBlock.getByRole( 'checkbox', {
				name: 'Gray',
			} );
			const grayActiveFilter = activeFiltersBlock.getByText(
				'Color: Gray',
				{ exact: true }
			);

			await expect( grayActiveFilter ).toBeHidden();

			await grayCheckbox.click();
			await page.waitForURL(
				( url ) => url.searchParams.get( 'filter_color' ) === 'gray'
			);

			await expect( grayActiveFilter ).toBeVisible();
			await expect( grayCheckbox ).toBeChecked();

			await activeFiltersBlock
				.getByRole( 'button', {
					name: 'Remove filter: Color: Gray',
				} )
				.click();
			await page.waitForURL(
				( url ) => ! url.searchParams.has( 'filter_color' )
			);

			await expect( grayActiveFilter ).toBeHidden();
			await expect( grayCheckbox ).not.toBeChecked();
		} );

		test( 'returns focus to the opener of the dismissed instance', async ( {
			page,
		} ) => {
			await page.setViewportSize( { width: 400, height: 600 } );
			await page.goto( '/shop' );
			const productFilters = page.locator(
				'.wp-block-woocommerce-product-filters'
			);
			await expect( productFilters ).toHaveCount( 2 );

			const filterOptionsBlock = productFilters.nth( 1 );
			const secondOpenButton = filterOptionsBlock.getByRole( 'button', {
				name: 'Filter products',
			} );
			await secondOpenButton.click();
			await expect(
				filterOptionsBlock.getByRole( 'button', { name: 'Close' } )
			).toBeFocused();
			const grayCheckbox = filterOptionsBlock.getByRole( 'checkbox', {
				name: 'Gray',
			} );
			await grayCheckbox.focus();
			await page.keyboard.press( 'Space' );
			await expect( page ).toHaveURL( /filter_color=gray/ );
			await expect( page.locator( '.wc-block-product' ) ).toHaveCount(
				2
			);
			await expect( grayCheckbox ).toBeFocused();
			await expect( page.locator( 'body' ) ).toHaveCSS(
				'overflow',
				'hidden'
			);
			await page.keyboard.press( 'Escape' );
			await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
			await expect(
				filterOptionsBlock.locator(
					'.wc-block-product-filters__overlay'
				)
			).toBeHidden();
			await expect( secondOpenButton ).toBeFocused();

			const activeFiltersBlock = productFilters.first();
			const firstOpenButton = activeFiltersBlock.getByRole( 'button', {
				name: 'Filter products',
			} );
			await firstOpenButton.click();
			await expect(
				activeFiltersBlock.getByRole( 'button', { name: 'Close' } )
			).toBeFocused();
			await expect(
				activeFiltersBlock.getByText( 'Color: Gray', { exact: true } )
			).toBeVisible();
			const removeFilterButton = activeFiltersBlock.getByRole( 'button', {
				name: 'Remove filter: Color: Gray',
			} );
			await removeFilterButton.focus();
			await page.keyboard.press( 'Enter' );
			await expect( page ).not.toHaveURL( /filter_color/ );
			await expect( removeFilterButton ).toBeHidden();
			await expect(
				activeFiltersBlock.getByRole( 'button', { name: 'Close' } )
			).toBeFocused();
			await page.keyboard.press( 'Escape' );
			await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
			await expect(
				activeFiltersBlock.locator(
					'.wc-block-product-filters__overlay'
				)
			).toBeHidden();
			await expect( firstOpenButton ).toBeFocused();
		} );
	} );
} );
