jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: jest.fn( ( _hook: string, value: unknown ) => value ),
} ) );
