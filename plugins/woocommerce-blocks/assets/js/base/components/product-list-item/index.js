/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { useInnerBlockConfigurationContext } from '@woocommerce/base-context/inner-block-configuration-context';
import { useProductLayoutContext } from '@woocommerce/base-context/product-layout-context';
import withComponentId from '@woocommerce/base-hocs/with-component-id';

/**
 * Internal dependencies
 */
import { renderProductLayout } from './utils';

const ProductListItem = ( { product, attributes, componentId } ) => {
	const { layoutConfig } = attributes;
	const { parentName } = useInnerBlockConfigurationContext();
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const isLoading = ! Object.keys( product ).length > 0;
	const classes = classnames( `${ layoutStyleClassPrefix }__product`, {
		'is-loading': isLoading,
	} );

	return (
		<li className={ classes } aria-hidden={ isLoading }>
			{ renderProductLayout(
				parentName,
				product,
				layoutConfig,
				componentId
			) }
		</li>
	);
};

ProductListItem.propTypes = {
	attributes: PropTypes.object.isRequired,
	product: PropTypes.object,
	// from withComponentId
	componentId: PropTypes.number.isRequired,
};

export default withComponentId( ProductListItem );
