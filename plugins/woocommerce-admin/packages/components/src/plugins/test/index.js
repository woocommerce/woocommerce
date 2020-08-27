/**
 * External dependencies
 */
import { shallow } from 'enzyme';
import { Button } from '@wordpress/components';

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

		const pluginsWrapper = shallow(
			<Plugins
				autoInstall
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installAndActivatePlugins={ installAndActivatePlugins }
			/>
		);

		const buttons = pluginsWrapper.find( Button );
		expect( buttons.length ).toBe( 0 );
	} );

	it( 'should render a continue button when no pluginSlugs are given', async () => {
		const pluginsWrapper = shallow(
			<Plugins pluginSlugs={ [] } onComplete={ () => {} } />
		);

		const continueButton = pluginsWrapper.find( Button );
		expect( continueButton.length ).toBe( 1 );
		expect( continueButton.text() ).toBe( 'Continue' );
	} );

	it( 'should render install and no thanks buttons', async () => {
		const pluginsWrapper = shallow(
			<Plugins pluginSlugs={ [ 'jetpack' ] } onComplete={ () => {} } />
		);

		const buttons = pluginsWrapper.find( Button );
		expect( buttons.length ).toBe( 2 );
		expect( buttons.at( 0 ).text() ).toBe( 'Install & enable' );
		expect( buttons.at( 1 ).text() ).toBe( 'No thanks' );
	} );
} );

describe( 'Installing and activating', () => {
	let pluginsWrapper;
	const response = {
		success: true,
		data: {
			activated: [ 'jetpack' ],
		},
	};
	const installAndActivatePlugins = jest.fn().mockResolvedValue( response );
	const onComplete = jest.fn();

	beforeEach( () => {
		pluginsWrapper = shallow(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installAndActivatePlugins={ installAndActivatePlugins }
			/>
		);
	} );

	it( 'should call installAndActivatePlugins', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( installAndActivatePlugins ).toHaveBeenCalledWith( [
			'jetpack',
		] );
	} );

	it( 'should call the onComplete callback', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		await expect( onComplete ).toHaveBeenCalledWith(
			[ 'jetpack' ],
			response
		);
	} );
} );

describe( 'Installing and activating errors', () => {
	let pluginsWrapper;
	const response = {
		errors: {
			'failed-plugin': [ 'error message' ],
		},
	};
	const installAndActivatePlugins = jest.fn().mockRejectedValue( response );
	const onComplete = jest.fn();
	const onError = jest.fn();

	beforeEach( () => {
		pluginsWrapper = shallow(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installAndActivatePlugins={ installAndActivatePlugins }
				onError={ onError }
			/>
		);
	} );

	it( 'should not call onComplete on install error', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( onComplete ).not.toHaveBeenCalled();
	} );

	it( 'should call the onError callback', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( onError ).toHaveBeenCalledWith( response.errors, response );
	} );
} );
