jest.mock( '@wordpress/preferences', () => ( {
	combineReducers: jest.fn(),
} ) );
