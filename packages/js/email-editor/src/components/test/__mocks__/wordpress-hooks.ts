jest.mock( '@wordpress/hooks', () => ( {
	applyFilters: ( _hook: string, value: string ) => value,
} ) );
