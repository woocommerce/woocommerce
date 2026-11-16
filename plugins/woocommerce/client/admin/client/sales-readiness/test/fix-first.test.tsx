/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { FixFirstList } from '../components/fix-first';
import type { FixFirstItem } from '../types';

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
} );
