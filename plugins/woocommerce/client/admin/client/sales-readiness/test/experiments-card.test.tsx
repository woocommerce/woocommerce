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

const FEATURES =
	'https://example.com/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features';

const card = (
	overrides: Partial< ExperimentsCardView > = {}
): ExperimentsCardView => ( {
	title: 'Features for AI assistants',
	badge: { intent: 'none', text: 'Experimental' },
	text: 'Try newer standards for AI assistants before they settle, such as WebMCP and Markdown versions of your pages.',
	enabled: false,
	button: 'Turn on',
	on: {
		text: 'You’re trying newer standards for AI assistants, such as WebMCP and Markdown versions of your pages. You can turn them off in <link>Settings > Advanced > Features</link>.',
		href: FEATURES,
	},
	error: 'We couldn’t turn on features for AI assistants. Try again.',
	...overrides,
} );

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
		expect(
			screen.getByText(
				'Try newer standards for AI assistants before they settle, such as WebMCP and Markdown versions of your pages.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Turn on' } )
		).toBeInTheDocument();
		expect( screen.queryByText( /You’re trying/ ) ).not.toBeInTheDocument();
	} );

	it( 'turns them on, then swaps the description for one linking to Features', async () => {
		mockApiFetch.mockResolvedValue( { enabled: true } );
		render( <ExperimentsCard card={ card() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn on' } )
		);

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/agent-readiness/ai-assistant-experiments',
			method: 'POST',
		} );
		expect(
			await screen.findByRole( 'link', {
				name: 'Settings > Advanced > Features',
			} )
		).toHaveAttribute( 'href', FEATURES );
		expect(
			screen.queryByText(
				'Try newer standards for AI assistants before they settle, such as WebMCP and Markdown versions of your pages.'
			)
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Turn on' } )
		).not.toBeInTheDocument();
	} );

	it( 'says they weren’t turned on when the request fails, and keeps the button', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'failed' ) );
		render( <ExperimentsCard card={ card() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn on' } )
		);

		expect(
			await screen.findByText(
				'We couldn’t turn on features for AI assistants. Try again.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Turn on' } )
		).toBeInTheDocument();
	} );

	it( 'treats a response that isn’t enabled as a failure', async () => {
		mockApiFetch.mockResolvedValue( { enabled: false } );
		render( <ExperimentsCard card={ card() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Turn on' } )
		);

		expect(
			await screen.findByText(
				'We couldn’t turn on features for AI assistants. Try again.',
				{ selector: '.components-notice__content' }
			)
		).toBeInTheDocument();
	} );

	it( 'replaces the description with one that says they’re on and links to Features', () => {
		render( <ExperimentsCard card={ card( { enabled: true } ) } /> );

		expect(
			screen.getByText(
				( _content, element ) =>
					element?.tagName !== 'A' &&
					element?.textContent ===
						'You’re trying newer standards for AI assistants, such as WebMCP and Markdown versions of your pages. You can turn them off in Settings > Advanced > Features.'
			)
		).toBeInTheDocument();
		expect(
			screen.queryByText(
				'Try newer standards for AI assistants before they settle, such as WebMCP and Markdown versions of your pages.'
			)
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'Settings > Advanced > Features',
			} )
		).toHaveAttribute( 'href', FEATURES );
		expect(
			screen.queryByRole( 'button', { name: 'Turn on' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the Features path as plain text when its link is unsafe', () => {
		render(
			<ExperimentsCard
				card={ card( {
					enabled: true,
					on: { ...card().on, href: 'javascript:alert(1)' },
				} ) }
			/>
		);

		expect(
			screen.getByText( 'Settings > Advanced > Features' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );
} );
