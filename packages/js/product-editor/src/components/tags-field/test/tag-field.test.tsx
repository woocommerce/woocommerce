/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { Form, FormContextType } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
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
			<Form initialValues={ { tags: [] } }>
				{ ( { getInputProps }: FormContextType< Product > ) => (
					<TagField
						id="tag-field"
						isVisible={ true }
						label="Tags"
						placeholder="Search or create tag…"
						{ ...getInputProps< ProductTagNodeProps[] >( 'tags' ) }
					/>
				) }
			</Form>
		);
		queryByPlaceholderText( 'Search or create tag…' )?.focus();
		expect( queryByText( 'Create new' ) ).toBeInTheDocument();
	} );

	it( 'should pass in the selected tags as select control items', () => {
		const { queryAllByText, queryByPlaceholderText } = render(
			<Form
				initialValues={ {
					tags: [
						{ id: 2, name: 'Test' },
						{ id: 5, name: 'Clothing' },
					],
				} }
			>
				{ ( { getInputProps }: FormContextType< Product > ) => (
					<TagField
						id="another-tag-field"
						isVisible={ true }
						label="Tags"
						placeholder="Search or create tag…"
						{ ...getInputProps< ProductTagNodeProps[] >( 'tags' ) }
					/>
				) }
			</Form>
		);
		queryByPlaceholderText( 'Search or create tag…' )?.focus();
		expect( queryAllByText( 'Test, Clothing' ) ).toHaveLength( 1 );
	} );
} );
