/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import {
	registerProductEntity,
	registerSettingsEntity,
} from '../register-entities';
import {
	registerProductEntity as registerProductEntityInternal,
	registerSettingsEntity as registerSettingsEntityInternal,
} from '../../entity-registration/register-entities';

jest.mock( '@wordpress/deprecated', () => jest.fn() );
jest.mock( '../../entity-registration/register-entities', () => ( {
	registerProductEntity: jest.fn(),
	registerSettingsEntity: jest.fn(),
} ) );

describe( 'deprecated entity registration helpers', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'warns once and delegates product registration', () => {
		registerProductEntity();
		registerProductEntity();

		expect( deprecated ).toHaveBeenCalledTimes( 1 );
		expect( deprecated ).toHaveBeenCalledWith( 'registerProductEntity()', {
			since: '11.1.0',
			alternative: 'automatic entity registration',
			plugin: 'WooCommerce',
			hint: 'Entities are registered automatically by the wc-entities script. Remove this call.',
		} );
		expect( registerProductEntityInternal ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'warns once and delegates settings registration', () => {
		registerSettingsEntity();
		registerSettingsEntity();

		expect( deprecated ).toHaveBeenCalledTimes( 1 );
		expect( deprecated ).toHaveBeenCalledWith( 'registerSettingsEntity()', {
			since: '11.1.0',
			alternative: 'automatic entity registration',
			plugin: 'WooCommerce',
			hint: 'Entities are registered automatically by the wc-entities script. Remove this call.',
		} );
		expect( registerSettingsEntityInternal ).toHaveBeenCalledTimes( 2 );
	} );
} );
