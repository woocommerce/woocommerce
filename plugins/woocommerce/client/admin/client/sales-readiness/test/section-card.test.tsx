/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { DeclaredCard, SectionCard } from '../components/section-card';
import type {
	CheckRow,
	Declared,
	DeclaredExperiments,
	RowState,
	Section,
} from '../types';

const mockApiFetch = jest.fn();
jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: ( ...args: unknown[] ) => mockApiFetch( ...args ),
} ) );
jest.mock( '../tracks', () => ( { trackReadiness: jest.fn() } ) );

const row = ( id: string, state: RowState ): CheckRow => ( {
	id,
	state,
	title: id,
	badge: null,
	text: '',
	fix: '',
	link: null,
	seen_at: null,
} );

const section = ( rows: CheckRow[], folded: CheckRow[] ): Section => ( {
	id: 'find',
	title: 'Can AI assistants find your store?',
	badge: { intent: 'low', text: '3 issues' },
	note: null,
	channels: [],
	rows,
	folded,
	empty: null,
} );

describe( 'SectionCard', () => {
	it( 'shows every check that did not pass and folds the passed ones', () => {
		render(
			<SectionCard
				section={ section(
					[
						row( 'a', 'required' ),
						row( 'b', 'open' ),
						row( 'c', 'open' ),
						row( 'd', 'unknown' ),
					],
					[ row( 'e', 'pass' ) ]
				) }
			/>
		);
		for ( const shown of [ 'a', 'b', 'c', 'd' ] ) {
			expect( screen.getByText( shown ) ).toBeVisible();
		}
		expect( screen.queryByText( 'e' ) ).toBeNull();
		expect(
			screen.getByRole( 'button', { name: 'Show 1 more check' } )
		).toBeInTheDocument();
	} );

	it( 'has no fold when nothing passed', () => {
		render(
			<SectionCard section={ section( [ row( 'a', 'open' ) ], [] ) } />
		);
		expect( screen.queryByText( /more check/ ) ).toBeNull();
	} );

	it( 'starts closed when every check passed, and lists them all once opened', async () => {
		render(
			<SectionCard
				section={ section(
					[],
					[
						row( 'a', 'pass' ),
						row( 'b', 'pass' ),
						row( 'c', 'pass' ),
					]
				) }
			/>
		);
		expect( screen.queryByText( 'a' ) ).not.toBeVisible();
		await userEvent.click(
			screen.getByRole( 'button', {
				name: /Can AI assistants find your store\?/,
			} )
		);
		for ( const passed of [ 'a', 'b', 'c' ] ) {
			expect( screen.getByText( passed ) ).toBeVisible();
		}
		expect( screen.queryByText( /more check/ ) ).toBeNull();
	} );

	it( 'shows the no products state with a way to add one', () => {
		render(
			<SectionCard
				section={ {
					...section( [], [] ),
					empty: {
						title: 'You don’t have any products yet',
						text: 'Product checks start once you add products.',
						link: { href: '#new', text: 'Add a product' },
					},
				} }
			/>
		);
		expect(
			screen.getByText( 'You don’t have any products yet' )
		).toBeVisible();
		expect(
			screen.getByRole( 'link', { name: 'Add a product' } )
		).toHaveAttribute( 'href', '#new' );
	} );
} );

describe( 'DeclaredCard', () => {
	it( 'lists items under their group, and says which were found, not found or not checked', async () => {
		const { container } = render(
			<DeclaredCard
				declared={ {
					title: 'Extra details for AI assistants',
					badge: { intent: 'none', text: 'Optional' },
					text: 'These are other files and code that some AI assistants use to learn about your store.',
					groups: [
						{
							title: 'Files in your root folder',
							items: [
								{ name: 'ai.txt usage file', found: true },
								{ name: 'AGENTS.md', found: false },
							],
						},
						{
							title: 'Not checked yet',
							items: [ { name: 'TDMRep', found: null } ],
						},
					],
				} }
			/>
		);

		await userEvent.click(
			screen.getByText( 'Extra details for AI assistants' )
		);

		const groups = container.querySelectorAll(
			'.wc-sales-readiness-declared__group'
		);
		expect( groups ).toHaveLength( 2 );
		expect( groups[ 0 ] ).toHaveTextContent( 'Files in your root folder' );
		expect( groups[ 0 ] ).toHaveTextContent( 'Found: ai.txt usage file' );
		expect( groups[ 0 ] ).toHaveTextContent( 'Not found: AGENTS.md' );
		expect( groups[ 1 ] ).toHaveTextContent( 'Not checked yet: TDMRep' );
		// Missing items say so on screen, as in the design; found and not checked ones don't.
		expect( groups[ 0 ] ).toHaveTextContent( 'AGENTS.md · not found' );
		expect( groups[ 0 ] ).not.toHaveTextContent( 'ai.txt usage file ·' );
		expect( groups[ 1 ] ).not.toHaveTextContent( 'not found' );
	} );
} );

describe( 'DeclaredCard AI shopping experiments', () => {
	const experiments = (
		overrides: Partial< DeclaredExperiments > = {}
	): DeclaredExperiments => ( {
		enabled: false,
		tag: 'Experiment',
		offer: {
			title: 'WooCommerce can add 2 of these with AI shopping experiments.',
			text: 'They’re marked below. These standards aren’t settled yet, and few AI assistants use them. You can turn them off at any time in Features.',
			button: 'Turn on AI shopping experiments',
		},
		turned_on: {
			text: 'AI shopping experiments are on. Run the check again to see the new files.',
			button: 'Run check',
		},
		on: {
			text: 'AI shopping experiments are on',
			link: {
				href: 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features',
				text: 'Turn off in Features',
			},
		},
		error: 'AI shopping experiments weren’t turned on. Try again.',
		...overrides,
	} );

	const declared = ( notice: DeclaredExperiments | null ): Declared => ( {
		title: 'Extra details for AI assistants',
		badge: { intent: 'none', text: 'Optional' },
		text: 'These are other files and code that some AI assistants use to learn about your store.',
		groups: [
			{
				title: 'Files in your root folder',
				items: [
					{
						name: 'ai.txt usage file',
						found: false,
						experiment: true,
					},
					{ name: 'AGENTS.md', found: false, experiment: false },
				],
			},
		],
		experiments: notice,
	} );

	const open = () =>
		userEvent.click(
			screen.getByText( 'Extra details for AI assistants' )
		);

	const run = ( pending = false ) => ( {
		onRun: jest.fn(),
		running: false,
		pending,
	} );

	beforeEach( () => {
		mockApiFetch.mockReset();
	} );

	it( 'offers to turn the experiments on, and tags and highlights the items they add', async () => {
		const { container } = render(
			<DeclaredCard
				declared={ declared( experiments() ) }
				run={ run() }
			/>
		);
		await open();

		expect(
			screen.getByText(
				'WooCommerce can add 2 of these with AI shopping experiments.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Turn on AI shopping experiments',
			} )
		).toBeInTheDocument();
		const highlighted = container.querySelectorAll(
			'.wc-sales-readiness-declared__item--experiment'
		);
		expect( highlighted ).toHaveLength( 1 );
		expect( highlighted[ 0 ] ).toHaveTextContent( 'ai.txt usage file' );
		expect( highlighted[ 0 ] ).toHaveTextContent( 'Experiment' );
		expect( screen.getAllByText( 'Experiment' ) ).toHaveLength( 1 );
	} );

	it( 'turns the experiments on, asks to run the check, then shows they are on once it finishes', async () => {
		mockApiFetch.mockResolvedValue( { enabled: true } );
		const checks = run();
		const { container, rerender } = render(
			<DeclaredCard
				declared={ declared( experiments() ) }
				run={ checks }
			/>
		);
		await open();

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Turn on AI shopping experiments',
			} )
		);

		expect( mockApiFetch ).toHaveBeenCalledWith( {
			path: '/wc/v3/agent-readiness/ai-shopping-experiments',
			method: 'POST',
		} );
		expect(
			await screen.findByText(
				'AI shopping experiments are on. Run the check again to see the new files.'
			)
		).toBeInTheDocument();
		expect(
			container.querySelector(
				'.wc-sales-readiness-declared__item--experiment'
			)
		).toBeNull();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Run check' } )
		);
		expect( checks.onRun ).toHaveBeenCalled();

		rerender(
			<DeclaredCard
				declared={ declared( experiments() ) }
				run={ run( true ) }
			/>
		);
		expect(
			screen.getByText(
				'AI shopping experiments are on. Run the check again to see the new files.'
			)
		).toBeInTheDocument();

		rerender(
			<DeclaredCard
				declared={ declared(
					experiments( { enabled: true, offer: null } )
				) }
				run={ run() }
			/>
		);
		expect(
			screen.queryByText(
				'AI shopping experiments are on. Run the check again to see the new files.'
			)
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Turn off in Features' } )
		).toBeInTheDocument();
	} );

	it( 'says the experiments were not turned on when the request fails', async () => {
		mockApiFetch.mockRejectedValue( new Error( 'failed' ) );
		render(
			<DeclaredCard
				declared={ declared( experiments() ) }
				run={ run() }
			/>
		);
		await open();

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Turn on AI shopping experiments',
			} )
		);

		expect(
			await screen.findByText(
				'AI shopping experiments weren’t turned on. Try again.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', {
				name: 'Turn on AI shopping experiments',
			} )
		).toBeInTheDocument();
	} );

	it( 'shows a line linking to Features once the experiments are on, without highlights', async () => {
		const { container } = render(
			<DeclaredCard
				declared={ declared(
					experiments( { enabled: true, offer: null } )
				) }
				run={ run() }
			/>
		);
		await open();

		expect(
			container.querySelector( '.wc-sales-readiness-experiments__on' )
		).toHaveTextContent(
			'AI shopping experiments are on · Turn off in Features'
		);
		expect(
			screen.getByRole( 'link', { name: 'Turn off in Features' } )
		).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);
		expect(
			screen.queryByRole( 'button', {
				name: 'Turn on AI shopping experiments',
			} )
		).not.toBeInTheDocument();
		expect(
			container.querySelector(
				'.wc-sales-readiness-declared__item--experiment'
			)
		).toBeNull();
		expect( screen.getByText( 'Experiment' ) ).toBeInTheDocument();
	} );

	it( 'shows no notice when nothing the experiments add is missing', async () => {
		render(
			<DeclaredCard
				declared={ declared( experiments( { offer: null } ) ) }
				run={ run() }
			/>
		);
		await open();

		expect(
			screen.queryByRole( 'button', {
				name: 'Turn on AI shopping experiments',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'AI shopping experiments are on' )
		).not.toBeInTheDocument();
	} );

	it( 'shows no notice or tags to users who can’t manage WooCommerce', async () => {
		const { container } = render(
			<DeclaredCard declared={ declared( null ) } run={ run() } />
		);
		await open();

		expect( screen.queryByText( 'Experiment' ) ).not.toBeInTheDocument();
		expect(
			container.querySelector( '.wc-sales-readiness-experiments' )
		).toBeNull();
	} );
} );
