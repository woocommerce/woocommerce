/**
 * External dependencies
 */
import {
	getBlockType,
	registerBlockType,
	unregisterBlockType,
} from '@wordpress/blocks';
import { applyFilters, addFilter, removeAllFilters } from '@wordpress/hooks';
import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';

/**
 * Internal dependencies
 */
import {
	restoreAllModifiedBlockSettings,
	updateBlockSettings,
} from '../block-config';
import { addFilterForEmail, clearEmailFilters } from '../filters';
import {
	registerFormatForEmail,
	resetFormats,
	unregisterFormatForEmail,
} from '../formats';

jest.mock( '@wordpress/blocks', () => ( {
	getBlockType: jest.fn(),
	registerBlockType: jest.fn(),
	unregisterBlockType: jest.fn(),
} ) );
jest.mock( '@wordpress/rich-text', () => ( {
	registerFormatType: jest.fn(),
	unregisterFormatType: jest.fn(),
} ) );

describe( 'email editor config tools', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'updateBlockSettings', () => {
		afterEach( () => restoreAllModifiedBlockSettings() );

		it( 'returns false for a block that is not registered', () => {
			( getBlockType as jest.Mock ).mockReturnValue( undefined );

			expect( updateBlockSettings( 'core/missing', () => ( {} ) ) ).toBe(
				false
			);
			expect( registerBlockType ).not.toHaveBeenCalled();
		} );

		it( 'restores the settings the block had before the first update', () => {
			( getBlockType as jest.Mock )
				.mockReturnValueOnce( {
					name: 'core/quote',
					title: 'Original',
					category: 'text',
				} )
				.mockReturnValueOnce( {
					name: 'core/quote',
					title: 'First',
					category: 'text',
				} );

			expect(
				updateBlockSettings( 'core/quote', () => ( {
					title: 'First',
				} ) )
			).toBe( true );
			expect( registerBlockType ).toHaveBeenCalledWith( 'core/quote', {
				title: 'First',
				category: 'text',
			} );
			updateBlockSettings( 'core/quote', () => ( { title: 'Second' } ) );
			( registerBlockType as jest.Mock ).mockClear();

			expect( restoreAllModifiedBlockSettings() ).toEqual( [
				'core/quote',
			] );
			expect( unregisterBlockType ).toHaveBeenCalledWith( 'core/quote' );
			expect( registerBlockType ).toHaveBeenCalledWith( 'core/quote', {
				title: 'Original',
				category: 'text',
			} );
			expect( restoreAllModifiedBlockSettings() ).toEqual( [] );
		} );

		it( 'returns false and logs when the block cannot be registered again', () => {
			const error = jest.spyOn( console, 'error' ).mockImplementation();
			( getBlockType as jest.Mock ).mockReturnValue( {
				name: 'core/quote',
			} );
			( registerBlockType as jest.Mock ).mockImplementationOnce( () => {
				throw new Error( 'invalid settings' );
			} );

			expect( updateBlockSettings( 'core/quote', () => ( {} ) ) ).toBe(
				false
			);
			expect( error ).toHaveBeenCalled();
			error.mockRestore();
		} );
	} );

	describe( 'clearEmailFilters', () => {
		afterEach( () => removeAllFilters( 'test_hook' ) );

		it( 'removes only the filters added for the email editor', () => {
			addFilter(
				'test_hook',
				'site/plugin',
				( value: string ) => value + '-site'
			);
			addFilterForEmail(
				'test_hook',
				'email/editor',
				( value: string ) => value + '-email'
			);

			expect( applyFilters( 'test_hook', 'x' ) ).toBe( 'x-site-email' );
			clearEmailFilters();
			expect( applyFilters( 'test_hook', 'x' ) ).toBe( 'x-site' );
		} );
	} );

	describe( 'resetFormats', () => {
		it( 'removes formats the email editor added and brings back the ones it removed', () => {
			const bold = { name: 'core/bold', tagName: 'strong' };
			( unregisterFormatType as jest.Mock ).mockReturnValueOnce( bold );
			unregisterFormatForEmail( 'core/bold' );
			registerFormatForEmail( 'email/tag', {
				name: 'email/tag',
				tagName: 'span',
				interactive: false,
				title: 'Tag',
				edit: null,
			} );
			jest.clearAllMocks();

			resetFormats();

			expect( unregisterFormatType ).toHaveBeenCalledWith( 'email/tag' );
			expect( registerFormatType ).toHaveBeenCalledWith( 'core/bold', {
				object: false,
				...bold,
			} );
			jest.clearAllMocks();
			resetFormats();
			expect( registerFormatType ).not.toHaveBeenCalled();
		} );
	} );
} );
