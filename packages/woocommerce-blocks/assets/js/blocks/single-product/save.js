/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import classnames from 'classnames';

const Save = ( { attributes } ) => {
	return (
		<div className={ classnames( 'is-loading', attributes.className ) }>
			<InnerBlocks.Content />
		</div>
	);
};

export default Save;
