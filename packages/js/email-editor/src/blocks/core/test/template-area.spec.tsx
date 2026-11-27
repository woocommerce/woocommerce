/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	preventInsertingIntoTemplateAreas,
	withTemplateAreaFrame,
	withTemplateAreaToolbar,
} from '../template-area';
import {
	getTemplateAreaClientIds,
	isTemplateAreaEditingEnabled,
	useIsTemplateArea,
} from '../../../components/template-areas';
import { recordEvent } from '../../../events';

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
	useSelect: jest.fn(),
} ) );
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ) );
jest.mock( '../../../store', () => ( { storeName: 'email-editor' } ) );
jest.mock( '@wordpress/block-editor', () => ( {
	BlockControls: ( { children } ) => <div>{ children }</div>,
	store: 'core/block-editor',
} ) );
jest.mock( '@wordpress/components', () => ( {
	ToolbarButton: ( { children, onClick } ) => (
		<button onClick={ onClick }>{ children }</button>
	),
} ) );
jest.mock( '@wordpress/compose', () => ( {
	createHigherOrderComponent: ( hoc ) => hoc,
} ) );
jest.mock( '../../../components/template-areas', () => ( {
	getTemplateAreaClientIds: jest.fn(),
	isTemplateAreaEditingEnabled: jest.fn(),
	useIsTemplateArea: jest.fn(),
} ) );
jest.mock( '../../../components/sidebar/edit-template-modal', () => ( {
	EditTemplateModal: () => <div>Confirm editing the template</div>,
} ) );
jest.mock( '../../../events', () => ( { recordEvent: jest.fn() } ) );
jest.mock( '../../../config-tools/filters', () => ( {
	addFilterForEmail: jest.fn(),
} ) );

const BlockEdit = () => <p>Block content</p>;
const BlockListBlock = ( { className, wrapperProps } ) => (
	<div
		data-testid="block"
		className={ className }
		onDoubleClick={ wrapperProps?.onDoubleClick }
	/>
);
const ToolbarBlockEdit = withTemplateAreaToolbar( BlockEdit );
const FramedBlock = withTemplateAreaFrame( BlockListBlock );

describe( 'template area tools', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( useSelect as jest.Mock ).mockImplementation( ( selector ) =>
			selector( () => ( {
				getCurrentTemplate: () => ( {
					id: 'theme//newsletter',
					title: 'Newsletter',
				} ),
			} ) )
		);
	} );

	it( 'shows the template name and an Edit template button that asks before opening the template', () => {
		( useIsTemplateArea as jest.Mock ).mockReturnValue( true );

		render( <ToolbarBlockEdit clientId="header" /> );
		fireEvent.click( screen.getByText( 'Edit template' ) );

		expect( screen.getByText( 'Newsletter' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Confirm editing the template' )
		).toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledWith(
			'template_area_edit_template_clicked',
			{ templateId: 'theme//newsletter' }
		);
	} );

	it( 'adds nothing to blocks outside the template areas', () => {
		( useIsTemplateArea as jest.Mock ).mockReturnValue( false );

		render( <ToolbarBlockEdit clientId="paragraph" /> );
		render( <FramedBlock clientId="paragraph" className="wp-block" /> );

		expect( screen.queryByText( 'Edit template' ) ).not.toBeInTheDocument();
		expect( screen.getByTestId( 'block' ) ).toHaveClass( 'wp-block', {
			exact: true,
		} );
	} );

	it( 'marks a template area and asks before opening the template on double-click', () => {
		( useIsTemplateArea as jest.Mock ).mockReturnValue( true );

		render( <FramedBlock clientId="footer" className="wp-block" /> );
		fireEvent.doubleClick( screen.getByTestId( 'block' ) );

		expect( screen.getByTestId( 'block' ) ).toHaveClass(
			'wp-block',
			'is-email-template-area'
		);
		expect(
			screen.getByText( 'Confirm editing the template' )
		).toBeInTheDocument();
	} );

	it( 'stops blocks from being added inside a template area', () => {
		( isTemplateAreaEditingEnabled as jest.Mock ).mockReturnValue( true );
		( getTemplateAreaClientIds as jest.Mock ).mockReturnValue( [
			'header',
		] );

		expect( preventInsertingIntoTemplateAreas( true, {}, 'header' ) ).toBe(
			false
		);
		expect( preventInsertingIntoTemplateAreas( true, {}, 'content' ) ).toBe(
			true
		);
		expect(
			preventInsertingIntoTemplateAreas( false, {}, 'content' )
		).toBe( false );
	} );
} );
