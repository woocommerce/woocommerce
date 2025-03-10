/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import TEMPLATE from './template';

const Edit = () => {
	return <InnerBlocks template={ TEMPLATE } />;
};

export default Edit;
