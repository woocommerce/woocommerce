/**
 * External dependencies
 */
import { render, Suspense } from '@wordpress/element';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';

// Some blocks take care of rendering their inner blocks automatically. For
// example, the empty cart. In those cases, we don't want to trigger the render
// function of inner components on load. Instead, the wrapper block can trigger
// the event `wc-blocks_render_blocks_frontend` to render its inner blocks.
const selectorsToSkipOnLoad = [ '.wp-block-woocommerce-cart' ];

// Given an element and a list of wrappers, check if the element is inside at
// least one of the wrappers.
const isElementInsideWrappers = ( el, wrappers ) => {
	return Array.prototype.some.call(
		wrappers,
		( wrapper ) => wrapper.contains( el ) && ! wrapper.isSameNode( el )
	);
};

/**
 * Renders a block component in each `containers` node.
 *
 * @param {Object}    props                         Render props.
 * @param {Function}  props.Block                   React component to use as a
 *                                                  replacement.
 * @param {NodeList}  props.containers              Containers to replace with
 *                                                  the Block component.
 * @param {Function}  [props.getProps]              Function to generate the
 *                                                  props object for the block.
 * @param {Function}  [props.getErrorBoundaryProps] Function to generate the
 *                                                  props object for the error
 *                                                  boundary.
 */
const renderBlockInContainers = ( {
	Block,
	containers,
	getProps = () => ( {} ),
	getErrorBoundaryProps = () => ( {} ),
} ) => {
	if ( containers.length === 0 ) {
		return;
	}

	// Use Array.forEach for IE11 compatibility.
	Array.prototype.forEach.call( containers, ( el, i ) => {
		const props = getProps( el, i );
		const errorBoundaryProps = getErrorBoundaryProps( el, i );
		const attributes = {
			...el.dataset,
			...( props.attributes || {} ),
		};
		el.classList.remove( 'is-loading' );

		renderBlock( {
			Block,
			container: el,
			props,
			attributes,
			errorBoundaryProps,
		} );
	} );
};

/**
 * Renders a block component in a single `container` node.
 *
 * @param {Object}    props                         Render props.
 * @param {Function}  props.Block                   React component to use as a
 *                                                  replacement.
 * @param {Node}      props.container               Container to replace with
 *                                                  the Block component.
 * @param {Object}    [props.attributes]            Attributes object for the
 *                                                  block.
 * @param {Object}    [props.props]                 Props object for the block.
 * @param {Object}    [props.errorBoundaryProps]    Props object for the error
 *                                                  boundary.
 */
export const renderBlock = ( {
	Block,
	container,
	attributes = {},
	props = {},
	errorBoundaryProps = {},
} ) => {
	render(
		<BlockErrorBoundary { ...errorBoundaryProps }>
			<Suspense fallback={ <div className="wc-block-placeholder" /> }>
				<Block { ...props } attributes={ attributes } />
			</Suspense>
		</BlockErrorBoundary>,
		container
	);
};

/**
 * Renders the block frontend in the elements matched by the selector which are
 * outside the wrapper elements.
 *
 * @param {Object}    props                         Render props.
 * @param {Function}  props.Block                   React component to use as a
 *                                                  replacement.
 * @param {string}    props.selector                CSS selector to match the
 *                                                  elements to replace.
 * @param {Function}  [props.getProps]              Function to generate the
 *                                                  props object for the block.
 * @param {Function}  [props.getErrorBoundaryProps] Function to generate the
 *                                                  props object for the error
 *                                                  boundary.
 * @param {NodeList}  props.wrappers                All elements matched by the
 *                                                  selector which are inside
 *                                                  the wrapper will be ignored.
 */
const renderBlockOutsideWrappers = ( {
	Block,
	getProps,
	getErrorBoundaryProps,
	selector,
	wrappers,
} ) => {
	const containers = document.body.querySelectorAll( selector );
	// Filter out blocks inside the wrappers.
	if ( wrappers.length > 0 ) {
		Array.prototype.filter.call( containers, ( el ) => {
			return ! isElementInsideWrappers( el, wrappers );
		} );
	}
	renderBlockInContainers( {
		Block,
		containers,
		getProps,
		getErrorBoundaryProps,
	} );
};

/**
 * Renders the block frontend in the elements matched by the selector inside the
 * wrapper element.
 *
 * @param {Object}    props                         Render props.
 * @param {Function}  props.Block                   React component to use as a
 *                                                  replacement.
 * @param {string}    props.selector                CSS selector to match the
 *                                                  elements to replace.
 * @param {Function}  [props.getProps]              Function to generate the
 *                                                  props object for the block.
 * @param {Function}  [props.getErrorBoundaryProps] Function to generate the
 *                                                  props object for the error
 *                                                  boundary.
 * @param {Element}   props.wrapper                 Wrapper element to query the
 *                                                  selector inside.
 */
const renderBlockInsideWrapper = ( {
	Block,
	getProps,
	getErrorBoundaryProps,
	selector,
	wrapper,
} ) => {
	const containers = wrapper.querySelectorAll( selector );
	renderBlockInContainers( {
		Block,
		containers,
		getProps,
		getErrorBoundaryProps,
	} );
};

/**
 * Renders the block frontend on page load. If the block is contained inside a
 * wrapper element that should be excluded from initial load, it adds the
 * appropriate event listeners to render the block when the
 * `wc-blocks_render_blocks_frontend` event is triggered.
 *
 * @param {Object}    props                         Render props.
 * @param {Function}  props.Block                   React component to use as a
 *                                                  replacement.
 * @param {string}    props.selector                CSS selector to match the
 *                                                  elements to replace.
 * @param {Function}  [props.getProps]              Function to generate the
 *                                                  props object for the block.
 * @param {Function}  [props.getErrorBoundaryProps] Function to generate the
 *                                                  props object for the error
 *                                                  boundary.
 */
export const renderFrontend = ( props ) => {
	const wrappersToSkipOnLoad = document.body.querySelectorAll(
		selectorsToSkipOnLoad.join( ',' )
	);
	renderBlockOutsideWrappers( {
		...props,
		wrappers: wrappersToSkipOnLoad,
	} );
	// For each wrapper, add an event listener to render the inner blocks when
	// `wc-blocks_render_blocks_frontend` event is triggered.
	Array.prototype.forEach.call( wrappersToSkipOnLoad, ( wrapper ) => {
		wrapper.addEventListener( 'wc-blocks_render_blocks_frontend', () => {
			renderBlockInsideWrapper( { ...props, wrapper } );
		} );
	} );
};

export default renderFrontend;
