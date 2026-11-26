/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { ExperimentsNotice } from '../components/experiments-notice';
import type { ExperimentsNoticeView } from '../types';

const mockApiFetch = jest.fn();
jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: ( ...args: unknown[] ) => mockApiFetch( ...args ),
} ) );

const TEXT =
	'Try newer standards for AI assistants, such as WebMCP and Markdown versions of your pages.';
const ROW =
	'https://example.com/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features#woocommerce_feature_ai_assistant_experiments_enabled';

const notice = (
	overrides: Partial< ExperimentsNoticeView > = {}
): ExperimentsNoticeView => ( {
	text: TEXT,
	link: { href: ROW, text: 'Turn on in Features' },
	...overrides,
} );

describe( 'ExperimentsNotice', () => {
	beforeEach( () => {
		mockApiFetch.mockReset();
		mockApiFetch.mockResolvedValue( { dismissed: true } );
	} );

	it( 'suggests the features and links to their row on the Features page', () => {
		render( <ExperimentsNotice notice={ notice() } /> );

		expect( screen.getByText( TEXT ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Turn on in Features' } )
		).toHaveAttribute( 'href', ROW );
	} );

	it( 'hides when dismissed and remembers it for this user', async () => {
		render( <ExperimentsNotice notice={ notice() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		);

		expect( screen.queryByText( TEXT ) ).not.toBeInTheDocument();
		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/agent-readiness/ai-assistant-experiments-notice',
			method: 'DELETE',
		} );
	} );

	it( 'stays hidden when saving the dismissal fails', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'failed' ) );
		render( <ExperimentsNotice notice={ notice() } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Dismiss' } )
		);

		expect( screen.queryByText( TEXT ) ).not.toBeInTheDocument();
	} );

	it( 'doesn’t make an unsafe link clickable', () => {
		render(
			<ExperimentsNotice
				notice={ notice( {
					link: {
						href: 'javascript:alert(1)',
						text: 'Turn on in Features',
					},
				} ) }
			/>
		);

		expect(
			screen.queryByRole( 'link', { name: 'Turn on in Features' } )
		).not.toBeInTheDocument();
	} );
} );
