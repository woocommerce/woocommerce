/**
 * External dependencies
 */
import { getValidBlockAttributes } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block';
import { ExpressCheckoutContext } from './context';
import metadata from './block.json';
import { ExpressCheckoutAttributes } from './types';

const FrontendBlock = ( attributes: ExpressCheckoutAttributes ) => {
	const validAttributes = getValidBlockAttributes(
		metadata.attributes,
		attributes
	);

	const { showButtonStyles, buttonHeight, buttonBorderRadius } =
		validAttributes;

	return (
		<ExpressCheckoutContext.Provider
			value={ { showButtonStyles, buttonHeight, buttonBorderRadius } }
		>
			<Block />
		</ExpressCheckoutContext.Provider>
	);
};

export default FrontendBlock;
