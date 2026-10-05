import {
	afterEach,
	beforeEach,
	describe,
	expect,
	it,
	vi,
	type Mock,
} from 'vitest';

/**
 * External dependencies
 */
import * as navigation from '@woocommerce/navigation';
/**
 * Internal dependencies
 */
import { updateQueryParams, createQueryParamsListener } from '../url-handling';
vi.mock( '@woocommerce/navigation', () => {
	const mock = {
		getHistory: vi.fn(),
		getQuery: vi.fn(),
		updateQueryString: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const mockedGetQuery = navigation.getQuery as Mock;
const mockedUpdateQueryString = navigation.updateQueryString as Mock;
const mockedGetHistory = navigation.getHistory as Mock;
describe( 'updateQueryParams', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it( 'calls updateQueryString with correct changes', () => {
		mockedGetQuery.mockReturnValue( {
			sidebar: 'oldSidebar',
			content: 'oldContent',
		} );
		const changes = {
			sidebar: 'newSidebar',
			content: 'newContent',
		};
		updateQueryParams( changes );
		expect( mockedUpdateQueryString ).toHaveBeenCalledWith( changes );
	} );
	it( 'does not call updateQueryString when new values match current ones', () => {
		mockedGetQuery.mockReturnValue( {
			sidebar: 'sameSidebar',
			content: 'sameContent',
		} );
		updateQueryParams( {
			sidebar: 'sameSidebar',
			content: 'sameContent',
		} );
		expect( mockedUpdateQueryString ).not.toHaveBeenCalled();
	} );

	// note that in this test and the below tests, we use .mock.lastCall and .toStrictEqual so that
	// we can distinguish the difference between { content: 'bar', sidebar: undefined } and { content: 'bar }
	// this is important because calling it with sidebar: undefined will unset the sidebar query parameter,
	// while calling it without the sidebar key will leave the existing sidebar query parameter if any
	it( 'calls updateQueryString with both parameters as undefined if passed in', () => {
		mockedGetQuery.mockReturnValue( {
			sidebar: 'existingSidebar',
			content: 'existingContent',
		} );
		const changes = {
			sidebar: undefined,
			content: undefined,
		};
		updateQueryParams( changes );
		expect( mockedUpdateQueryString.mock.lastCall[ 0 ] ).toStrictEqual(
			changes
		);
	} );
	it( 'correctly updates with undefined if one parameter is undefined', () => {
		mockedGetQuery.mockReturnValue( {
			sidebar: 'existingSidebar',
			content: 'existingContent',
		} );
		const changes = {
			sidebar: 'newSidebar',
			content: undefined,
		};
		updateQueryParams( changes );
		expect( mockedUpdateQueryString.mock.lastCall[ 0 ] ).toStrictEqual(
			changes
		);
	} );
	it( 'only updates changes that are different', () => {
		mockedGetQuery.mockReturnValue( {
			sidebar: 'existingSidebar',
			content: 'existingContent',
		} );
		const changes = {
			sidebar: 'newSidebar',
			content: 'existingContent',
		};
		updateQueryParams( changes );
		expect( mockedUpdateQueryString.mock.lastCall[ 0 ] ).toStrictEqual( {
			sidebar: 'newSidebar',
		} );
	} );
	it( 'only updates changes that are passed in', () => {
		mockedGetQuery.mockReturnValue( {
			sidebar: 'existingSidebar',
		} );
		const changes = {
			sidebar: 'newSidebar',
		};
		updateQueryParams( changes );
		expect( mockedUpdateQueryString.mock.lastCall[ 0 ] ).toStrictEqual(
			changes
		);
	} );
} );
describe( 'createQueryParamsListener', () => {
	let unlistenMock: Mock;
	let sendBackMock: Mock;
	let historyMock: {
		listen: Mock;
		location: {
			search: string;
		};
	};
	beforeEach( () => {
		sendBackMock = vi.fn();
		unlistenMock = vi.fn();
		historyMock = {
			listen: vi.fn().mockImplementation( () => {
				return unlistenMock;
			} ),
			location: {
				search: '?test=sameValue',
			},
		};
		mockedGetHistory.mockReturnValue( historyMock );
	} );
	afterEach( () => {
		vi.clearAllMocks();
	} );
	it( 'registers and unregisters the listener', () => {
		const unlisten = createQueryParamsListener( 'test', sendBackMock );
		expect( historyMock.listen ).toHaveBeenCalled();
		unlisten();
		expect( unlistenMock ).toHaveBeenCalled();
	} );
	it( 'calls sendBack when the specified query parameter changes', () => {
		const paramName = 'test';
		createQueryParamsListener( paramName, sendBackMock );

		// Simulate a query param change
		historyMock.listen.mock.calls[ 0 ][ 0 ]( {
			action: 'POP',
			location: {
				search: `?${ paramName }=newValue`,
			},
		} );
		expect( sendBackMock ).toHaveBeenCalledWith( {
			type: 'EXTERNAL_URL_UPDATE',
		} );
	} );
	it( "does not call sendBack when the query parameter that changes isn't what we are listening to", () => {
		const paramName = 'test';
		createQueryParamsListener( paramName, sendBackMock );

		// Simulate a navigation that does not change the specific query param
		historyMock.listen.mock.calls[ 0 ][ 0 ]( {
			action: 'POP',
			location: {
				search: '?test=sameValue&anotherParam=different',
			},
		} );
		expect( sendBackMock ).not.toHaveBeenCalled();
	} );
	it( 'does not call sendBack when action is not POP', () => {
		const paramName = 'test';
		createQueryParamsListener( paramName, sendBackMock );

		// Simulate a PUSH action
		historyMock.listen.mock.calls[ 0 ][ 0 ]( {
			action: 'PUSH',
			location: {
				search: `?${ paramName }=newValue`,
			},
		} );
		expect( sendBackMock ).not.toHaveBeenCalled();
	} );
} );
