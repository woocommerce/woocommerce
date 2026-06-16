const mockRender = jest.fn();
const mockCreateRoot = jest.fn( () => ( {
	render: mockRender,
} ) );

jest.mock( '@wordpress/element', () => ( {
	createRoot: mockCreateRoot,
} ) );

jest.mock( '../app', () => ( {
	MultiCurrencySettingsApp: () => <div>Multi-currency settings app</div>,
} ) );

describe( 'multi-currency-settings entrypoint', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		jest.resetModules();
		document.body.innerHTML = '';
	} );

	it( 'mounts into the multi-currency settings container', () => {
		const container = document.createElement( 'div' );
		container.id = 'wcpay_multi_currency_settings_container';
		document.body.appendChild( container );

		jest.isolateModules( () => {
			require( '../index' );
		} );

		expect( mockCreateRoot ).toHaveBeenCalledWith( container );
		expect( mockRender ).toHaveBeenCalled();
	} );

	it( 'does not mount when the settings container is missing', () => {
		jest.isolateModules( () => {
			require( '../index' );
		} );

		expect( mockCreateRoot ).not.toHaveBeenCalled();
		expect( mockRender ).not.toHaveBeenCalled();
	} );
} );
