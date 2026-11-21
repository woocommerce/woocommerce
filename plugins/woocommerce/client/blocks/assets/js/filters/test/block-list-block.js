/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { getBlockType } from '@wordpress/blocks';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { applyFilters, removeFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import '../block-list-block';

jest.mock( '@wordpress/blocks', () => ( {
	getBlockType: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	store: 'core/block-editor',
} ) );

const BlockListBlock = ( { attributes } ) => (
	<pre data-testid="attributes">{ JSON.stringify( attributes ) }</pre>
);
const BlockWithDefaults = applyFilters(
	'editor.BlockListBlock',
	BlockListBlock
);

describe( 'WooCommerce block defaults', () => {
	let registry;
	let updateBlockAttributes;
	let setAttributes;

	beforeEach( () => {
		jest.clearAllMocks();
		setAttributes = jest.fn();
		updateBlockAttributes = jest.fn( () => ( { type: 'UPDATE' } ) );
		registry = createRegistry();
		registry.registerStore( 'core/block-editor', {
			reducer: ( state = {} ) => state,
			actions: { updateBlockAttributes },
		} );
		getBlockType.mockReturnValue( {
			attributes: {
				columns: { type: 'number' },
				alignButtons: { type: 'boolean' },
				label: { type: 'string' },
				style: { type: 'object' },
			},
			defaults: { columns: 3, alignButtons: false },
		} );
	} );

	afterAll( () => {
		removeFilter(
			'editor.BlockListBlock',
			'woocommerce-blocks/block-list-block'
		);
	} );

	const renderBlock = (
		attributes = {},
		name = 'woocommerce/test-defaults'
	) =>
		render(
			<RegistryProvider value={ registry }>
				<BlockWithDefaults
					clientId="owning-block"
					block={ { name } }
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</RegistryProvider>
		);

	it( 'persists only missing defaults in the owning editor registry', () => {
		const attributes = { style: { color: { background: '#7928ca' } } };

		renderBlock( attributes );

		expect( updateBlockAttributes ).toHaveBeenCalledTimes( 1 );
		expect( updateBlockAttributes ).toHaveBeenCalledWith( 'owning-block', {
			columns: 3,
			alignButtons: false,
		} );
		expect( setAttributes ).not.toHaveBeenCalled();
		expect(
			JSON.parse( screen.getByTestId( 'attributes' ).textContent )
		).toEqual( { ...attributes, columns: 3, alignButtons: false } );
		expect( attributes ).toEqual( {
			style: { color: { background: '#7928ca' } },
		} );
	} );

	it( 'keeps existing attributes out of the defaults write', () => {
		renderBlock( { columns: 5, label: 'Existing label' } );

		expect( updateBlockAttributes ).toHaveBeenCalledWith( 'owning-block', {
			alignButtons: false,
		} );
	} );

	it.each( [ false, 0, '', null ] )(
		'preserves an explicitly supplied value of %p',
		( value ) => {
			getBlockType.mockReturnValue( {
				attributes: { setting: {} },
				defaults: { setting: 'default' },
			} );

			renderBlock( { setting: value } );

			expect( updateBlockAttributes ).not.toHaveBeenCalled();
			expect( setAttributes ).not.toHaveBeenCalled();
			expect(
				JSON.parse( screen.getByTestId( 'attributes' ).textContent )
			).toEqual( { setting: value } );
		}
	);

	it( 'does not write when all defaults are already present', () => {
		renderBlock( { columns: 4, alignButtons: true } );

		expect( updateBlockAttributes ).not.toHaveBeenCalled();
		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'does not rewrite colors when a block without defaults remounts', () => {
		getBlockType.mockReturnValue( { attributes: { backgroundColor: {} } } );
		const attributes = { backgroundColor: 'accent-3' };
		const { unmount } = renderBlock( attributes );
		unmount();

		renderBlock( attributes );

		expect( updateBlockAttributes ).not.toHaveBeenCalled();
		expect( setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'ignores undefined defaults and defaults outside the attribute schema', () => {
		getBlockType.mockReturnValue( {
			attributes: { columns: {}, label: {} },
			defaults: { columns: 3, label: undefined, unknown: 'ignored' },
		} );

		renderBlock();

		expect( updateBlockAttributes ).toHaveBeenCalledWith( 'owning-block', {
			columns: 3,
		} );
	} );

	it( 'does not apply WooCommerce defaults to other blocks', () => {
		renderBlock( { label: 'Paragraph' }, 'core/paragraph' );

		expect( updateBlockAttributes ).not.toHaveBeenCalled();
		expect( setAttributes ).not.toHaveBeenCalled();
	} );
} );
