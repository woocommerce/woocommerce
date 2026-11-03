/**
 * External dependencies
 */
import { act, fireEvent, screen, within } from '@testing-library/react';
import { useState } from '@wordpress/element';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { renderParentBlock } from '../render-parent-block';

describe( 'renderParentBlock', () => {
	let roots: ReturnType< typeof renderParentBlock > = [];

	const renderTestParent = async (
		props: Partial< Parameters< typeof renderParentBlock >[ 0 ] > = {}
	) => {
		// renderParentBlock creates its own root, outside Testing Library's render.
		// eslint-disable-next-line testing-library/no-unnecessary-act
		await act( async () => {
			roots = renderParentBlock( {
				Block: ( { children } ) => <div>{ children }</div>,
				selector: '.test-parent',
				blockName: 'woocommerce/test-parent',
				blockMap: {},
				getProps: () => ( {} ),
				options: { multiple: true },
				...props,
			} );
		} );
	};

	afterEach( () => {
		act( () => {
			roots.forEach( ( { root } ) => root.unmount() );
		} );
		roots = [];
		document.body.innerHTML = '';
	} );

	it( 'keeps the markup inside template elements', async () => {
		document.body.innerHTML =
			'<div class="test-parent"><div class="inner"><template data-wp-each="state.items"><p class="item">Item</p></template></div></div>';

		await renderTestParent();

		const template = document.querySelector( 'template' );

		expect( template?.getAttribute( 'data-wp-each' ) ).toBe(
			'state.items'
		);
		expect( template?.innerHTML ).toBe( '<p class="item">Item</p>' );
	} );

	it( 'keeps interactive nodes and their existing event handlers', async () => {
		document.body.innerHTML =
			'<div class="test-parent"><div class="inner"><div data-wp-interactive="woocommerce/product-collection"><button data-wp-on--click="actions.addCartItem">Add to cart</button><template data-wp-each="state.notices"><p>Notice</p></template></div></div></div>';
		const region = document.querySelector( '[data-wp-interactive]' );
		const button = screen.getByRole( 'button' );
		const template = document.querySelector( 'template' );
		const onClick = jest.fn();
		button.addEventListener( 'click', onClick );

		await renderTestParent();

		fireEvent.click( screen.getByRole( 'button' ) );
		expect( onClick ).toHaveBeenCalledTimes( 1 );
		expect( document.querySelector( '[data-wp-interactive]' ) ).toBe(
			region
		);
		expect( document.querySelector( 'button' ) ).toBe( button );
		expect( document.querySelector( 'template' ) ).toBe( template );
		expect( template?.innerHTML ).toBe( '<p>Notice</p>' );
	} );

	it( 'keeps interactive regions available to hydration that started before React rendering', async () => {
		document.body.innerHTML =
			'<div class="test-parent"><div data-wp-interactive="woocommerce/product-collection"><button>Add to cart</button></div></div>';
		// Hydration queries regions before yielding to other work on the main thread.
		const regions = document.querySelectorAll< HTMLElement >(
			'[data-wp-interactive]'
		);
		const onClick = jest.fn();

		await renderTestParent();

		within( regions[ 0 ] )
			.getByRole( 'button' )
			.addEventListener( 'click', onClick );
		fireEvent.click( screen.getByRole( 'button' ) );

		expect( regions[ 0 ].isConnected ).toBe( true );
		expect( onClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'leaves interactive updates intact when the React parent rerenders', async () => {
		document.body.innerHTML =
			'<div class="test-parent"><div data-wp-interactive="woocommerce/product-collection"><button>Add to cart</button></div></div>';
		const button = screen.getByRole( 'button' );
		const Parent = ( { children }: { children: ReactNode } ) => {
			const [ count, setCount ] = useState( 0 );
			return (
				<div>
					<button onClick={ () => setCount( count + 1 ) }>
						Update
					</button>
					<p>{ count }</p>
					{ children }
				</div>
			);
		};

		await renderTestParent( { Block: Parent } );
		button.textContent = '1 in cart';
		fireEvent.click( screen.getByRole( 'button', { name: 'Update' } ) );

		expect( document.querySelector( 'p' ) ).toHaveTextContent( '1' );
		expect( document.querySelector( '[data-wp-interactive] button' ) ).toBe(
			button
		);
		expect( button ).toHaveTextContent( '1 in cart' );
	} );

	it( 'still renders registered React blocks alongside interactive regions', async () => {
		document.body.innerHTML =
			'<div class="test-parent"><div class="inner"><div data-block-name="woocommerce/test-child">Server content</div><div data-wp-interactive="woocommerce/product-collection"><button>Add to cart</button></div></div></div>';

		await renderTestParent( {
			blockMap: {
				'woocommerce/test-child': () => <p>React content</p>,
			},
		} );

		expect( document.querySelector( 'p' ) ).toHaveTextContent(
			'React content'
		);
		expect( document.body ).not.toHaveTextContent( 'Server content' );
		expect(
			document.querySelector( '[data-wp-interactive]' )
		).toBeVisible();
	} );
} );
