/**
 * External dependencies
 */
import withFilteredAttributes from '@woocommerce/base-hocs/with-filtered-attributes';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

export default withFilteredAttributes( attributes )( Block );
