/**
 * External dependencies
 */
import { withFilteredAttributes } from '@woocommerce/shared-hocs';

/**
 * Internal dependencies
 */
import Block from './block';
import metadata from './block.json';

export default withFilteredAttributes( metadata.attributes )( Block );
