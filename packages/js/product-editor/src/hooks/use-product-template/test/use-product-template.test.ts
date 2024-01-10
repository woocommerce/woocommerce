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
					productData: {
						type: 'simple',
					},
				},
				{
					id: 'template-2',
					title: 'Template 2',
					description: 'Template 2 description',
					icon: 'icon',
					layoutTemplateId: 'layout-template-2',
					order: 2,
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
					productData: {
						type: 'simple',
					},
				},
				{
					id: 'standard-product-template',
					title: 'Standard Product Template',
					description: 'Standard Product Template description',
					icon: 'icon',
					layoutTemplateId: 'layout-template-4',
					order: 4,
					productData: {
						type: 'simple',
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
			useProductTemplate( 'template-3', 'simple' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-3' );
	} );

	it( 'should return the first product template with a matching type in the productData if no matching product template by id', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', 'grouped' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-2' );
	} );

	it( 'should return the first product template with a matching type in the productData if no product template id is set', () => {
		const { result } = renderHook( () =>
			useProductTemplate( undefined, 'grouped' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-2' );
	} );

	it( 'should return undefined if no matching product template by id or type', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', 'external' )
		);

		expect( result.current.productTemplate ).toBeUndefined();
	} );

	it( 'should use the standard product template if the product type is variable', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-1', 'variable' )
		);

		expect( result.current.productTemplate?.id ).toEqual(
			'standard-product-template'
		);
	} );

	it( 'should use the product type to match if the product template id matches a template with a different product type', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-2', 'simple' )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-1' );
	} );
} );
