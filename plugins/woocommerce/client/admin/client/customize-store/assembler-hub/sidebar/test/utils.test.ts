/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { findPatternByBlock } from '../utils';
import { PatternWithBlocks } from '~/customize-store/types/pattern';

describe( 'findPatternByBlock', () => {
	// Sample patterns and blocks for testing
	const pattern1: PatternWithBlocks = {
		blockTypes: [ 'type1' ],
		categories: [ 'category1' ],
		content: 'content1',
		name: 'pattern1',
		source: 'source1',
		title: 'title1',
		blocks: [
			{
				attributes: {
					attr1: 'value1',
					attr2: 'value2',
					className: 'class1',
				},
				clientId: 'clientId1',
				innerBlocks: [],
				isValid: true,
				name: 'block1',
				originalContent: 'originalContent1',
			},
		],
	};

	const pattern2: PatternWithBlocks = {
		blockTypes: [ 'type2' ],
		categories: [ 'category2' ],
		content: 'content2',
		name: 'pattern2',
		source: 'source2',
		title: 'title2',
		blocks: [
			{
				attributes: {
					attr1: 'value1',
					attr2: 'value2',
					className: 'class2',
				},
				clientId: 'clientId2',
				innerBlocks: [],
				isValid: true,
				name: 'block2',
				originalContent: 'originalContent2',
			},
		],
	};

	const patterns: PatternWithBlocks[] = [ pattern1, pattern2 ];

	it( 'should find a pattern by block attributes', () => {
		const blockToFind: BlockInstance = {
			attributes: {
				attr1: 'value1',
				attr2: 'value2',
				className: 'class1 preview-opacity', // Include the preview opacity class
			},
			clientId: 'clientId1',
			innerBlocks: [],
			isValid: true,
			name: 'block1',
			originalContent: 'originalContent1',
		};

		const result = findPatternByBlock( patterns, blockToFind );

		expect( result ).toEqual( pattern1 );
	} );

	it( 'should not find a pattern for a block with different attributes', () => {
		const blockToFind: BlockInstance = {
			attributes: {
				attr1: 'value1',
				attr2: 'value2',
				className: 'class3', // Different className
			},
			clientId: 'clientId3', // Different clientId
			innerBlocks: [],
			isValid: true,
			name: 'block3', // Different block name
			originalContent: 'originalContent3', // Different original content
		};

		const result = findPatternByBlock( patterns, blockToFind );

		expect( result ).toBeUndefined();
	} );
} );
