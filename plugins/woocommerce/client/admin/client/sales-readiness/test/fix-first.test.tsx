/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { FixFirstCard, FixFirstList } from '../components/fix-first';
import type { FixFirstItem } from '../types';

jest.mock( '../tracks', () => ( { trackReadiness: jest.fn() } ) );

const item = ( id: string, tier: string ): FixFirstItem => ( {
	id,
	tier,
	badge:
		tier === 'Required for AI shopping'
			? { intent: 'low', text: 'Required' }
			: null,
	title: id,
	why: '',
	link: null,
} );

describe( 'FixFirstList', () => {
	it( 'names each tier once, with the Required badge once beside its heading', () => {
		render(
			<FixFirstList
				items={ [
					item( 'payment_method', 'Required for AI shopping' ),
					item( 'guest_checkout', 'Required for AI shopping' ),
					item( 'image', 'Needed to list products' ),
				] }
				surface="page"
			/>
		);

		expect(
			screen.getAllByText( 'Required for AI shopping' )
		).toHaveLength( 1 );
		expect( screen.getAllByText( 'Needed to list products' ) ).toHaveLength(
			1
		);
		expect( screen.getAllByText( 'Required' ) ).toHaveLength( 1 );
	} );

	it( 'starts an item’s line with what it affects, in bold', () => {
		render(
			<FixFirstList
				items={ [
					{
						...item( 'missing_images', 'Needed to list products' ),
						impact: 'Affects 38% of your sales.',
						why: 'AI shopping apps need a main image to list a product.',
					},
				] }
				surface="page"
			/>
		);

		const impact = screen.getByText( 'Affects 38% of your sales.' );
		expect( impact.tagName ).toBe( 'STRONG' );
		expect( impact.parentElement ).toHaveTextContent(
			'Affects 38% of your sales. AI shopping apps need a main image to list a product.'
		);
	} );
} );

describe( 'FixFirstCard', () => {
	it( 'links to where Analytics is turned on, after the ranking line', () => {
		render(
			<FixFirstCard
				fixFirst={ {
					title: 'Fix first',
					ranking:
						'Start with the fixes that affect the most products.',
					link: {
						href: '/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features',
						text: 'Turn on Analytics to see which fixes affect the most sales.',
					},
					items: [ item( 'image', 'Needed to list products' ) ],
				} }
			/>
		);

		expect(
			screen.getByRole( 'link', {
				name: 'Turn on Analytics to see which fixes affect the most sales.',
			} )
		).toHaveAttribute(
			'href',
			'/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features'
		);
	} );
} );
