/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { renderInnerBlocks } from './render-inner-blocks';

/**
 * Renders a block component in the place of a specified set of selectors.
 *
 * @param {Object}   props                         Render props.
 * @param {Function} props.Block                   React component to use as a replacement.
 * @param {string}   props.selector                CSS selector to match the elements to replace.
 * @param {string}   [props.blockName]             Optional Block Name. Used for inner block component mapping.
 * @param {Function} [props.getProps]              Function to generate the props object for the block.
 */
export const renderParentBlock = ( {
	Block,
	selector,
	blockName = '',
	getProps = () => {},
} ) => {
	const getPropsWithChildren = ( el, i ) => {
		const children =
			el.children && el.children.length
				? renderInnerBlocks( {
						blockName,
						children: el.children,
				  } )
				: null;
		return { ...getProps( el, i ), children };
	};
	renderFrontend( {
		Block,
		selector,
		getProps: getPropsWithChildren,
	} );
};
