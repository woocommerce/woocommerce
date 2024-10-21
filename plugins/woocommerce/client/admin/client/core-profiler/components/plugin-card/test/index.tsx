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
				installed={ false }
				onChange={ onChange }
				checked={ false }
				icon={ null }
				title={ 'Plugin title' }
				description={ 'Plugin description' }
				learnMoreLink={ null }
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
} );
