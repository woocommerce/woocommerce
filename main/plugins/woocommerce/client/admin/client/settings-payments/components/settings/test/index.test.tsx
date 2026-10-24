/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { Settings } from '..';

describe( 'Settings component structure', () => {
	it( 'renders layout and nested children', () => {
		render(
			<Settings>
				<Settings.Layout>
					<div data-testid="inside-layout">Layout Content</div>
				</Settings.Layout>
			</Settings>
		);

		expect( screen.getByTestId( 'inside-layout' ) ).toBeInTheDocument();
	} );

	it( 'renders a section with title and description', () => {
		render(
			<Settings.Section
				title="My Section"
				description="This is a description"
			>
				<span>Child Content</span>
			</Settings.Section>
		);

		expect( screen.getByText( 'My Section' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'This is a description' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Child Content' ) ).toBeInTheDocument();
	} );

	it( 'renders a form and triggers onSubmit', () => {
		const onSubmit = jest.fn( ( e ) => e.preventDefault() );
		render(
			<Settings.Form onSubmit={ onSubmit }>
				<button type="submit">Save</button>
			</Settings.Form>
		);

		fireEvent.click( screen.getByText( 'Save' ) );
		expect( onSubmit ).toHaveBeenCalled();
	} );

	it( 'renders actions with children', () => {
		render(
			<Settings.Actions>
				<button>Action Button</button>
			</Settings.Actions>
		);

		expect( screen.getByText( 'Action Button' ) ).toBeInTheDocument();
	} );
} );
