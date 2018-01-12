const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

/**
 * Products block.
 */
registerBlockType( 'woocommerce/products', {
	title: __( 'Products' ),
	icon: 'universal-access-alt',
	category: 'widgets',

	// This will need to be converted to pull dynamic data from the API similar to https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/.
	edit( { className } ) {
		const style = {
			backgroundColor: 'purple',
			padding: '2em',
			color: 'white',
		}

		return <div className={ className } style={ style }>This needs to do stuff.</div>;
	},

	save() {
		return '[products limit="3"]';
	},
} );
