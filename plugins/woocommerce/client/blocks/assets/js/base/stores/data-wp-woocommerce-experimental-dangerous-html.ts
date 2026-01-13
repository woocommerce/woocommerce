/**
 * External dependencies
 */
import { privateApis } from '@wordpress/interactivity';

const { directive } = privateApis(
	'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WordPress.'
);

directive(
	'woocommerce-experimental-dangerous-html',
	( {
		directives: { [ 'woocommerce-experimental-dangerous-html' ]: html },
		element,
		evaluate,
	} ) => {
		const entry = html.find( ( { suffix } ) => suffix === null );
		const result = evaluate( entry );
		element.props.dangerouslySetInnerHTML = { __html: result };
	}
);
