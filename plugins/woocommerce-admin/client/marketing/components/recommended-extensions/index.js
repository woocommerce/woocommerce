/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { Spinner } from '@wordpress/components';
import classnames from 'classnames';
import { withDispatch, withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import RecommendedExtensionsItem from './item';
import { STORE_KEY } from '../../data/constants';

const RecommendedExtensions = ( {
	extensions,
	isLoading,
	title,
	description,
	category,
} ) => {
	if ( extensions.length === 0 && ! isLoading ) {
		return null;
	}

	const categoryClass = category
		? `woocommerce-marketing-recommended-extensions-card__category-${ category }`
		: '';

	return (
		<Card
			title={ title }
			description={ description }
			className={ classnames(
				'woocommerce-marketing-recommended-extensions-card',
				categoryClass
			) }
		>
			<Fragment>
				{ isLoading ? (
					<Spinner />
				) : (
					<div
						className={ classnames(
							'woocommerce-marketing-recommended-extensions-card__items',
							`woocommerce-marketing-recommended-extensions-card__items--count-${ extensions.length }`
						) }
					>
						{ extensions.map( ( extension ) => (
							<RecommendedExtensionsItem
								key={ extension.product }
								category={ category }
								{ ...extension }
							/>
						) ) }
					</div>
				) }
			</Fragment>
		</Card>
	);
};

RecommendedExtensions.propTypes = {
	/**
	 * Array of recommended extensions.
	 */
	extensions: PropTypes.arrayOf( PropTypes.object ).isRequired,
	/**
	 * Whether the card is loading.
	 */
	isLoading: PropTypes.bool.isRequired,
	/**
	 * Cart title.
	 */
	title: PropTypes.string,
	/**
	 * Card description.
	 */
	description: PropTypes.string,
	/**
	 * Category of extensions to display.
	 */
	category: PropTypes.string,
};

RecommendedExtensions.defaultProps = {
	title: __( 'Recommended extensions', 'woocommerce-admin' ),
	description: __(
		'Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.',
		'woocommerce-admin'
	),
};

export { RecommendedExtensions };

export default compose(
	withSelect( ( select, props ) => {
		const { getRecommendedPlugins, isResolving } = select( STORE_KEY );

		return {
			extensions: getRecommendedPlugins( props.category ),
			isLoading: isResolving( 'getRecommendedPlugins', [
				props.category,
			] ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( RecommendedExtensions );
