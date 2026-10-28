/**
 * External dependencies
 */
import {
	test as base,
	expect,
	BLOCK_THEME_SLUG,
	getProductAttributeIds,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductFiltersPage } from './product-filters.page';

const blockData = {
	name: 'woocommerce/product-filter-attribute',
	selectors: {
		frontend: {},
		editor: {
			settings: {},
		},
	},
	slug: 'archive-product',
};

const getExpectedStyleControls = ( wpCoreVersion: number ) => {
	if ( wpCoreVersion >= 7.1 ) {
		return [
			'Typography',
			'Dimensions',
			wpCoreVersion >= 7.2 ? 'Borders' : 'Border',
		];
	}
	return [ 'Color', 'Typography', 'Dimensions' ];
};

const test = base.extend< { pageObject: ProductFiltersPage } >( {
	pageObject: async ( { page, editor, frontendUtils }, use ) => {
		const pageObject = new ProductFiltersPage( {
			page,
			editor,
			frontendUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
	} );

	test( 'should display the correct inspector style controls', async ( {
		editor,
		pageObject,
		wpCoreVersion,
	} ) => {
		await pageObject.addProductFiltersBlock( { cleanContent: true } );

		const block = editor.canvas.getByLabel( 'Block: Color' );

		await expect( block ).toBeVisible();

		await editor.selectBlocks( block );
		await editor.openDocumentSettingsSidebar();
		await editor.page.getByRole( 'tab', { name: 'Styles' } ).click();

		for ( const control of getExpectedStyleControls( wpCoreVersion ) ) {
			await expect(
				editor.page.getByRole( 'heading', {
					name: control,
					exact: true,
				} )
			).toBeVisible();
		}
	} );

	test( 'Dropdown inherits and updates the ancestor background without explicit colors', async ( {
		editor,
		page,
	} ) => {
		const { colorAttributeId } = await getProductAttributeIds();
		await editor.setContent( '' );
		await editor.insertBlock( {
			name: 'core/group',
			attributes: {
				style: { color: { background: '#111111', text: '#ffffff' } },
			},
			innerBlocks: [
				{
					name: 'woocommerce/product-filters',
					attributes: { overlayMode: 'off' },
					innerBlocks: [
						{
							name: 'woocommerce/product-filter-attribute',
							attributes: {
								attributeId: colorAttributeId,
								displayStyle: 'woocommerce/dropdown',
							},
							innerBlocks: [ { name: 'woocommerce/dropdown' } ],
						},
					],
				},
			],
		} );
		const dropdown = editor.canvas.getByLabel( 'Block: Dropdown', {
			exact: true,
		} );
		await editor.selectBlocks( dropdown );
		const trigger = dropdown.locator( '.wc-block-dropdown__trigger' );
		const panel = dropdown.locator( '.wc-block-dropdown__panel' );
		await expect( trigger ).toHaveCSS( 'color', 'rgb(255, 255, 255)' );
		await expect( trigger ).toHaveCSS(
			'background-color',
			'rgb(17, 17, 17)'
		);
		await expect( panel ).toHaveCSS(
			'background-color',
			'rgb(17, 17, 17)'
		);
		await trigger.focus();
		await expect( trigger ).not.toBeFocused();
		await page.evaluate( () => {
			const { select, dispatch } = window.wp.data;
			const group = select( 'core/block-editor' )
				.getBlocks()
				.find( ( block ) => block.name === 'core/group' );
			dispatch( 'core/block-editor' ).updateBlockAttributes(
				group.clientId,
				{ style: { color: { background: '#284b63', text: '#ffffff' } } }
			);
		} );
		await expect( trigger ).toHaveCSS(
			'background-color',
			'rgb(40, 75, 99)'
		);
		await expect( panel ).toHaveCSS(
			'background-color',
			'rgb(40, 75, 99)'
		);
	} );
} );
