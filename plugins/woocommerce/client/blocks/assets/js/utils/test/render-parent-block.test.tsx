/**
 * External dependencies
 */
import { act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { renderParentBlock } from '../render-parent-block';

describe( 'renderParentBlock', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'keeps the markup inside template elements', async () => {
		document.body.innerHTML =
			'<div class="test-parent"><div class="inner"><template data-wp-each="state.items"><p class="item">Item</p></template></div></div>';

		await act( async () => {
			renderParentBlock( {
				Block: ( { children } ) => <div>{ children }</div>,
				selector: '.test-parent',
				blockName: 'woocommerce/test-parent',
				blockMap: {},
				getProps: () => ( {} ),
				options: { multiple: true },
			} );
		} );

		const template = document.querySelector( 'template' );

		expect( template?.getAttribute( 'data-wp-each' ) ).toBe(
			'state.items'
		);
		expect( template?.innerHTML ).toBe( '<p class="item">Item</p>' );
	} );
} );
