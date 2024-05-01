/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
const Foo = wc.blocksCheckout.ExperimentalOrderMeta;

const render = () => {
	console.log( 'render' );
	return (
		<Foo>
			<h1>SUPERSTAR DJ's RULE THE WORLD</h1>
		</Foo>
	);
};

console.log( 'registering plugin' );

export const reg = () => {
	registerPlugin( 'my-plugin', { render, scope: 'woocommerce-checkout' } );

	render();
};
