/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { initializeEditor } from '../../../../../tests/integration/helpers/integration-test-editor';
import blockJson from '../block.json';
import '../';

// Mock settings
jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( () => ( {
		productId: '123',
		images: [
			{
				id: 1,
				src: 'test-image-1.jpg',
				thumbnail: 'test-thumb-1.jpg',
				alt: 'Test 1',
			},
		],
	} ) ),
} ) );

async function setup() {
	const testBlock = [
		{
			name: blockJson.name,
			attributes: {
				hoverZoom: true,
				fullScreenOnClick: true,
			},
		},
	];
	return initializeEditor( testBlock );
}

describe( 'Product Gallery Block', () => {
	it( 'should render the block with default attributes', async () => {
		await setup();
		const block = screen.getByLabelText( /Block: Product Gallery/i );
		expect( block ).toBeInTheDocument();
		expect( block ).toHaveAttribute( 'data-hover-zoom', 'true' );
		expect( block ).toHaveAttribute( 'data-full-screen-on-click', 'true' );
	} );
} );
