/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Form } from '@woocommerce/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TagField } from '../tag-field';
import { ProductTagNodeProps } from '../types';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '../use-tag-search', () => {
	return {
		useTagSearch: jest.fn().mockReturnValue( {
			searchTags: jest.fn(),
			getFilteredItemsForSelectTree: jest.fn().mockReturnValue( [] ),
			isSearching: false,
			tagsSelectList: [],
			tagTreeKeyValues: {},
		} ),
	};
} );

describe( 'TagField', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render a dropdown select control', () => {
		const { queryByText, queryByPlaceholderText } = render(
			<Form< {
				tags: ProductTagNodeProps[];
			} >
				initialValues={ { tags: [] } }
			>
				{ ( { getInputProps } ) => (
					<TagField
						id="tag-field"
						isVisible={ true }
						label="Tags"
						placeholder="Search or create tag…"
						{ ...getInputProps( 'tags' ) }
					/>
				) }
			</Form>
		);
		const searchInput = queryByPlaceholderText( 'Search or create tag…' );
		userEvent.click( searchInput! );
		expect( queryByText( 'Create new' ) ).toBeInTheDocument();
	} );

	it( 'should pass in the selected tags as select control items', () => {
		const { queryAllByText, queryByPlaceholderText } = render(
			<Form< {
				tags: ProductTagNodeProps[];
			} >
				initialValues={ {
					tags: [
						{ id: 2, name: 'Test' },
						{ id: 5, name: 'Clothing' },
					],
				} }
			>
				{ ( { getInputProps } ) => (
					<TagField
						id="another-tag-field"
						isVisible={ true }
						label="Tags"
						placeholder="Search or create tag…"
						{ ...getInputProps( 'tags' ) }
					/>
				) }
			</Form>
		);
		queryByPlaceholderText( 'Search or create tag…' )?.focus();
		expect( queryAllByText( 'Test, Clothing' ) ).toHaveLength( 1 );
	} );
} );
