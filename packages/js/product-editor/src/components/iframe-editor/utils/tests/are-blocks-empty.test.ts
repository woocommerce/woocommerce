/**
 * Internal dependencies
 */
import { areBlocksEmpty } from '../are-blocks-empty';

describe( 'areBlocksEmpty', () => {
	it( 'should return false if there is more than one block', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: [],
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
			{
				name: 'core/paragraph',
				attributes: [],
				innerBlocks: [],
				isValid: true,
				clientId: '2',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( false );
	} );

	it( 'should return false if there is one block that is not a paragraph block', () => {
		const blocks = [
			{
				name: 'core/heading',
				attributes: [],
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( false );
	} );

	it( 'should return false if the paragraph block has content', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: 'Some content',
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( false );
	} );

	it( 'should return false if the paragraph block has no content but a backgroundColor', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: '',
					backgroundColor: 'red',
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( false );
	} );

	it( 'should return false if the paragraph block has no content but any attribute', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					foo: 'bar',
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( false );
	} );

	it( 'should return true if an empty block list is passed', () => {
		expect( areBlocksEmpty( [] ) ).toBe( true );
	} );

	it( 'should return true if a null block list is passed', () => {
		expect( areBlocksEmpty( null ) ).toBe( true );
	} );

	it( 'should return true if an undefined block list is passed', () => {
		expect( areBlocksEmpty( undefined ) ).toBe( true );
	} );

	it( 'should return true if the paragraph block has no content or attributes', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: [],
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( true );
	} );

	it( 'should return true if the paragraph block has empty content', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: '',
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( true );
	} );

	it( 'should return true if the paragraph block has empty content (after trimming)', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: '     ',
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( true );
	} );

	it( 'should return true if the paragraph block has no content and an undefined backgroundColor', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: '',
					backgroundColor: undefined,
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( true );
	} );

	it( 'should return true if the paragraph block has no content and dropCap set true', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: '',
					dropCap: true,
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( true );
	} );

	it( 'should return true if the paragraph block has no content and dropCap set false', () => {
		const blocks = [
			{
				name: 'core/paragraph',
				attributes: {
					content: '',
					dropCap: false,
				},
				innerBlocks: [],
				isValid: true,
				clientId: '1',
			},
		];

		expect( areBlocksEmpty( blocks ) ).toBe( true );
	} );
} );
