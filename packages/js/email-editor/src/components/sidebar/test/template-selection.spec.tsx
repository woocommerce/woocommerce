import '../../test/__mocks__/setup-shared-mocks';

/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { TemplateSelection } from '../template-selection';
import { usePreviewTemplates } from '../../../hooks';

jest.mock( '@wordpress/components', () => ( {
	Button: ( { children, onClick } ) => (
		<button onClick={ onClick }>{ children }</button>
	),
	DropdownMenu: ( { text } ) => <div>{ `Dropdown: ${ text }` }</div>,
	Flex: ( { children } ) => <div>{ children }</div>,
	FlexItem: ( { children } ) => <div>{ children }</div>,
	MenuItem: ( { children } ) => <div>{ children }</div>,
	PanelRow: ( { children } ) => <div>{ children }</div>,
} ) );

jest.mock( '../../../hooks', () => ( { usePreviewTemplates: jest.fn() } ) );
jest.mock( '../../../events', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '../../template-select', () => ( {
	SelectTemplateModal: () => null,
} ) );
jest.mock( '../edit-template-modal', () => ( {
	EditTemplateModal: () => <div>Confirm editing</div>,
} ) );

const setup = ( canUserEditTemplates: boolean, templateCount: number ) => {
	( useSelect as jest.Mock ).mockImplementation( ( selector ) =>
		selector( () => ( {
			getCurrentTemplate: () => ( { title: 'Newsletter' } ),
			getEditedEmailContent: () => '',
			canUserEditTemplates: () => canUserEditTemplates,
			getEmailPostType: () => 'mailpoet_email',
		} ) )
	);
	( usePreviewTemplates as jest.Mock ).mockReturnValue( [
		Array( templateCount ).fill( {} ),
	] );
	render( <TemplateSelection /> );
};

describe( 'TemplateSelection', () => {
	it( 'opens the confirmation from a visible edit template link', () => {
		setup( true, 1 );

		fireEvent.click( screen.getByText( 'Edit template' ) );

		expect( screen.getByText( 'Confirm editing' ) ).toBeInTheDocument();
	} );

	it( 'hides the link from users who cannot edit templates', () => {
		setup( false, 1 );

		expect( screen.queryByText( 'Edit template' ) ).not.toBeInTheDocument();
		expect( screen.getByText( 'Newsletter' ) ).toBeInTheDocument();
	} );

	it( 'keeps the dropdown only when there is another template to swap to', () => {
		setup( true, 2 );

		expect(
			screen.getByText( 'Dropdown: Newsletter' )
		).toBeInTheDocument();
	} );

	it( 'shows the template name as plain text when there is nothing to swap to', () => {
		setup( true, 1 );

		expect(
			screen.queryByText( 'Dropdown: Newsletter' )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Newsletter' ) ).toBeInTheDocument();
	} );
} );
