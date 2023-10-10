/**
 * Add Custom Block Control to Existing Block
 */

/**
 * Internal dependencies
 */
//import Controls from '../components/Controls';

const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;

const allowedBlocks = [ 'woocommerce/product-images-field' ]; // Enable control to existing Group block

/**
 * Add custom attribute
 */
function addAttributes( settings ) {
	// Check if attributes exists and compare the block name
	if (
		typeof settings.attributes !== 'undefined' &&
		allowedBlocks.includes( settings.name )
	) {
		settings.attributes = Object.assign( settings.attributes, {
			theme: {
				type: 'string',
				default: '',
			},
		} );
	}

	return settings;
}
wp.hooks.addFilter(
	'blocks.registerBlockType',
	'woo-ai/image-background-replacer',
	addAttributes
);

/**
 * Add Custom Block Controls
 */
const addBlockControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, isSelected } = props;

		if ( ! allowedBlocks.includes( name ) ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<Fragment>
				{ isSelected && <h1 { ...props }>HELLO WORLD</h1> }
				<BlockEdit { ...props } />
			</Fragment>
		);
	};
}, 'addBlockControls' );

wp.hooks.addFilter(
	'editor.BlockEdit',
	'woo-ai/image-background-replacer',
	addBlockControls
);
