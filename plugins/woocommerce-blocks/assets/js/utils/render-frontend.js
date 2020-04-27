/**
 * External dependencies
 */
import { render } from 'react-dom';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';

/**
 * Given some block attributes, gets attributes from the dataset or uses defaults.
 *
 * @param {Object} blockAttributes Object containing block attributes.
 * @param {Array} dataset Dataset from DOM.
 * @return {Array} Array of parsed attributes.
 */
export const getAttributesFromDataset = ( blockAttributes, dataset ) => {
	const attributes = [];

	Object.keys( blockAttributes ).forEach( ( key ) => {
		if ( typeof dataset[ key ] !== 'undefined' ) {
			switch ( blockAttributes[ key ].type ) {
				case 'boolean':
					attributes[ key ] = dataset[ key ] !== 'false';
					break;
				case 'number':
					attributes[ key ] = parseInt( dataset[ key ], 10 );
					break;
				default:
					attributes[ key ] = dataset[ key ];
					break;
			}
		} else {
			attributes[ key ] = blockAttributes[ key ].default;
		}
	} );

	return attributes;
};

/**
 * Renders a block component in the place of a specified set of selectors.
 *
 * @param {string}   selector                CSS selector to match the elements
 * to replace.
 * @param {Function} Block                   React block to use as a replacement.
 * @param {Function} [getProps]              Function to generate the props
 * object for the block.
 * @param {Function} [getErrorBoundaryProps] Function to generate the props
 * object for the error boundary.
 */
export const renderFrontend = (
	selector,
	Block,
	getProps = () => {},
	getErrorBoundaryProps = () => {}
) => {
	const containers = document.querySelectorAll( selector );

	if ( containers.length ) {
		// Use Array.forEach for IE11 compatibility.
		Array.prototype.forEach.call( containers, ( el, i ) => {
			const props = getProps( el, i );
			const errorBoundaryProps = getErrorBoundaryProps( el, i );
			const attributes = {
				...el.dataset,
				...props.attributes,
			};

			el.classList.remove( 'is-loading' );

			render(
				<BlockErrorBoundary { ...errorBoundaryProps }>
					<Block { ...props } attributes={ attributes } />
				</BlockErrorBoundary>,
				el
			);
		} );
	}
};

export default renderFrontend;
