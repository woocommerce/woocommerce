/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { SelectControl } from '../index';

describe( 'SelectControl', () => {
	const query = 'lorem';
	const options = [
		{ key: '1', label: 'lorem 1', value: { id: '1' } },
		{ key: '2', label: 'lorem 2', value: { id: '2' } },
		{ key: '3', label: 'bar', value: { id: '3' } },
	];

	it( 'returns all elements', () => {
		const { getByRole } = render( <SelectControl options={ options } /> );

		userEvent.click( getByRole( 'combobox' ) );

		expect(
			getByRole( 'option', { name: 'lorem 1' } )
		).toBeInTheDocument();
		expect(
			getByRole( 'option', { name: 'lorem 2' } )
		).toBeInTheDocument();
		expect( getByRole( 'option', { name: 'bar' } ) ).toBeInTheDocument();
	} );

	it( 'returns matching elements', async () => {
		const { getByRole, queryByRole } = render(
			<SelectControl isSearchable options={ options } />
		);

		userEvent.type( getByRole( 'combobox' ), query );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'lorem 1' } )
			).toBeInTheDocument()
		);

		expect(
			getByRole( 'option', { name: 'lorem 2' } )
		).toBeInTheDocument();
		expect( queryByRole( 'option', { name: 'bar' } ) ).toBeNull();
	} );

	it( "doesn't return matching excluded elements", async () => {
		const { getByRole, queryByRole } = render(
			<SelectControl
				isSearchable
				options={ options }
				selected={ [ options[ 1 ] ] }
				excludeSelectedOptions
				multiple
			/>
		);

		userEvent.type( getByRole( 'combobox' ), query );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'lorem 1' } )
			).toBeInTheDocument()
		);

		expect( queryByRole( 'option', { name: 'lorem 2' } ) ).toBeNull();
		expect( queryByRole( 'option', { name: 'bar' } ) ).toBeNull();
	} );

	it( 'trims spaces from input', async () => {
		const { getByRole, queryByRole } = render(
			<SelectControl isSearchable options={ options } />
		);

		userEvent.type( getByRole( 'combobox' ), '    ' + query + ' ' );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'lorem 1' } )
			).toBeInTheDocument()
		);

		expect(
			getByRole( 'option', { name: 'lorem 2' } )
		).toBeInTheDocument();
		expect( queryByRole( 'option', { name: 'bar' } ) ).toBeNull();
	} );

	it( 'limits results', async () => {
		const { getByRole, getAllByRole } = render(
			<SelectControl isSearchable options={ options } maxResults={ 1 } />
		);

		userEvent.type( getByRole( 'combobox' ), query );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'lorem 1' } )
			).toBeInTheDocument()
		);

		expect( getAllByRole( 'option' ) ).toHaveLength( 1 );
	} );

	it( 'shows options initially', async () => {
		const { getByRole, getAllByRole } = render(
			<SelectControl isSearchable options={ options } />
		);

		userEvent.click( getByRole( 'combobox' ) );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect( getAllByRole( 'option' ) ).toHaveLength( 3 )
		);
	} );

	it( 'hides options before query', async () => {
		const { getByRole, getAllByRole, queryAllByRole } = render(
			<SelectControl hideBeforeSearch isSearchable options={ options } />
		);

		userEvent.click( getByRole( 'combobox' ) );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect( queryAllByRole( 'option' ) ).toHaveLength( 0 )
		);

		userEvent.type( getByRole( 'combobox' ), query );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect( getAllByRole( 'option' ) ).toHaveLength( 2 )
		);
	} );

	it( 'appends an option after filtering', async () => {
		const { getByRole, getAllByRole } = render(
			<SelectControl
				options={ options }
				onFilter={ ( filteredOptions ) =>
					filteredOptions.concat( [
						{ key: 'new-option', label: 'New options' },
					] )
				}
			/>
		);

		userEvent.type( getByRole( 'combobox' ), query );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			// TODO: check the actual options here - "bar" is shown, where I expected "new options". -Jeff
			expect( getAllByRole( 'option' ) ).toHaveLength( 3 )
		);
	} );

	it( 'changes the options on search', async () => {
		const queriedOptions = [];
		// eslint-disable-next-line no-shadow
		const queryOptions = ( options, searchedQuery ) => {
			if ( searchedQuery === 'test' ) {
				queriedOptions.push( {
					key: 'test-option',
					label: 'Test option',
					value: { id: '4' },
				} );
			}
			return queriedOptions;
		};

		const { getByRole, getAllByRole } = render(
			<SelectControl
				isSearchable
				options={ queriedOptions }
				onSearch={ queryOptions }
				onFilter={ () => queriedOptions }
			/>
		);

		userEvent.type( getByRole( 'combobox' ), 'test' );

		// Wait for the search promise to resolve.
		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'Test option' } )
			).toBeInTheDocument()
		);

		expect( getAllByRole( 'option' ) ).toHaveLength( 1 );
	} );

	describe( 'selected value', () => {
		it( 'should return an array if array', async () => {
			const onChangeMock = jest.fn();
			const { getByRole } = render(
				<SelectControl
					isSearchable
					selected={ [ { ...options[ 0 ] } ] }
					options={ options }
					onSearch={ () => options }
					onFilter={ () => options }
					onChange={ onChangeMock }
				/>
			);

			userEvent.clear( getByRole( 'combobox' ) );
			userEvent.type( getByRole( 'combobox' ), 'test' );

			// Wait for the search promise to resolve.
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);
			userEvent.click( getByRole( 'option', { name: 'bar' } ) );
			expect( onChangeMock ).toHaveBeenCalledWith(
				[ options[ 2 ] ],
				'test'
			);
		} );

		it( 'should return key value as string if selected is string', async () => {
			const onChangeMock = jest.fn();
			const { getByRole } = render(
				<SelectControl
					isSearchable
					selected={ options[ 0 ].key }
					options={ options }
					onSearch={ () => options }
					onFilter={ () => options }
					onChange={ onChangeMock }
				/>
			);

			userEvent.clear( getByRole( 'combobox' ) );
			userEvent.type( getByRole( 'combobox' ), 'test' );

			// Wait for the search promise to resolve.
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);
			userEvent.click( getByRole( 'option', { name: 'bar' } ) );
			expect( onChangeMock ).toHaveBeenCalledWith(
				options[ 2 ].key,
				'test'
			);
		} );

		describe( 'control onChange', () => {
			it( 'should return array if selected is array and onChange triggered from control', () => {
				const onChangeMock = jest.fn();
				const { getByRole } = render(
					<SelectControl
						isSearchable
						selected={ [ { ...options[ 0 ] } ] }
						options={ options }
						onSearch={ () => options }
						onFilter={ () => options }
						onChange={ onChangeMock }
					/>
				);

				userEvent.clear( getByRole( 'combobox' ) );
				userEvent.type( getByRole( 'combobox' ), '{backspace}' );

				expect( onChangeMock ).toHaveBeenCalledWith( [], '' );
			} );

			it( 'should return string if selected is string and onChange triggered from control', () => {
				const onChangeMock = jest.fn();
				const { getByRole } = render(
					<SelectControl
						isSearchable
						selected={ options[ 0 ].key }
						options={ options }
						onSearch={ () => options }
						onFilter={ () => options }
						onChange={ onChangeMock }
					/>
				);

				userEvent.clear( getByRole( 'combobox' ) );
				userEvent.type( getByRole( 'combobox' ), '{backspace}' );

				expect( onChangeMock ).toHaveBeenCalledWith( '', '' );
			} );
		} );
	} );

	it( 'displays multiple selection not inline', async () => {
		const { getByText } = render(
			<SelectControl
				isSearchable
				options={ options }
				selected={ [ options[ 1 ] ] }
				multiple
				inlineTags={ false }
			/>
		);

		expect( getByText( options[ 1 ].label ) ).toBeInTheDocument();
	} );
} );
