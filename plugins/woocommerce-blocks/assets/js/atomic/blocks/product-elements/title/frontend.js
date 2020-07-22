/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import withFilteredAttributes from '@woocommerce/base-hocs/with-filtered-attributes';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

export default compose( withFilteredAttributes( attributes ) )( Block );
