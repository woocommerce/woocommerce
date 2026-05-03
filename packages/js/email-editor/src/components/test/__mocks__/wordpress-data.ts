jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
	dispatch: jest.fn( () => ( {
		registerEntityAction: jest.fn(),
		unregisterEntityAction: jest.fn(),
	} ) ),
	use: jest.fn(),
	useDispatch: jest.fn(),
	useSelect: jest.fn(),
	createRegistrySelector: jest.fn(),
} ) );
