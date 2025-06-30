/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectControl } from '../index';
import { Option, Selected } from '../types';

describe( 'SelectControl', () => {
	const query = 'lorem';
	const options: Option[] = [
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

	it( "returns Türkiye when searching for Türkiye when ignoreDiacritics is true ", async () => {
		const options: Option[] = [
			{ key: '1', label: 'One', value: { id: 'one' } },
			{ key: '2', label: 'Two', value: { id: 'two' } },
			{ key: '3', label: 'Türkiye', value: { id: 'türkiye' } },
		];

		const { getByRole } = render(
			<SelectControl ignoreDiacritics={ true } isSearchable options={ options } />
		);

		userEvent.type( getByRole( 'combobox' ), 'Türkiye' );

		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'Türkiye' } )
			).toBeInTheDocument()
		);
	} );

	it( "returns Türkiye when searching for Turkiye when ignoreDiacritics is true", async () => {
		const options: Option[] = [
			{ key: '1', label: 'One', value: { id: 'one' } },
			{ key: '2', label: 'Two', value: { id: 'two' } },
			{ key: '3', label: 'Türkiye', value: { id: 'türkiye' } },
		];

		const { getByRole } = render(
			<SelectControl ignoreDiacritics={ true } isSearchable options={ options } />
		);

		userEvent.type( getByRole( 'combobox' ), 'Turkiye' );

		await waitFor( () =>
			expect(
				getByRole( 'option', { name: 'Türkiye' } )
			).toBeInTheDocument()
		);
	} );

	it( "does not return Türkiye when searching for Turkiye when ignoreDiacritics is false", async () => {
		const options: Option[] = [
			{ key: '1', label: 'One', value: { id: 'one' } },
			{ key: '2', label: 'Two', value: { id: 'two' } },
			{ key: '3', label: 'Türkiye', value: { id: 'türkiye' } },
		];

		const { getByRole, queryByRole } = render(
			<SelectControl ignoreDiacritics={ false } isSearchable options={ options } />
		);

		userEvent.type( getByRole( 'combobox' ), 'Turkiye' );

		await waitFor(() => {
			expect( queryByRole( 'option', { name: 'Türkiye' } ) ).toBeNull();
		});

	} );

	it( "doesn't return matching excluded elements", async () => {
		const { getByRole, queryByRole } = render(
			<SelectControl
				isSearchable
				options={ options }
				selected={ [ options[ 1 ] ] as Selected }
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
		const queriedOptions: Option[] = [];
		// eslint-disable-next-line @typescript-eslint/no-shadow
		const queryOptions = async (
			options: Option[],
			searchedQuery: string | null
		) => {
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

	it( 'should not automatically select first option on focus', async () => {
		const onChangeMock = jest.fn();
		const { getByRole, queryByRole } = render(
			<SelectControl
				isSearchable
				showAllOnFocus
				options={ options }
				onSearch={ async () => options }
				onFilter={ () => options }
				onChange={ onChangeMock }
			/>
		);
		getByRole( 'combobox' ).focus();
		await waitFor( () =>
			expect( getByRole( 'option', { name: 'bar' } ) ).toBeInTheDocument()
		);
		expect( queryByRole( 'option', { selected: true } ) ).toBeNull();
	} );

	describe( 'virtual scrolling', () => {
		const manyOptions: Option[] = Array.from(
			{ length: 100 },
			( _, i ) => ( {
				key: `${ i }`,
				label: `Option ${ i }`,
				value: { id: `${ i }` },
			} )
		);

		it( 'renders with virtualScroll enabled', async () => {
			const { getByRole, queryByRole } = render(
				<SelectControl options={ manyOptions } virtualScroll />
			);

			userEvent.click( getByRole( 'combobox' ) );
			await waitFor( () => {
				expect(
					getByRole( 'option', { name: 'Option 0' } )
				).toBeInTheDocument();
			} );

			// Only a subset of options should be in the DOM due to virtualization
			expect( queryByRole( 'option', { name: 'Option 99' } ) ).toBeNull();
		} );

		it( 'allows searching with virtualScroll enabled', async () => {
			const { getByRole, queryByRole } = render(
				<SelectControl
					options={ manyOptions }
					virtualScroll
					isSearchable
				/>
			);

			userEvent.click( getByRole( 'combobox' ) );
			userEvent.type( getByRole( 'combobox' ), '99' );
			await waitFor( () => {
				expect(
					getByRole( 'option', { name: 'Option 99' } )
				).toBeInTheDocument();
			} );

			// Only Option 99 should be visible
			expect( queryByRole( 'option', { name: 'Option 0' } ) ).toBeNull();
		} );

		it( 'allows selecting options using keyboard with virtualScroll enabled', async () => {
			const onChangeMock = jest.fn();
			const { getByRole } = render(
				<SelectControl
					options={ manyOptions }
					virtualScroll
					onChange={ onChangeMock }
				/>
			);

			getByRole( 'combobox' ).focus();
			userEvent.click( getByRole( 'combobox' ) );
			await waitFor( () => {
				expect(
					getByRole( 'option', { name: 'Option 0' } )
				).toBeInTheDocument();
			} );

			// Navigate and select Option 2
			userEvent.type(
				getByRole( 'combobox' ),
				'{arrowdown}{arrowdown}{arrowdown}'
			);
			userEvent.type( getByRole( 'combobox' ), '{enter}' );

			expect( onChangeMock ).toHaveBeenCalledWith(
				[ { key: '2', label: 'Option 2', value: { id: '2' } } ],
				''
			);
		} );
	} );

	describe( 'selected value', () => {
		it( 'should return an array if array', async () => {
			const onChangeMock = jest.fn();
			const { getByRole } = render(
				<SelectControl
					isSearchable
					selected={ [ { ...options[ 0 ] } ] as Selected }
					options={ options }
					onSearch={ async () => options }
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
					onSearch={ async () => options }
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

		describe( 'prop excludeSelectedOptions', () => {
			it( 'should preserve selected option when focused', async () => {
				const onChangeMock = jest.fn();
				const { getByRole } = render(
					<SelectControl
						isSearchable
						showAllOnFocus
						selected={ options[ 2 ].key }
						options={ options }
						onSearch={ async () => options }
						onFilter={ () => options }
						onChange={ onChangeMock }
						excludeSelectedOptions={ false }
					/>
				);
				getByRole( 'combobox' ).focus();
				await waitFor( () =>
					expect(
						getByRole( 'option', { name: 'bar' } )
					).toBeInTheDocument()
				);
				// In browser, the <Button> in <List> component is automatically "selected" when <Control> lost focus.
				// I was not able to produce the same behaviour with unit test, but a click on the currently
				// selected option should be sufficient to simulate the logic in this test.
				userEvent.click( getByRole( 'option', { selected: true } ) );
				expect( onChangeMock ).toHaveBeenCalledTimes( 0 );
			} );

			it( 'should reset selected option if searching', async () => {
				const onChangeMock = jest.fn();
				const { getByRole, queryByRole } = render(
					<SelectControl
						isSearchable
						showAllOnFocus
						selected={ options[ 2 ].key }
						options={ options }
						onSearch={ async () => options }
						onFilter={ () => options }
						onChange={ onChangeMock }
						excludeSelectedOptions={ false }
					/>
				);

				getByRole( 'combobox' ).focus();
				await waitFor( () =>
					expect(
						getByRole( 'option', { name: 'bar' } )
					).toBeInTheDocument()
				);

				userEvent.click( getByRole( 'option', { name: 'bar' } ) );
				userEvent.clear( getByRole( 'combobox' ) );
				userEvent.type( getByRole( 'combobox' ), 'bar' );

				await waitFor( () =>
					expect(
						getByRole( 'option', { name: 'bar' } )
					).toBeInTheDocument()
				);

				expect(
					queryByRole( 'option', { selected: true } )
				).toBeNull();
			} );
		} );

		it( 'disables the component', async () => {
			const { getByRole } = render(
				<SelectControl disabled options={ options } />
			);

			await waitFor( () =>
				expect( getByRole( 'combobox' ) ).toBeDisabled()
			);
		} );

		describe( 'control onChange', () => {
			it( 'should return array if selected is array and onChange triggered from control', () => {
				const onChangeMock = jest.fn();
				const { getByRole } = render(
					<SelectControl
						isSearchable
						selected={ [ { ...options[ 0 ] } ] as Selected }
						options={ options }
						onSearch={ async () => options }
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
						onSearch={ async () => options }
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
				selected={ [ options[ 1 ] ] as Selected }
				multiple
				inlineTags={ false }
			/>
		);

		expect( getByText( options[ 1 ].label as string ) ).toBeInTheDocument();
	} );

	describe( 'keyboard interaction', () => {
		it( 'pressing keydown on combobox should highlight first option', async () => {
			const { getByRole } = render(
				<SelectControl
					options={ options }
					onSearch={ async () => options }
					onFilter={ () => options }
				/>
			);

			getByRole( 'combobox' ).focus();
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);

			userEvent.type( getByRole( 'combobox' ), '{arrowdown}' );

			expect(
				getByRole( 'option', { selected: true } ).textContent
			).toEqual( options[ 0 ].label );
		} );

		it( 'pressing keydown on combobox with selected should highlight the next option', async () => {
			const { getByRole } = render(
				<SelectControl
					options={ options }
					onSearch={ async () => options }
					onFilter={ () => options }
					selected={ options[ 1 ].key }
					excludeSelectedOptions={ false }
				/>
			);

			getByRole( 'combobox' ).focus();
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);

			userEvent.type( getByRole( 'combobox' ), '{arrowdown}' );

			expect(
				getByRole( 'option', { selected: true } ).textContent
			).toEqual( options[ 2 ].label );
		} );

		it( 'pressing keydown on combobox with selected last option should rotate to first option', async () => {
			const { getByRole } = render(
				<SelectControl
					options={ options }
					onSearch={ async () => options }
					onFilter={ () => options }
					selected={ options[ options.length - 1 ].key }
					excludeSelectedOptions={ false }
				/>
			);

			getByRole( 'combobox' ).focus();
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);

			userEvent.type( getByRole( 'combobox' ), '{arrowdown}' );

			expect(
				getByRole( 'option', { selected: true } ).textContent
			).toEqual( options[ 0 ].label );
		} );

		it( 'pressing keyup on combobox should highlight last option', async () => {
			const { getByRole } = render(
				<SelectControl
					options={ options }
					onSearch={ async () => options }
					onFilter={ () => options }
				/>
			);

			getByRole( 'combobox' ).focus();
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);

			userEvent.type( getByRole( 'combobox' ), '{arrowup}' );

			expect(
				getByRole( 'option', { selected: true } ).textContent
			).toEqual( options[ options.length - 1 ].label );
		} );

		it( 'pressing tab on combobox should hide option list', async () => {
			const { getByRole, queryByRole } = render(
				<SelectControl
					options={ options }
					onSearch={ async () => options }
					onFilter={ () => options }
				/>
			);

			getByRole( 'combobox' ).focus();
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);

			userEvent.type( getByRole( 'combobox' ), '{tab}' );

			expect( queryByRole( 'option', { selected: true } ) ).toBeNull();
		} );

		it( 'pressing enter should select highlighted option', async () => {
			const onChangeMock = jest.fn();
			const { getByRole } = render(
				<SelectControl
					options={ options }
					onSearch={ async () => options }
					onFilter={ () => options }
					excludeSelectedOptions={ false }
					onChange={ onChangeMock }
				/>
			);

			getByRole( 'combobox' ).focus();
			await waitFor( () =>
				expect(
					getByRole( 'option', { name: 'bar' } )
				).toBeInTheDocument()
			);

			userEvent.type( getByRole( 'combobox' ), '{arrowdown}{enter}' );

			expect( onChangeMock ).toHaveBeenCalled();
		} );
	} );
} );
