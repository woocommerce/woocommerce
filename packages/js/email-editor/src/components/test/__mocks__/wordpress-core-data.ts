jest.mock( '@wordpress/core-data', () => ( {
	createSelector: jest.fn(),
	store: {},
} ) );
