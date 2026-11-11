/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { BlockInstance } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { UpsellsDowngradeNotice } from '../edit/inspector-controls/upsells-downgrade-notice';

const mockEditor = {
	editEntityRecord: jest.fn(),
	removeBlock: jest.fn(),
	replaceBlock: jest.fn(),
};

const mockBlockEditor = {
	getBlocks: jest.fn( (): Partial< BlockInstance >[] => [] ),
	getBlockAttributes: jest.fn( () => ( {} ) ),
	getBlock: jest.fn( (): { clientId: string } | null => null ),
	canRemoveBlock: jest.fn( (): boolean => true ),
};

jest.mock( '@wordpress/block-editor', () => ( {
	store: 'core/block-editor',
} ) );

jest.mock( '@wordpress/blocks', () => ( {
	createBlock: ( name: string, attributes: Record< string, unknown > ) => ( {
		name,
		attributes,
	} ),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: () => mockEditor,
	useSelect: jest.fn(),
	select: () => mockBlockEditor,
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

describe( 'UpsellsDowngradeNotice', () => {
	beforeEach( () => {
		mockEditor.editEntityRecord.mockClear();
		mockEditor.removeBlock.mockClear();
		mockEditor.replaceBlock.mockClear();
		mockBlockEditor.getBlocks.mockReset();
		mockBlockEditor.getBlockAttributes.mockReset();
		mockBlockEditor.getBlockAttributes.mockReturnValue( {} );
		mockBlockEditor.getBlock.mockReset();
		mockBlockEditor.getBlock.mockReturnValue( null );
		mockBlockEditor.canRemoveBlock.mockReset();
		mockBlockEditor.canRemoveBlock.mockReturnValue( true );
		mockSite( { [ OPTION ]: 'yes' } );
	} );

	test( 'stages the classic display and removes the Upsells block when Product Details is present', () => {
		mockBlockEditor.getBlocks.mockReturnValue( [
			{
				name: 'core/group',
				innerBlocks: [
					{
						name: 'woocommerce/product-details',
						innerBlocks: [],
					},
				] as BlockInstance[],
			},
		] );

		render( <UpsellsDowngradeNotice clientId="upsells-block" /> );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Switch back',
			} )
		);

		expect( mockEditor.removeBlock ).toHaveBeenCalledWith(
			'upsells-block'
		);
		expect( mockEditor.editEntityRecord ).toHaveBeenCalledWith(
			'root',
			'site',
			undefined,
			{ [ OPTION ]: 'no' }
		);
		expect( mockEditor.replaceBlock ).not.toHaveBeenCalled();
	} );

	test( 'replaces the Upsells block with Product Details when the template has none', () => {
		mockBlockEditor.getBlocks.mockReturnValue( [
			{
				name: 'woocommerce/product-collection',
				innerBlocks: [],
			},
		] );
		mockBlockEditor.getBlockAttributes.mockReturnValue( { align: 'full' } );

		render( <UpsellsDowngradeNotice clientId="upsells-block" /> );
		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Switch back',
			} )
		);

		expect( mockEditor.replaceBlock ).toHaveBeenCalledWith(
			'upsells-block',
			{
				name: 'woocommerce/product-details',
				attributes: { align: 'full' },
			}
		);
		expect( mockEditor.editEntityRecord ).toHaveBeenCalledWith(
			'root',
			'site',
			undefined,
			{ [ OPTION ]: 'no' }
		);
		expect( mockEditor.removeBlock ).not.toHaveBeenCalled();
	} );

	test( 'hides the notice until upsells are migrated', () => {
		mockSite( { [ OPTION ]: 'no' } );
		const { rerender } = render(
			<UpsellsDowngradeNotice clientId="upsells-block" />
		);

		expect(
			screen.queryByRole( 'button', { name: 'Switch back' } )
		).not.toBeInTheDocument();

		mockSite( false, false );
		rerender( <UpsellsDowngradeNotice clientId="upsells-block" /> );

		expect(
			screen.queryByRole( 'button', { name: 'Switch back' } )
		).not.toBeInTheDocument();
	} );
} );
