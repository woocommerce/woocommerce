/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { createRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Button from '../index';

describe( 'Button global styles', () => {
	it.each( [ undefined, '/checkout/' ] )(
		'uses the core Button selectors for href %s without adding a layout box',
		( href ) => {
			render( <Button { ...( href ? { href } : {} ) }>Continue</Button> );

			const button = screen.getByRole( href ? 'link' : 'button' );
			expect( button ).toHaveClass(
				'wp-element-button',
				'wp-block-button__link'
			);
			expect( button.closest( '.wp-block-button' ) ).toHaveStyle( {
				display: 'contents',
			} );
		}
	);

	it( 'preserves refs, custom styles and click handlers', () => {
		const ref = createRef< HTMLButtonElement >();
		const onClick = jest.fn();
		render(
			<Button
				ref={ ref }
				onClick={ onClick }
				style={ { borderRadius: '12px' } }
			>
				Apply
			</Button>
		);

		const button = screen.getByRole( 'button' );
		expect( ref.current ).toBe( button );
		expect( button ).toHaveStyle( { borderRadius: '12px' } );
		fireEvent.click( button );
		expect( onClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'preserves the disabled state', () => {
		const onClick = jest.fn();
		render(
			<Button disabled onClick={ onClick }>
				Apply
			</Button>
		);

		const button = screen.getByRole( 'button' );
		expect( button ).toBeDisabled();
		fireEvent.click( button );
		expect( onClick ).not.toHaveBeenCalled();
	} );
} );
