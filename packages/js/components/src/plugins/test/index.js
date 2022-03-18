/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */

import { Plugins } from '../index.js';

describe( 'Rendering', () => {
	it( 'should render nothing when autoInstalling', async () => {
		const installAndActivatePlugins = jest.fn().mockResolvedValue( {
			success: true,
			data: {
				activated: [ 'jetpack' ],
			},
		} );
		const onComplete = jest.fn();

		const { queryByRole } = render(
			<Plugins
				autoInstall
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installAndActivatePlugins={ installAndActivatePlugins }
			/>
		);

		expect( queryByRole( 'button' ) ).toBeNull();
	} );

	it( 'should render a continue button when no pluginSlugs are given', async () => {
		const { getByRole } = render(
			<Plugins pluginSlugs={ [] } onComplete={ () => {} } />
		);

		expect(
			getByRole( 'button', { name: 'Continue' } )
		).toBeInTheDocument();
	} );

	it( 'should render install and no thanks buttons', async () => {
		const { getByRole } = render(
			<Plugins pluginSlugs={ [ 'jetpack' ] } onComplete={ () => {} } />
		);

		expect(
			getByRole( 'button', { name: 'Install & enable' } )
		).toBeInTheDocument();
		expect(
			getByRole( 'button', { name: 'No thanks' } )
		).toBeInTheDocument();
	} );

	it( 'should render an abort button when the abort handler is provided', async () => {
		const { getByRole, getAllByRole } = render(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ () => {} }
				onAbort={ () => {} }
			/>
		);

		expect( getAllByRole( 'button' ) ).toHaveLength( 3 );
		expect( getByRole( 'button', { name: 'Abort' } ) ).toBeInTheDocument();
	} );
} );

describe( 'Installing and activating', () => {
	it( 'should call installAndActivatePlugins and onComplete', async () => {
		const response = {
			success: true,
			data: {
				activated: [ 'jetpack' ],
			},
		};
		const installAndActivatePlugins = jest
			.fn()
			.mockResolvedValue( response );
		const onComplete = jest.fn();

		const { getByRole } = render(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installAndActivatePlugins={ installAndActivatePlugins }
			/>
		);

		userEvent.click( getByRole( 'button', { name: 'Install & enable' } ) );

		expect( installAndActivatePlugins ).toHaveBeenCalledWith( [
			'jetpack',
		] );

		await waitFor( () =>
			expect( onComplete ).toHaveBeenCalledWith( [ 'jetpack' ], response )
		);
	} );
} );

describe( 'Installing and activating errors', () => {
	it( 'should call installAndActivatePlugins and onComplete', async () => {
		const response = {
			errors: {
				'failed-plugin': [ 'error message' ],
			},
		};
		const installAndActivatePlugins = jest
			.fn()
			.mockRejectedValue( response );
		const onComplete = jest.fn();
		const onError = jest.fn();

		const { getByRole } = render(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installAndActivatePlugins={ installAndActivatePlugins }
				onError={ onError }
			/>
		);

		userEvent.click( getByRole( 'button', { name: 'Install & enable' } ) );

		expect( onComplete ).not.toHaveBeenCalled();

		await waitFor( () =>
			expect( onError ).toHaveBeenCalledWith( response.errors, response )
		);
	} );
} );
