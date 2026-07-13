/**
 * External dependencies
 */
import clsx from 'clsx';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { addFilterForEmail } from '../../config-tools/filters';
import { unwrapCompressedPresetStyleVariable } from '../../style-variables';

type SpacingPadding =
	| string
	| { top?: string; right?: string; bottom?: string; left?: string };

// Columns handle their own width, so don't let full-width blocks inside a
// column break out (the renderer doesn't either).
const COLUMN_BLOCKS = [ 'core/column', 'core/columns' ];

// User blocks live inside these wrappers, which add no inset of their own. We
// skip them so a full-width block breaks out of the padded template group above.
const PASSTHROUGH_BLOCKS = [ 'core/post-content', 'woocommerce/email-content' ];

/**
 * Checks whether the value is zero (0, 0px, 0em, 0%, ...)
 */
function isZeroValue( value?: string ): boolean {
	if ( value === undefined || value === null ) {
		return false;
	}
	return parseFloat( String( value ) ) === 0;
}

/**
 * Reads a block's left/right padding as CSS values (preset vars resolved).
 * Returns null for a side when it's missing or zero.
 */
function getHorizontalPadding( padding?: SpacingPadding ): {
	left: string | null;
	right: string | null;
} {
	const toCss = ( value?: string ): string | null => {
		if ( value === undefined || value === null || isZeroValue( value ) ) {
			return null;
		}
		return unwrapCompressedPresetStyleVariable( String( value ) );
	};

	if ( ! padding ) {
		return { left: null, right: null };
	}
	if ( typeof padding === 'string' ) {
		const value = toCss( padding );
		return { left: value, right: value };
	}
	return { left: toCss( padding.left ), right: toCss( padding.right ) };
}

type BreakoutInfo = {
	left: string | null;
	right: string | null;
};

/**
 * Finds the padding a full-width block should break out of.
 *
 * Inside the post content (user blocks), a block breaks out of its direct
 * parent group's padding only, matching the core editor - if the direct parent
 * has no padding there's nothing to break out of.
 *
 * Returns false when the block sits in a column, since columns keep their width.
 *
 * Skips the ancestor lookup when `enabled` is false (block isn't full width) so
 * we don't traverse the tree for every block in the editor.
 */
function useBreakoutPadding(
	clientId: string,
	enabled: boolean
): BreakoutInfo | false {
	return useSelect(
		( select ) => {
			if ( ! enabled ) {
				return { left: null, right: null };
			}
			const { getBlockParents, getBlockName, getBlockAttributes } =
				select( blockEditorStore );
			// getBlockParents lists ancestors root-first, so the last entry is
			// the direct parent. Walk from there up to the root.
			const parents = getBlockParents( clientId ) as string[];
			let inTemplate = false;

			for ( let i = parents.length - 1; i >= 0; i-- ) {
				const parentId = parents[ i ];
				const name = getBlockName( parentId ) as string;

				if ( COLUMN_BLOCKS.includes( name ) ) {
					return false;
				}
				// Post content wrappers separate user blocks from the template.
				// Once we pass one, we're climbing the template layout.
				if ( PASSTHROUGH_BLOCKS.includes( name ) ) {
					inTemplate = true;
					continue;
				}

				const padding = getHorizontalPadding(
					getBlockAttributes( parentId )?.style?.spacing
						?.padding as SpacingPadding
				);
				if ( padding.left || padding.right ) {
					return padding;
				}
				// Parent has no padding. In user content, stop here - there's
				// nothing to break out of. In the template, keep going up to
				// find the padded group.
				if ( ! inTemplate ) {
					return { left: null, right: null };
				}
			}

			return { left: null, right: null };
		},
		[ clientId, enabled ]
	);
}

function BlockWithFullWidth( { block: BlockListBlock, props } ) {
	const isFullWidth = props.attributes?.align === 'full';
	const breakout = useBreakoutPadding( props.clientId, isFullWidth );

	// Not full width, or inside a column: render the block untouched.
	if ( ! isFullWidth || breakout === false ) {
		return <BlockListBlock { ...props } />;
	}

	const style: Record< string, string > = {
		...( props.wrapperProps?.style || {} ),
	};
	if ( breakout.left ) {
		style.marginLeft = `calc(-1 * ${ breakout.left })`;
	}
	if ( breakout.right ) {
		style.marginRight = `calc(-1 * ${ breakout.right })`;
	}

	return (
		<BlockListBlock
			{ ...props }
			className={ clsx( props.className, 'is-email-full-width' ) }
			wrapperProps={ { ...props.wrapperProps, style } }
		/>
	);
}

/**
 * Marks full-width aligned blocks so they go edge-to-edge in the editor canvas,
 * the same way they show up in the sent email. Always renders the same component
 * so toggling alignment doesn't remount the block.
 */
const withFullWidthClassName = createHigherOrderComponent(
	( BlockListBlock ) =>
		function maybeAddFullWidthClassName( props ) {
			return (
				<BlockWithFullWidth block={ BlockListBlock } props={ props } />
			);
		},
	'withFullWidthClassName'
);

/**
 * Makes full-width blocks span the full width in the editor, the same way they
 * show up in the sent email.
 */
export function enableFullWidthBlocks(): void {
	addFilterForEmail(
		'editor.BlockListBlock',
		'woocommerce-email-editor/full-width-blocks',
		withFullWidthClassName
	);
}
