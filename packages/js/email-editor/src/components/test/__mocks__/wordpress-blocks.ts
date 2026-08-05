jest.mock( '@wordpress/blocks', () => ( {
	serialize: jest.fn(),
	parse: jest.fn(),
} ) );
