/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { PrototypeFlagsProvider } from '../PrototypeFlagsContext';
import { DevPanel } from '../DevPanel';

function renderWithProvider() {
	return render(
		<PrototypeFlagsProvider>
			<DevPanel />
		</PrototypeFlagsProvider>
	);
}

describe( 'DevPanel', () => {
	beforeEach( () => {
		localStorage.clear();
	} );

	it( 'renders a "Dev" button', () => {
		renderWithProvider();
		expect( screen.getByRole( 'button', { name: /dev/i } ) ).toBeInTheDocument();
	} );

	it( 'does not show flag toggles initially', () => {
		renderWithProvider();
		expect( screen.queryByRole( 'checkbox' ) ).not.toBeInTheDocument();
	} );

	it( 'shows flag toggles after clicking Dev', async () => {
		renderWithProvider();

		await userEvent.click( screen.getByRole( 'button', { name: /dev/i } ) );

		expect( screen.getAllByRole( 'checkbox' ).length ).toBeGreaterThanOrEqual( 1 );
	} );

	it( 'shows flag label text when expanded', async () => {
		renderWithProvider();

		await userEvent.click( screen.getByRole( 'button', { name: /dev/i } ) );

		expect( screen.getByText( 'Example feature' ) ).toBeInTheDocument();
	} );

	it( 'collapses when Dev is clicked again', async () => {
		renderWithProvider();

		await userEvent.click( screen.getByRole( 'button', { name: /dev/i } ) );
		await userEvent.click( screen.getByRole( 'button', { name: /dev/i } ) );

		expect( screen.queryByRole( 'checkbox' ) ).not.toBeInTheDocument();
	} );

	it( 'checkbox reflects current flag value', async () => {
		renderWithProvider();

		await userEvent.click( screen.getByRole( 'button', { name: /dev/i } ) );

		const checkbox = screen.getByRole( 'checkbox', {
			name: /example feature/i,
		} );
		expect( checkbox ).not.toBeChecked();
	} );

	it( 'toggling a checkbox updates the flag', async () => {
		renderWithProvider();

		await userEvent.click( screen.getByRole( 'button', { name: /dev/i } ) );

		const checkbox = screen.getByRole( 'checkbox', {
			name: /example feature/i,
		} );
		await userEvent.click( checkbox );

		expect( checkbox ).toBeChecked();
	} );
} );
