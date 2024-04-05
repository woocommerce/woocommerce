/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { LayoutOptions, ProductCollectionLayout } from './types';

interface DisplayLayoutAttribute {
	type: 'list' | 'flex';
	columns: number;
	shrinkColumns: boolean;
}

const migrateDisplayLayout = (
	attributes: { displayLayout: DisplayLayoutAttribute },
	innerBlocks: BlockInstance[]
) => {
	const { displayLayout = null, ...newAttributes } = attributes;
	if ( ! displayLayout ) {
		return [ attributes, innerBlocks ];
	}

	const { type, columns, shrinkColumns } = displayLayout;

	// Convert custom displayLayout to templateLayout which will then
	// be applied automatically to the template block.
	let templateLayout: ProductCollectionLayout;
	if ( type === 'flex' ) {
		// Convert responsive columns to size-based automatic columns.
		if ( shrinkColumns ) {
			// We can replicate the previous responsive behavior by using the same
			// math as the column classes. This subtracts the gap between columns
			// from the width of the container and then divides the width into
			// the number of columns that was selected.
			const columnWidth = `max( 150px, (100% - ${
				1.25 * ( columns - 1 )
			}em) / ${ columns } )`;

			templateLayout = {
				type: LayoutOptions.GRID,
				minimumColumnWidth: columnWidth,
			};
		} else {
			templateLayout = {
				type: LayoutOptions.GRID,
				columnCount: columns,
			};
		}
	} else {
		templateLayout = {
			type: LayoutOptions.STACK,
		};
	}

	return {
		templateLayout,
		...newAttributes,
	};
};

// Version with `displayLayout` instead of `templateLayout`.
const v1 = {
	attributes: {
		queryId: {
			type: 'number',
		},
		query: {
			type: 'object',
		},
		tagName: {
			type: 'string',
		},
		displayLayout: {
			type: 'object',
		},
		convertedFromProducts: {
			type: 'boolean',
			default: false,
		},
		collection: {
			type: 'string',
		},
		hideControls: {
			default: [],
			type: 'array',
		},
		queryContextIncludes: {
			type: 'array',
		},
		forcePageReload: {
			type: 'boolean',
			default: false,
		},
	},
	supports: {
		align: [ 'wide', 'full' ],
		anchor: true,
		html: false,
		__experimentalLayout: true,
		interactivity: true,
	},
	save( { attributes: { tagName: Tag = 'div' } } ) {
		const blockProps = useBlockProps.save();
		const innerBlocksProps = useInnerBlocksProps.save( blockProps );
		return <Tag { ...innerBlocksProps } />;
	},
	isEligible: ( {
		displayLayout,
	}: {
		displayLayout?: DisplayLayoutAttribute;
	} ) => {
		return !! displayLayout;
	},
	migrate: migrateDisplayLayout,
};

const deprecated = [ v1 ];

export default deprecated;
