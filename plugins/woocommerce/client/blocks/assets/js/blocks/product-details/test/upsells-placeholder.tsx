/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import UpsellsPlaceholder from '../upsells-placeholder';

const mockEditor = {
	editEntityRecord: jest.fn(),
	insertBlock: jest.fn(),
};

jest.mock( '@wordpress/block-editor', () => {
	const { createElement } = jest.requireActual( '@wordpress/element' );

	return {
		Warning: ( { children, actions } ) =>
			createElement( 'div', null, children, actions ),
	};
} );

jest.mock( '@wordpress/blocks', () => ( {
	createBlock: ( name, attributes ) => ( { name, attributes } ),
	createBlocksFromInnerBlocksTemplate: () => [],
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
	useDispatch: () => mockEditor,
	select: () => ( {
		getBlockRootClientId: () => 'root-client-id',
		getBlockIndex: () => 1,
		getBlockAttributes: () => ( { align: 'wide' } ),
	} ),
} ) );

const OPTION = 'woocommerce_product_upsells_migrated_to_block';

function mockSite( site: Record< string, unknown > | false, loaded = true ) {
	( useSelect as jest.Mock ).mockImplementation( ( selectSite ) =>
		selectSite( () => ( {
			getEntityRecord: () => site,
			getEditedEntityRecord: () => site,
			hasFinishedResolution: () => loaded,
		} ) )
	);
}

describe( 'UpsellsPlaceholder', () => {
	test( 'saves the migration and inserts the Upsells block', () => {
		mockSite( { [ OPTION ]: 'no' } );

		render( <UpsellsPlaceholder clientId="product-details" /> );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Switch to the Upsells block',
			} )
		);

		expect( mockEditor.editEntityRecord ).toHaveBeenCalledWith(
			'root',
			'site',
			undefined,
			{ [ OPTION ]: 'yes' }
		);
		expect( mockEditor.insertBlock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'woocommerce/product-collection',
				attributes: expect.objectContaining( {
					align: 'wide',
					collection: 'woocommerce/product-collection/upsells',
					queryContextIncludes: [ 'collection' ],
					hideControls: [ 'inherit' ],
					query: expect.objectContaining( { perPage: 5 } ),
					displayLayout: expect.objectContaining( { columns: 5 } ),
				} ),
			} ),
			2,
			'root-client-id',
			true
		);
	} );

	test( 'hides the notice once upsells are migrated or still loading', () => {
		mockSite( { [ OPTION ]: 'yes' } );
		const { rerender } = render(
			<UpsellsPlaceholder clientId="product-details" />
		);

		expect(
			screen.queryByRole( 'button', {
				name: 'Switch to the Upsells block',
			} )
		).not.toBeInTheDocument();

		mockSite( false, false );
		rerender( <UpsellsPlaceholder clientId="product-details" /> );

		expect(
			screen.queryByRole( 'button', {
				name: 'Switch to the Upsells block',
			} )
		).not.toBeInTheDocument();
	} );
} );
