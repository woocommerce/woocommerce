/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import React from 'react';

/**
 * Internal dependencies
 */
import { PluginCard } from '../plugin-card';

describe( 'PluginCard', () => {
	it( 'should trigger onChange when title and description is clicked', () => {
		const onChange = jest.fn();
		const { queryByText } = render(
			<PluginCard
				plugin={ {
					is_activated: false,
					image_url: '',
					key: 'plugin-key',
					label: 'Plugin title',
					description: 'Plugin description',
				} }
				onChange={ onChange }
				checked={ false }
			/>
		);
		const title = queryByText( 'Plugin title' );
		const description = queryByText( 'Plugin description' );
		expect( title ).toBeInTheDocument();
		expect( description ).toBeInTheDocument();
		if ( title ) fireEvent.click( title );
		if ( description ) fireEvent.click( description );
		expect( onChange ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'should not trigger onChange and checkbox when it is disabled', () => {
		const onChange = jest.fn();
		const { queryByRole, queryByText } = render(
			<PluginCard
				plugin={ {
					is_activated: false,
					image_url: '',
					key: 'plugin-key',
					label: 'Plugin title',
					description: 'Plugin description',
				} }
				onChange={ onChange }
				checked={ false }
				disabled={ true }
			/>
		);
		const checkbox = queryByRole( 'checkbox' );
		expect( checkbox ).toBeDisabled();
		const title = queryByText( 'Plugin title' );
		const description = queryByText( 'Plugin description' );
		if ( title ) fireEvent.click( title );
		if ( description ) fireEvent.click( description );
		expect( onChange ).not.toHaveBeenCalled();
	} );
} );
