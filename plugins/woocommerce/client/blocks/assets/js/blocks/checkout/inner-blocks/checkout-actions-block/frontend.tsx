/**
 * External dependencies
 */
import { withFilteredAttributes } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import metadata from './block.json';

export default withFilteredAttributes( {
	...attributes,
	...metadata.attributes,
} )( Block );
