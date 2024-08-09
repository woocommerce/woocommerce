/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { MoreMenuFill } from '../product-block-editor-fills';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );
jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '../more-menu-items', () => ( {
	...jest.requireActual( '../more-menu-items' ),
	ClassicEditorMenuItem: jest.fn().mockImplementation( () => <div></div> ),
	FeedbackMenuItem: jest.fn().mockImplementation( ( { onClick } ) => (
		<div>
			<button onClick={ onClick }>Feedback button</button>
		</div>
	) ),
} ) );

describe( 'MoreMenuFill', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );
	it( 'should trigger product_dropdown_option_click track event when clicking the menu', async () => {
		( useSelect as jest.Mock ).mockReturnValue( {
			type: 'simple',
			status: 'publish',
		} );
		const { getByText } = render( <MoreMenuFill onClose={ () => {} } /> );
		fireEvent.click( getByText( 'Feedback button' ) );

		expect( recordEvent ).toHaveBeenCalledWith(
			'product_dropdown_option_click',
			{
				product_status: 'publish',
				product_type: 'simple',
				selected_option: 'feedback',
			}
		);
	} );
} );
