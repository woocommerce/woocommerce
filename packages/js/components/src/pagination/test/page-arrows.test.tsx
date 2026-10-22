/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { isRTL } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PageArrows } from '../page-arrows';
import { PageArrowsWithPicker } from '../page-arrows-with-picker';

jest.mock( '@wordpress/i18n', () => ( {
	...jest.requireActual( '@wordpress/i18n' ),
	isRTL: jest.fn( () => false ),
} ) );

const mockedIsRTL = isRTL as jest.MockedFunction< typeof isRTL >;

// chevronLeft points left (path starts moving toward smaller x), chevronRight
// the opposite; compare the rendered SVG path data to tell them apart.
function getArrowPaths( container: HTMLElement ) {
	const buttons = container.querySelectorAll(
		'.woocommerce-pagination__link'
	);
	return Array.from( buttons ).map(
		( button ) => button.querySelector( 'path' )?.getAttribute( 'd' )
	);
}

const CHEVRON_LEFT_D = 'M14.6 7l-1.2-1L8 12l5.4 6 1.2-1-4.6-5z';
const CHEVRON_RIGHT_D = 'M10.6 6L9.4 7l4.6 5-4.6 5 1.2 1 5.4-6z';

describe( 'PageArrows', () => {
	afterEach( () => {
		mockedIsRTL.mockReturnValue( false );
	} );

	it( 'points previous left and next right in LTR', () => {
		const { container } = render(
			<PageArrows
				currentPage={ 2 }
				pageCount={ 3 }
				setCurrentPage={ () => {} }
			/>
		);
		expect( getArrowPaths( container ) ).toEqual( [
			CHEVRON_LEFT_D,
			CHEVRON_RIGHT_D,
		] );
	} );

	it( 'points previous right and next left in RTL', () => {
		mockedIsRTL.mockReturnValue( true );
		const { container } = render(
			<PageArrows
				currentPage={ 2 }
				pageCount={ 3 }
				setCurrentPage={ () => {} }
			/>
		);
		expect( getArrowPaths( container ) ).toEqual( [
			CHEVRON_RIGHT_D,
			CHEVRON_LEFT_D,
		] );
	} );
} );

describe( 'PageArrowsWithPicker', () => {
	afterEach( () => {
		mockedIsRTL.mockReturnValue( false );
	} );

	it( 'points previous left and next right in LTR', () => {
		const { container } = render(
			<PageArrowsWithPicker
				currentPage={ 2 }
				pageCount={ 3 }
				setCurrentPage={ () => {} }
			/>
		);
		expect( getArrowPaths( container ) ).toEqual( [
			CHEVRON_LEFT_D,
			CHEVRON_RIGHT_D,
		] );
	} );

	it( 'points previous right and next left in RTL', () => {
		mockedIsRTL.mockReturnValue( true );
		const { container } = render(
			<PageArrowsWithPicker
				currentPage={ 2 }
				pageCount={ 3 }
				setCurrentPage={ () => {} }
			/>
		);
		expect( getArrowPaths( container ) ).toEqual( [
			CHEVRON_RIGHT_D,
			CHEVRON_LEFT_D,
		] );
	} );
} );
