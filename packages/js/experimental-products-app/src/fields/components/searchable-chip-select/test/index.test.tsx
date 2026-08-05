/**
 * External dependencies
 */
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';

/**
 * Internal dependencies
 */
import { SearchableChipSelect, SearchableChipSelectControl } from '../index';

describe( 'SearchableChipSelect', () => {
	const mockItems = [
		{ value: 'apple', label: 'Apple' },
		{ value: 'banana', label: 'Banana' },
		{ value: 'cherry', label: 'Cherry' },
	];

	it( 'forwards ref', () => {
		const ref = createRef< HTMLDivElement >();

		render( <SearchableChipSelect ref={ ref } /> );

		expect( ref.current ).toBeInstanceOf( HTMLDivElement );
	} );

	it( 'passes aria-label and aria-describedby props to the input', () => {
		render(
			<>
				<SearchableChipSelect
					aria-label="My label"
					aria-describedby="searchable-chip-select-description"
				/>
				<p id="searchable-chip-select-description">My description</p>
			</>
		);

		expect(
			screen.getByRole( 'combobox', {
				name: 'My label',
				description: 'My description',
			} )
		).toBeInTheDocument();
	} );

	it( 'passes aria-labelledby prop to the input', () => {
		render(
			<>
				<p id="searchable-chip-select-label">My label</p>
				<SearchableChipSelect aria-labelledby="searchable-chip-select-label" />
			</>
		);

		expect(
			screen.getByRole( 'combobox', {
				name: 'My label',
			} )
		).toBeInTheDocument();
	} );

	it( 'renders accessible control label and description', () => {
		render(
			<SearchableChipSelectControl
				label="Fruits"
				description="Choose your favorite fruits"
				items={ mockItems }
			/>
		);

		expect(
			screen.getByRole( 'combobox', {
				name: 'Fruits',
				description: 'Choose your favorite fruits',
			} )
		).toBeVisible();
	} );

	it( 'renders a placeholder chip when there are no selected values', () => {
		render(
			<SearchableChipSelectControl
				label="Fruits"
				items={ mockItems }
				placeholderChip="Mixed (2)"
			/>
		);

		expect( screen.getByText( 'Mixed (2)' ) ).toBeVisible();
		expect(
			screen.queryByLabelText( 'Clear all' )
		).not.toBeInTheDocument();
	} );

	it( 'renders custom empty content', async () => {
		render(
			<SearchableChipSelectControl
				label="Fruits"
				items={ [] }
				emptyContent="No fruit found."
			/>
		);

		await userEvent.click(
			screen.getByRole( 'combobox', {
				name: 'Fruits',
			} )
		);

		expect( screen.getByRole( 'status' ) ).toHaveTextContent(
			'No fruit found.'
		);
	} );

	it( 'supports custom chip and item renderers', async () => {
		const ref = createRef< HTMLDivElement >();
		const chipRef = createRef< HTMLDivElement >();
		const itemRef = createRef< HTMLDivElement >();

		render(
			<SearchableChipSelectControl
				ref={ ref }
				label="Fruits"
				items={ mockItems }
				defaultValue={ [ mockItems[ 0 ] ] }
				chipsContent={ ( value ) =>
					value.map( ( item ) => (
						<SearchableChipSelectControl.ChipWithRemove
							key={ item.value }
							ref={ chipRef }
						>
							{ item.label }
						</SearchableChipSelectControl.ChipWithRemove>
					) )
				}
			>
				{ ( item ) => (
					<SearchableChipSelectControl.Item
						key={ item.value }
						ref={ item.value === 'banana' ? itemRef : undefined }
						value={ item }
					>
						{ item.label }
					</SearchableChipSelectControl.Item>
				) }
			</SearchableChipSelectControl>
		);

		expect( ref.current ).toBeInstanceOf( HTMLDivElement );
		expect( chipRef.current ).toBeInstanceOf( HTMLDivElement );

		await userEvent.click(
			screen.getByRole( 'combobox', {
				name: 'Fruits',
			} )
		);

		await waitFor( () => {
			expect( itemRef.current ).toBeInstanceOf( HTMLDivElement );
		} );
	} );
} );
