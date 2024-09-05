/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */

import { Plugins } from '../index';

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useDispatch: jest
		.fn()
		.mockReturnValue( { installAndActivatePlugins: jest.fn() } ),
	useSelect: jest.fn().mockReturnValue( false ),
} ) );

describe( 'Rendering', () => {
	afterAll( () => {
		jest.restoreAllMocks();
	} );

	it( 'should render nothing when autoInstalling', async () => {
		const { installAndActivatePlugins } = useDispatch();

		installAndActivatePlugins.mockResolvedValue( {
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
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ () => {} }
				onSkip={ () => {} }
			/>
		);

		expect(
			getByRole( 'button', { name: 'Install & enable' } )
		).toBeInTheDocument();
		expect(
			getByRole( 'button', { name: 'No thanks' } )
		).toBeInTheDocument();
	} );

	it( 'should not render no thanks when onSkip handler is not provided', async () => {
		const { getByRole, queryByText } = render(
			<Plugins pluginSlugs={ [ 'jetpack' ] } onComplete={ () => {} } />
		);

		expect(
			getByRole( 'button', { name: 'Install & enable' } )
		).toBeInTheDocument();
		expect( queryByText( 'No thanks' ) ).not.toBeInTheDocument();
	} );

	it( 'should render an abort button when the abort handler is provided', async () => {
		const { getByRole, getAllByRole } = render(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ () => {} }
				onAbort={ () => {} }
			/>
		);

		expect( getAllByRole( 'button' ) ).toHaveLength( 2 );
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
		const onComplete = jest.fn();

		const { getByRole } = render(
			<Plugins pluginSlugs={ [ 'jetpack' ] } onComplete={ onComplete } />
		);

		userEvent.click( getByRole( 'button', { name: 'Install & enable' } ) );

		// Get the mocked installAndActivatePlugins function.
		const { installAndActivatePlugins } = useDispatch();
		installAndActivatePlugins.mockResolvedValue( response );

		expect( installAndActivatePlugins ).toHaveBeenCalledWith( [
			'jetpack',
		] );

		await waitFor( () =>
			expect( onComplete ).toHaveBeenCalledWith( [ 'jetpack' ], response )
		);
	} );
} );

describe( 'Installing and activating errors', () => {
	it( 'should call installAndActivatePlugins and onError', async () => {
		const response = {
			errors: {
				'failed-plugin': [ 'error message' ],
			},
		};

		// Get the mocked installAndActivatePlugins function.
		const { installAndActivatePlugins } = useDispatch();
		installAndActivatePlugins.mockRejectedValue( response );

		const onComplete = jest.fn();
		const onError = jest.fn();

		const { getByRole } = render(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
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
