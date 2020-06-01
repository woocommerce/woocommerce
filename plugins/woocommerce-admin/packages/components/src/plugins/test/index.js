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
	it( 'should render nothing when autoInstalling', () => {
		const pluginsWrapper = shallow(
			<Plugins
				autoInstall
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ () => {} }
			/>
		);
		const buttons = pluginsWrapper.find( Button );

		expect( buttons.length ).toBe( 0 );
	} );

	it( 'should render a continue button when no pluginSlugs are given', () => {
		const pluginsWrapper = shallow(
			<Plugins pluginSlugs={ [] } onComplete={ () => {} } />
		);
		const continueButton = pluginsWrapper.find( Button );
		expect( continueButton.length ).toBe( 1 );
		expect( continueButton.text() ).toBe( 'Continue' );
	} );

	it( 'should render install and no thanks buttons', () => {
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
	const installPlugins = jest.fn().mockReturnValue( {
		success: true,
	} );
	const activatePlugins = jest.fn().mockReturnValue( {
		success: true,
		data: {
			activated: [ 'jetpack' ],
		},
	} );
	const createNotice = jest.fn();
	const onComplete = jest.fn();

	beforeEach( () => {
		pluginsWrapper = shallow(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ onComplete }
				installPlugins={ installPlugins }
				activatePlugins={ activatePlugins }
				createNotice={ createNotice }
			/>
		);
	} );

	it( 'should call installPlugins', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( installPlugins ).toHaveBeenCalledWith( [ 'jetpack' ] );
	} );

	it( 'should call activatePlugin', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( activatePlugins ).toHaveBeenCalledWith( [ 'jetpack' ] );
	} );
	it( 'should create a success notice', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( createNotice ).toHaveBeenCalledWith(
			'success',
			'Plugins were successfully installed and activated.'
		);
	} );
	it( 'should call the onComplete callback', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( onComplete ).toHaveBeenCalledWith( [ 'jetpack' ] );
	} );
} );

describe( 'Installing and activating errors', () => {
	let pluginsWrapper;
	const errors = {
		errors: {
			'failed-plugin': [ 'error message' ],
		},
	};
	const installPlugins = jest.fn().mockReturnValue( {
		errors,
	} );
	const activatePlugins = jest.fn().mockReturnValue( {
		success: false,
	} );
	const createNotice = jest.fn();
	const onError = jest.fn();

	beforeEach( () => {
		pluginsWrapper = shallow(
			<Plugins
				pluginSlugs={ [ 'jetpack' ] }
				onComplete={ () => {} }
				installPlugins={ installPlugins }
				activatePlugins={ activatePlugins }
				createNotice={ createNotice }
				onError={ onError }
			/>
		);
	} );

	it( 'should not call activatePlugin on install error', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( activatePlugins ).not.toHaveBeenCalled();
	} );

	it( 'should create an error notice', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			errors.errors[ 'failed-plugin' ][ 0 ]
		);
	} );

	it( 'should call the onError callback', async () => {
		const installButton = pluginsWrapper.find( Button ).at( 0 );
		installButton.simulate( 'click' );

		expect( onError ).toHaveBeenCalledWith( errors );
	} );
} );
