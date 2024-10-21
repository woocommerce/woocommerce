/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './form';
import { parseAttributes } from './utils';

const getProps = ( el: HTMLElement ) => {
	return {
		attributes: parseAttributes( el.dataset ),
		isEditor: false,
	};
};

// This does not replace the entire block markup, just the form part.
renderFrontend( {
	selector: '.wc-block-order-confirmation-create-account-form',
	Block,
	getProps,
} );
