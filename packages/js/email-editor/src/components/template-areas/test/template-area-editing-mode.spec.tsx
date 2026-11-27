import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TemplateAreaEditingMode } from '../template-area-editing-mode';
import {
	getTemplateAreaClientIds,
	isTemplateAreaEditingEnabled,
} from '../template-areas';

jest.mock( '../template-areas', () => ( {
	getTemplateAreaClientIds: jest.fn(),
	isTemplateAreaEditingEnabled: jest.fn(),
} ) );

const setBlockEditingMode = jest.fn();
const unsetBlockEditingMode = jest.fn();

const setup = ( enabled: boolean ) => {
	( isTemplateAreaEditingEnabled as jest.Mock ).mockReturnValue( enabled );
	( getTemplateAreaClientIds as jest.Mock ).mockReturnValue( [
		'header',
		'footer',
	] );
	( useSelect as jest.Mock ).mockImplementation( ( selector ) =>
		selector( () => ( {
			getClientIdsOfDescendants: ( ids: string[] ) =>
				ids.includes( 'header' ) ? [ 'logo' ] : [],
		} ) )
	);
	( useDispatch as jest.Mock ).mockReturnValue( {
		setBlockEditingMode,
		unsetBlockEditingMode,
	} );
};

describe( 'TemplateAreaEditingMode', () => {
	beforeEach( () => jest.clearAllMocks() );

	it( 'makes the template areas selectable and keeps their blocks locked', () => {
		setup( true );

		const { unmount } = render( <TemplateAreaEditingMode /> );

		expect( setBlockEditingMode.mock.calls ).toEqual( [
			[ 'header', 'contentOnly' ],
			[ 'footer', 'contentOnly' ],
			[ 'logo', 'disabled' ],
		] );

		unmount();

		expect( unsetBlockEditingMode.mock.calls ).toEqual( [
			[ 'header' ],
			[ 'footer' ],
			[ 'logo' ],
		] );
	} );

	it( 'leaves the template locked when area editing is off', () => {
		setup( false );

		render( <TemplateAreaEditingMode /> );

		expect( setBlockEditingMode ).not.toHaveBeenCalled();
	} );
} );
