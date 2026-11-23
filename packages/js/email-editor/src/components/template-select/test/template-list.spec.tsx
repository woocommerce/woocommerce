/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import { TemplateList } from '../template-list';
import type { TemplatePreview } from '../../../store';

jest.mock( '@wordpress/editor', () => ( { store: { name: 'core/editor' } } ) );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn( () => ( { layout: undefined } ) ),
} ) );
jest.mock( '@wordpress/block-editor', () => ( {
	BlockPreview: () => null,
} ) );
jest.mock( '../async', () => ( {
	Async: ( { children } ) => children,
} ) );
jest.mock( '../../../hooks', () => ( { useEmailCss: () => [ [] ] } ) );
jest.mock( '../../../store', () => ( {} ) );

const template = ( id: number, category: string ) =>
	( {
		id,
		slug: `template-${ id }`,
		displayName: `Template ${ id }`,
		category,
		previewContentParsed: [],
	} ) as unknown as TemplatePreview;

describe( 'TemplateList', () => {
	const templates = [
		template( 1, 'basic' ),
		template( 2, 'welcome' ),
		template( 3, 'basic' ),
	];

	it( 'shows only the templates of the selected category', () => {
		render(
			<TemplateList
				templates={ templates }
				onTemplateSelection={ jest.fn() }
				selectedCategory="basic"
			/>
		);

		expect(
			screen.getAllByRole( 'heading' ).map( ( h ) => h.textContent )
		).toEqual( [ 'Template 1', 'Template 3' ] );
	} );

	it( 'shows the empty state and the legacy notice for a recent category without emails', () => {
		render(
			<TemplateList
				templates={ templates }
				onTemplateSelection={ jest.fn() }
				selectedCategory="recent"
			/>
		);

		expect(
			screen.getByText( 'No recent templates.' )
		).toBeInTheDocument();
		// The notice repeats its text for screen readers.
		expect(
			screen.getAllByText(
				'Templates created on the legacy editor will not appear here.'
			).length
		).toBeGreaterThan( 0 );
		expect( screen.queryAllByRole( 'heading', { level: 4 } ) ).toHaveLength(
			0
		);
	} );
} );
