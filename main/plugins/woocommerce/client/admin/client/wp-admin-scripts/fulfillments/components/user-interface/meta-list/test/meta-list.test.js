/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MetaList from '../meta-list';

describe( 'MetaList', () => {
	it( 'renders a list of meta items', () => {
		const metaList = [
			{ label: 'Label 1', value: 'Value 1' },
			{ label: 'Label 2', value: 'Value 2' },
		];

		render( <MetaList metaList={ metaList } /> );

		expect( screen.getByText( 'Label 1' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Value 1' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Label 2' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Value 2' ) ).toBeInTheDocument();
	} );

	it( 'renders (empty) for empty values', () => {
		const metaList = [ { label: 'Label 1', value: '' } ];

		render( <MetaList metaList={ metaList } /> );

		expect( screen.getByText( 'Label 1' ) ).toBeInTheDocument();
		expect( screen.getByText( '(empty)' ) ).toBeInTheDocument();
	} );
} );
