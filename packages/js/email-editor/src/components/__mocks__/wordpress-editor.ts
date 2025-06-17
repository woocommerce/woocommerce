jest.mock( '@wordpress/editor', () => ( {
	useEntitiesSavedStatesIsDirty: jest.fn(),
	store: {},
} ) );
