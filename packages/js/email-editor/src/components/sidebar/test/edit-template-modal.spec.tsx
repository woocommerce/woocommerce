/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EditTemplateModal } from '../edit-template-modal';

jest.mock( '@wordpress/data', () => ( { useSelect: jest.fn() } ) );
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ) );
jest.mock( '../../../store', () => ( { storeName: 'email-editor' } ) );
jest.mock( '../../../events', () => ( {
	recordEvent: jest.fn(),
	recordEventOnce: jest.fn(),
} ) );
jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick } ) => (
		<button onClick={ onClick }>{ children }</button>
	),
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
	Modal: ( { children } ) => <div>{ children }</div>,
} ) );

describe( 'EditTemplateModal', () => {
	it( 'opens the template and closes itself, so it is not open again on the way back', () => {
		const onNavigateToEntityRecord = jest.fn();
		const close = jest.fn();
		( useSelect as jest.Mock ).mockImplementation( ( selector ) =>
			selector( () => ( {
				getEditorSettings: () => ( { onNavigateToEntityRecord } ),
				getCurrentTemplate: () => ( { id: 'theme//newsletter' } ),
			} ) )
		);

		render( <EditTemplateModal close={ close } /> );
		fireEvent.click( screen.getByText( 'Edit template' ) );

		expect( onNavigateToEntityRecord ).toHaveBeenCalledWith( {
			postId: 'theme//newsletter',
			postType: 'wp_template',
		} );
		expect( close ).toHaveBeenCalled();
	} );
} );
