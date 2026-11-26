/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { ExperimentsCard } from '../components/experiments-card';
import type { ExperimentsCardView } from '../types';

const mockApiFetch = jest.fn();
jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: ( ...args: unknown[] ) => mockApiFetch( ...args ),
} ) );

const OFF_TEXT =
	'Try newer standards for AI assistants before they settle, such as WebMCP and Markdown versions of your pages.';
const ON_TEXT =
	'You’re trying newer standards for AI assistants, such as WebMCP and Markdown versions of your pages.';
const ON_ERROR = 'We couldn’t turn on features for AI assistants. Try again.';
const OFF_ERROR = 'We couldn’t turn off features for AI assistants. Try again.';

const card = (
	overrides: Partial< ExperimentsCardView > = {}
): ExperimentsCardView => ( {
	title: 'Features for AI assistants',
	badge: { intent: 'none', text: 'Experimental' },
	enabled: false,
	off: { text: OFF_TEXT, button: 'Turn on', error: ON_ERROR },
	on: { text: ON_TEXT, button: 'Turn off', error: OFF_ERROR },
	...overrides,
} );

// The visible notice, not the copy announced to screen readers.
const notice = ( text: string ) =>
	screen.findByText( text, { selector: '.components-notice__content' } );

describe( 'ExperimentsCard', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
	} );

	it( 'shows the title, badge, description and a Turn on button while off', () => {
		render( <ExperimentsCard card={ card() } /> );

		expect(
			screen.getByText( 'Features for AI assistants' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Experimental' ) ).toBeInTheDocument();
		expect( screen.getByText( OFF_TEXT ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Turn on' } )
		).toBeInTheDocument();
	} );

	it( 'shows the on description and a Turn off button once they’re on', () => {
		render( <ExperimentsCard card={ card( { enabled: true } ) } /> );

		expect( screen.getByText( ON_TEXT ) ).toBeInTheDocument();
		expect( screen.queryByText( OFF_TEXT ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Turn off' } )
		).toBeInTheDocument();
	} );

	it( 'turns them on, then offers to turn them off', async () => {
		mockApiFetch.mockResolvedValue( { enabled: true } );
		render( <ExperimentsCard card={ card() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn on' } )
		);

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/agent-readiness/ai-assistant-experiments',
			method: 'POST',
			data: { enabled: true },
		} );
		expect(
			await screen.findByRole( 'button', { name: 'Turn off' } )
		).toBeInTheDocument();
		expect( screen.getByText( ON_TEXT ) ).toBeInTheDocument();
	} );

	it( 'turns them off, then offers to turn them on', async () => {
		mockApiFetch.mockResolvedValue( { enabled: false } );
		render( <ExperimentsCard card={ card( { enabled: true } ) } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn off' } )
		);

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/agent-readiness/ai-assistant-experiments',
			method: 'POST',
			data: { enabled: false },
		} );
		expect(
			await screen.findByRole( 'button', { name: 'Turn on' } )
		).toBeInTheDocument();
		expect( screen.getByText( OFF_TEXT ) ).toBeInTheDocument();
	} );

	it( 'says they weren’t turned on when the request fails, and keeps the button', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'failed' ) );
		render( <ExperimentsCard card={ card() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn on' } )
		);

		expect( await notice( ON_ERROR ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Turn on' } )
		).toBeInTheDocument();
	} );

	it( 'says they weren’t turned off when the request fails, and keeps the button', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'failed' ) );
		render( <ExperimentsCard card={ card( { enabled: true } ) } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn off' } )
		);

		expect( await notice( OFF_ERROR ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Turn off' } )
		).toBeInTheDocument();
	} );

	it( 'treats a response that doesn’t match the change as a failure', async () => {
		mockApiFetch.mockResolvedValue( { enabled: false } );
		render( <ExperimentsCard card={ card() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn on' } )
		);

		expect( await notice( ON_ERROR ) ).toBeInTheDocument();
	} );
} );
