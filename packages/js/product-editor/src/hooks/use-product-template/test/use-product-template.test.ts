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
					isSelectableByUser: true,
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
					isSelectableByUser: true,
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
					isSelectableByUser: true,
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
					isSelectableByUser: true,
					order: 4,
					productData: {
						type: 'simple',
					},
				},
				{
					id: 'gift-card-product-template',
					title: 'Gift card Product Template',
					description: 'Gift CardProduct Template description',
					icon: 'icon',
					layoutTemplateId: 'layout-template-5',
					isSelectableByUser: true,
					order: 5,
					productData: {
						type: 'simple',
						meta_data: [ { key: '_gift_card', value: 'yes' } ],
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
			useProductTemplate( 'template-3', { type: 'simple' } )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-3' );
	} );

	it( 'should return the first product template with a matching type in the productData if no matching product template by id', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', { type: 'grouped' } )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-2' );
	} );

	it( 'should return the first product template with a matching type in the productData if no product template id is set', () => {
		const { result } = renderHook( () =>
			useProductTemplate( undefined, { type: 'grouped' } )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-2' );
	} );

	it( 'should return undefined if no matching product template by id or type', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', { type: 'external' } )
		);

		expect( result.current.productTemplate ).toBeUndefined();
	} );

	it( 'should use the standard product template if no templateId is provided', () => {
		const { result } = renderHook( () =>
			useProductTemplate( undefined, { type: 'simple' } )
		);

		expect( result.current.productTemplate?.id ).toEqual(
			'standard-product-template'
		);
	} );

	it( 'should use the product type to match if the product template id matches a template with a different product type', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'template-2', { type: 'simple' } )
		);

		expect( result.current.productTemplate?.id ).toEqual( 'template-1' );
	} );

	it( 'should select the product template with the most matching fields if there are multiple matching templates', () => {
		const { result } = renderHook( () =>
			useProductTemplate( 'invalid-template-id', {
				type: 'simple',
				meta_data: [ { key: '_gift_card', value: 'yes' } ],
			} )
		);

		expect( result.current.productTemplate?.id ).toEqual(
			'gift-card-product-template'
		);
	} );
} );
