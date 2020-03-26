/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import {
	useInnerBlockConfigurationContext,
	useProductLayoutContext,
} from '@woocommerce/base-context';
import { withInstanceId } from '@woocommerce/base-hocs/with-instance-id';

/**
 * Internal dependencies
 */
import { renderProductLayout } from './utils';

const ProductListItem = ( { product, attributes, instanceId } ) => {
	const { layoutConfig } = attributes;
	const { parentName } = useInnerBlockConfigurationContext();
	const { layoutStyleClassPrefix } = useProductLayoutContext();
	const isLoading = Object.keys( product ).length === 0;
	const classes = classnames( `${ layoutStyleClassPrefix }__product`, {
		'is-loading': isLoading,
	} );

	return (
		<li className={ classes } aria-hidden={ isLoading }>
			{ renderProductLayout(
				parentName,
				product,
				layoutConfig,
				instanceId
			) }
		</li>
	);
};

ProductListItem.propTypes = {
	attributes: PropTypes.object.isRequired,
	product: PropTypes.object,
	// from withInstanceId
	instanceId: PropTypes.number.isRequired,
};

export default withInstanceId( ProductListItem );
