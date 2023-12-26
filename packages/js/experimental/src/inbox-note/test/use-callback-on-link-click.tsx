/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useCallbackOnLinkClick } from '../use-callback-on-link-click';

const TestComp = ( { callback }: { callback: ( link: string ) => void } ) => {
	const containerRef = useCallbackOnLinkClick( ( link ) => {
		callback( link );
	} );

	return (
		<span ref={ containerRef }>
			Some Text
			<br />
			<span>
				Inner paragraph
				<a href="http://tosomewhere.com">Link</a>
			</span>
			<button>Button</button>
		</span>
	);
};

describe( 'useCallbackOnLinkClick hook', () => {
	it( 'should call callback with link when inner anchor element is clicked', () => {
		const callback = jest.fn();
		const { getByText } = render( <TestComp callback={ callback } /> );
		userEvent.click( getByText( 'Link' ) );
		expect( callback ).toHaveBeenCalledWith( 'http://tosomewhere.com/' );
	} );

	it( 'should not call callback if click event target does not have an href', () => {
		const callback = jest.fn();
		const { getByText } = render( <TestComp callback={ callback } /> );
		userEvent.click( getByText( 'Button' ) );
		expect( callback ).not.toHaveBeenCalled();
	} );

	it( 'should remove listener on unmount', () => {
		const listener = jest.fn();
		const { getByText, unmount } = render(
			<TestComp callback={ jest.fn() } />
		);
		const span = getByText( 'Some Text' );
		jest.spyOn( span, 'removeEventListener' ).mockImplementation(
			listener
		);
		unmount();
		expect( listener ).toHaveBeenCalledTimes( 1 );
	} );
} );
