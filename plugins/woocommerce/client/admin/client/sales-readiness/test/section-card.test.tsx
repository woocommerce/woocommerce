/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { SectionCard } from '../components/section-card';
import type { CheckRow, RowState, Section } from '../types';

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
	it( 'shows two checks and says how many issues the fold hides', () => {
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
		expect( screen.getByText( 'a' ) ).toBeVisible();
		expect( screen.getByText( 'b' ) ).toBeVisible();
		for ( const folded of [ 'c', 'd', 'e' ] ) {
			expect( screen.queryByText( folded ) ).toBeNull();
		}
		expect(
			screen.getByRole( 'button', {
				name: 'Show 3 more checks, 1 issue',
			} )
		).toBeInTheDocument();
	} );

	it( 'names only the checks when no issue is folded', () => {
		render(
			<SectionCard
				section={ section(
					[ row( 'a', 'open' ) ],
					[ row( 'b', 'pass' ), row( 'c', 'unknown' ) ]
				) }
			/>
		);
		expect(
			screen.getByRole( 'button', { name: 'Show 1 more check' } )
		).toBeInTheDocument();
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
