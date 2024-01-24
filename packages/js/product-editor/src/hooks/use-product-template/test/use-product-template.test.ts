/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useProductTemplate } from '../use-product-template';

const originalProductBlockEditorSettings =
	globalThis.window.productBlockEditorSettings;

describe( 'useProductTemplate', () => {
	beforeEach( () => {
		globalThis.window.productBlockEditorSettings = {
			productTemplates: [
				{
					id: 'template-1',
					title: 'Template 1',
					description: 'Template 1 description',
					icon: 'icon',
					order: 1,
					layoutTemplateId: 'layout-template-1',
					postType: 'product',
					productData: {
						type: 'simple',
					},
				},
				{
					id: 'template-2a',
					title: 'Template 2a',
					description: 'Template 2a description',
					icon: 'icon',
					order: 999,
					layoutTemplateId: 'layout-template-2a',
					postType: 'custom_product',
					productData: {
						type: 'grouped',
					},
				},
				{
					id: 'template-2',
					title: 'Template 2',
					description: 'Template 2 description',
					icon: 'icon',
					layoutTemplateId: 'layout-template-2',
					order: 2,
					postType: 'product',
					productData: {
						type: 'grouped',
					},
				},
				{
					id: 'template-3',
					title: 'Template 3',
					description: 'Template 3 description',
					icon: 'icon',
					layoutTemplateId: 'layout-template-3',
					order: 3,
					postType: 'product',
					productData: {
						type: 'simple',
					},
				},
				{
					id: 'template-4',
					title: 'Template 4',
					description: 'Template 4 description',
					icon: 'icon',
					layoutTemplateId: 'layout-template-4',
					order: 4,
					postType: 'product_variation',
					productData: {
						type: undefined,
					},
				},
			],
		};
	} );

	afterEach( () => {
		globalThis.window.productBlockEditorSettings =
			originalProductBlockEditorSettings;
	} );

	it( 'should return the product template if it exists', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-3', 'simple', 'product' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-3' );
	} );

	it( 'should return the first product template with a matching type in the productData if no matching product template by id', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', 'grouped', 'product' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-2' );
	} );

	it( 'should return the first product template with a matching type in the productData if no product template id is set', () => {
		const { result } = renderHook( () =>
			useProductTemplate( undefined, 'grouped', 'product' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-2' );
	} );

	it( 'should return the first product template with a matching post type when no product template id or product type is set', () => {
		const { result } = renderHook( () =>
			useProductTemplate( undefined, undefined, 'product_variation' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-4' );
	} );

	it( 'should return undefined if no matching product template by id or type', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', 'external', 'product' )
		);

		expect( result.current.productTemplate ).toBeUndefined();
	} );

	it( 'should return undefined if post type is not set', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-1', 'simple', undefined )
		);

		expect( result.current.productTemplate ).toBeUndefined();
	} );

	it( 'should return undefined if post type does not match', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-1', 'simple', 'product_variation' )
		);

		expect( result.current.productTemplate ).toBeUndefined();
	} );

	it( 'should use the product type to match if the product template id matches a template with a different product type', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-2', 'simple', 'product' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-1' );
	} );
} );
