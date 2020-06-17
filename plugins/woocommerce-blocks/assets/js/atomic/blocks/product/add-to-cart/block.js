/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { AddToCartFormContextProvider } from '@woocommerce/base-context';
import { useProductDataContext } from '@woocommerce/shared-context';
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';
import { AddToCartButton } from './shared';
import {
	SimpleProductForm,
	VariableProductForm,
	ExternalProductForm,
	GroupedProductForm,
} from './product-types';

/**
 * Product Add to Form Block Component.
 *
 * @param {Object} props                     Incoming props.
 * @param {string} [props.className]         CSS Class name for the component.
 * @param {boolean} [props.showFormElements] Should form elements be shown?
 * @param {Object} [props.product]           Optional product object. Product from context will be
 *                                           used if this is not provided.
 * @return {*} The component.
 */
const Block = ( { className, showFormElements, ...props } ) => {
	const productDataContext = useProductDataContext();
	const product = props.product || productDataContext.product || {};
	const componentClass = classnames(
		className,
		'wc-block-components-product-add-to-cart',
		{
			'wc-block-components-product-add-to-cart--placeholder': isEmpty(
				product
			),
		}
	);

	return (
		<AddToCartFormContextProvider
			product={ product }
			showFormElements={ showFormElements }
		>
			<div className={ componentClass }>
				<>
					{ showFormElements ? (
						<AddToCartForm
							productType={ product.type || 'simple' }
						/>
					) : (
						<AddToCartButton />
					) }
				</>
			</div>
		</AddToCartFormContextProvider>
	);
};

const AddToCartForm = ( { productType } ) => {
	if ( productType === 'variable' ) {
		return <VariableProductForm />;
	}
	if ( productType === 'grouped' ) {
		return <GroupedProductForm />;
	}
	if ( productType === 'external' ) {
		return <ExternalProductForm />;
	}
	if ( productType === 'simple' || productType === 'variation' ) {
		return <SimpleProductForm />;
	}
	return null;
};

Block.propTypes = {
	className: PropTypes.string,
	product: PropTypes.object,
};

export default Block;
