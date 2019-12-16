/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withFeedbackPrompt } from '@woocommerce/block-hocs';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';

const CheckoutEditor = ( { attributes } ) => {
	const { className } = attributes;
	// @todo: wrap Block with Disabled once you finish building the form
	return (
		<div className={ className }>
			<Block attributes={ attributes } isEditor={ true } />
		</div>
	);
};

export default withFeedbackPrompt(
	__(
		'We are currently working on improving our checkout and providing merchants with tools and options to customize their checkout to their stores needs.',
		'woo-gutenberg-products-block'
	)
)( CheckoutEditor );
