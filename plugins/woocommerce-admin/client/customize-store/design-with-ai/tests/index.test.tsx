/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { AnyInterpreter } from 'xstate';

/**
 * Internal dependencies
 */

import {
	FlowType,
	customizeStoreStateMachineContext,
} from '~/customize-store/types';
import * as utils from '~/customize-store/utils';
import { DesignWithAi } from '../';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/ai', () => ( {
	__experimentalRequestJetpackToken: jest.fn(),
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock(
	'@wordpress/edit-site/build-module/components/global-styles/global-styles-provider',
	() => ( {
		mergeBaseAndUserConfigs: jest.fn(),
	} )
);

jest.mock( '~/customize-store/utils', () => ( {
	navigateOrParent: jest.fn(),
} ) );

const parentMachine = {} as AnyInterpreter;
const parentContext = {} as customizeStoreStateMachineContext;

describe( 'DesignWithAi state machine', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should run when the flow is AI related - AI is online', async () => {
		const navigateOrParent = jest.spyOn( utils, 'navigateOrParent' );

		render(
			<DesignWithAi
				parentMachine={ parentMachine }
				context={ { ...parentContext, flowType: FlowType.AIOnline } }
				sendEvent={ jest.fn() }
				currentState={ 'designWithAi' }
			/>
		);

		expect( navigateOrParent ).not.toHaveBeenCalled();
	} );

	it( 'should run when the flow is AI related - AI is offline', async () => {
		const navigateOrParent = jest.spyOn( utils, 'navigateOrParent' );

		render(
			<DesignWithAi
				parentMachine={ parentMachine }
				context={ { ...parentContext, flowType: FlowType.AIOffline } }
				sendEvent={ jest.fn() }
				currentState={ 'designWithAi' }
			/>
		);

		expect( navigateOrParent ).not.toHaveBeenCalled();
	} );

	it( 'should not run when the flow is no AI related', async () => {
		const navigateOrParent = jest.spyOn( utils, 'navigateOrParent' );

		render(
			<DesignWithAi
				parentMachine={ parentMachine }
				context={ { ...parentContext, flowType: FlowType.noAI } }
				sendEvent={ jest.fn() }
				currentState={ 'designWithAi' }
			/>
		);

		expect( navigateOrParent ).toHaveBeenCalled();
	} );
} );
