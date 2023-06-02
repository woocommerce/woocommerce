/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { kebabCase } from 'lodash';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';

// Component to display cart item data and variations.
const ProductDetails = ( { details = [] } ) => {
	if ( ! Array.isArray( details ) ) {
		return null;
	}

	details = details.filter( ( detail ) => ! detail.hidden );

	if ( details.length === 0 ) {
		return null;
	}

	return (
		<ul className="wc-block-components-product-details">
			{ details.map( ( detail ) => {
				const className = detail.name
					? `wc-block-components-product-details__${ kebabCase(
							detail.name
					  ) }`
					: '';
				return (
					<li
						key={ detail.name + ( detail.display || detail.name ) }
						className={ className }
					>
						{ detail.name && (
							<>
								<span className="wc-block-components-product-details__name">
									{ decodeEntities( detail.name ) }:
								</span>{ ' ' }
							</>
						) }
						<span className="wc-block-components-product-details__value">
							{ decodeEntities( detail.display || detail.value ) }
						</span>
					</li>
				);
			} ) }
		</ul>
	);
};

ProductDetails.propTypes = {
	details: PropTypes.arrayOf(
		PropTypes.shape( {
			display: PropTypes.string,
			name: PropTypes.string,
			value: PropTypes.string.isRequired,
		} )
	),
};

export default ProductDetails;
