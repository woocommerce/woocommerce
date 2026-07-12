/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import withScrollToTop from '../index';

const focusedMock = jest.fn();
const scrollIntoViewMock = jest.fn();

let scrollToTop;
const TestComponent = withScrollToTop( ( props ) => {
	scrollToTop = props.scrollToTop;
	return (
		<span>
			<button />
		</span>
	);
} );

const setup = ( { inView } ) => {
	render( <TestComponent /> );
	const button = screen.getByRole( 'button' );
	button.focus = focusedMock;
	button.scrollIntoView = scrollIntoViewMock;
	button.getBoundingClientRect = () => ( {
		bottom: inView ? 0 : -10,
	} );
	scrollToTop( { focusableSelector: 'button' } );
};

describe( 'withScrollToTop Component', () => {
	afterEach( () => {
		focusedMock.mockReset();
		scrollIntoViewMock.mockReset();
	} );

	describe( 'if component is not in view', () => {
		beforeEach( () => {
			setup( { inView: false } );
		} );

		it( 'scrolls to top of the component when scrollToTop is called', () => {
			expect( scrollIntoViewMock ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'moves focus to top of the component when scrollToTop is called', () => {
			expect( focusedMock ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'if component is in view', () => {
		beforeEach( () => {
			setup( { inView: true } );
		} );

		it( "doesn't scroll to top of the component when scrollToTop is called", () => {
			expect( scrollIntoViewMock ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'moves focus to top of the component when scrollToTop is called', () => {
			expect( focusedMock ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
