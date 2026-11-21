/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { getBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { withDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * withDefaultAttributes HOC for editor.BlockListBlock.
 *
 * @param object BlockListBlock The BlockListBlock element.
 */
const withDefaultAttributes = createHigherOrderComponent(
	( BlockListBlock ) => {
		class WrappedComponent extends Component {
			mounted = false;

			componentDidMount() {
				const { block, clientId, updateBlockAttributes } = this.props;

				if ( block.name.startsWith( 'woocommerce/' ) ) {
					const missingDefaults = this.getMissingDefaults();

					if ( Object.keys( missingDefaults ).length ) {
						updateBlockAttributes( clientId, missingDefaults );
					}
				}
			}

			componentDidUpdate() {
				if (
					this.props.block.name.startsWith( 'woocommerce/' ) &&
					! this.mounted
				) {
					this.mounted = true;
				}
			}

			getMissingDefaults() {
				const blockType = getBlockType( this.props.block.name );
				const attributes = this.props.attributes || {};
				const missingDefaults = {};

				if (
					! blockType ||
					typeof blockType.attributes === 'undefined' ||
					typeof blockType.defaults === 'undefined'
				) {
					return missingDefaults;
				}

				Object.keys( blockType.attributes ).forEach( ( key ) => {
					if (
						typeof attributes[ key ] === 'undefined' &&
						typeof blockType.defaults[ key ] !== 'undefined'
					) {
						missingDefaults[ key ] = blockType.defaults[ key ];
					}
				} );

				return missingDefaults;
			}

			getAttributesWithDefaults() {
				if (
					this.mounted ||
					! this.props.block.name.startsWith( 'woocommerce/' )
				) {
					return this.props.attributes;
				}

				const missingDefaults = this.getMissingDefaults();

				if ( ! Object.keys( missingDefaults ).length ) {
					return this.props.attributes;
				}

				return {
					...( this.props.attributes || {} ),
					...missingDefaults,
				};
			}

			render() {
				const { updateBlockAttributes, ...props } = this.props;

				return (
					<BlockListBlock
						{ ...props }
						attributes={ this.getAttributesWithDefaults() }
					/>
				);
			}
		}
		return withDispatch( ( dispatch ) => ( {
			updateBlockAttributes:
				dispatch( blockEditorStore ).updateBlockAttributes,
		} ) )( WrappedComponent );
	},
	'withDefaultAttributes'
);

/**
 * Hook into `editor.BlockListBlock` to set default attributes (if blocks
 * define them separately) when a block is inserted.
 *
 * This is a workaround for Gutenberg which does not save "default" attributes
 * to the post, which means if defaults change, all existing blocks change too.
 *
 * See https://github.com/WordPress/gutenberg/issues/7342
 *
 * To use this, the block name needs a `woocommerce/` prefix, and as well
 * as defining `attributes` during block registration, you must also declare an
 * array called `defaults`. Defaults should be omitted from `attributes`.
 */
addFilter(
	'editor.BlockListBlock',
	'woocommerce-blocks/block-list-block',
	withDefaultAttributes
);
