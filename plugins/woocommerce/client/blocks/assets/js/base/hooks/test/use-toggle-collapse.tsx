/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import { animateCollapse, collapseCopy } from '@woocommerce/base-utils';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useToggleCollapse } from '../use-toggle-collapse';

jest.mock( '@woocommerce/base-utils', () => ( {
	animateCollapse: jest.fn(),
	collapseCopy: jest.fn(),
} ) );

let toggle: ( event?: { detail: number } ) => void;

const Section = ( {
	initialShown = false,
	growFromTrigger = false,
}: {
	initialShown?: boolean;
	growFromTrigger?: boolean;
} ) => {
	const [ isShown, setIsShown ] = useState( initialShown );
	const { triggerRef, onToggle } = useToggleCollapse< HTMLButtonElement >(
		isShown,
		() => document.getElementById( 'content' ),
		{ growFromTrigger }
	);
	toggle = ( event ) => {
		onToggle( event && { ...event, currentTarget: triggerRef.current } );
		setIsShown( ! isShown );
	};
	return (
		<>
			<button ref={ triggerRef }>Toggle</button>
			{ isShown && <div id="content" /> }
		</>
	);
};

describe( 'useToggleCollapse', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'grows the content after a click', () => {
		render( <Section /> );
		act( () => toggle( { detail: 1 } ) );
		expect( animateCollapse ).toHaveBeenLastCalledWith(
			screen.getByRole( 'button' ).nextElementSibling,
			true,
			true,
			undefined
		);
	} );

	it( 'grows the content from the height of the control', () => {
		render( <Section growFromTrigger /> );
		jest.spyOn(
			screen.getByRole( 'button' ),
			'offsetHeight',
			'get'
		).mockReturnValue( 24 );
		act( () => toggle( { detail: 1 } ) );
		expect( animateCollapse ).toHaveBeenLastCalledWith(
			expect.any( HTMLElement ),
			true,
			true,
			{ height: '24px', opacity: '0' }
		);
	} );

	it( 'collapses a copy of the content after a click', () => {
		render( <Section initialShown /> );
		const content = document.getElementById( 'content' );
		act( () => toggle( { detail: 1 } ) );
		expect( collapseCopy ).toHaveBeenCalledWith( content );
	} );

	it( 'applies a key press immediately', () => {
		render( <Section initialShown /> );
		act( () => toggle( { detail: 0 } ) );
		expect( collapseCopy ).not.toHaveBeenCalled();
		act( () => toggle( { detail: 0 } ) );
		expect( animateCollapse ).toHaveBeenLastCalledWith(
			expect.any( HTMLElement ),
			true,
			false,
			undefined
		);
	} );

	it( 'applies a key press immediately when focus is visible on the control', () => {
		render( <Section initialShown /> );
		jest.spyOn( screen.getByRole( 'button' ), 'matches' ).mockReturnValue(
			true
		);
		act( () => toggle() );
		expect( collapseCopy ).not.toHaveBeenCalled();
	} );
} );
