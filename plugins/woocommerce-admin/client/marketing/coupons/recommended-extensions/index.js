/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import clsx from 'clsx';
import { withDispatch, withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import RecommendedExtensionsItem from './item';
import RecommendedExtensionsPlaceholder from './placeholder';
import { STORE_KEY } from '~/marketing/data/constants';
import Card from '../card';

const RecommendedExtensions = ( {
	extensions,
	isLoading,
	title = __( 'Recommended extensions', 'woocommerce' ),
	description = __(
		'Great marketing requires the right tools. Take your marketing to the next level with our recommended marketing extensions.',
		'woocommerce'
	),
	category,
} ) => {
	if ( extensions.length === 0 && ! isLoading ) {
		return null;
	}

	const categoryClass = category
		? `woocommerce-marketing-recommended-extensions-card__category-${ category }`
		: '';
	const placholdersCount = 5;

	return (
		<Card
			title={ title }
			description={ description }
			className={ clsx(
				'woocommerce-marketing-recommended-extensions-card',
				categoryClass
			) }
		>
			{ isLoading ? (
				<div
					className={ clsx(
						'woocommerce-marketing-recommended-extensions-card__items',
						`woocommerce-marketing-recommended-extensions-card__items--count-${ placholdersCount }`
					) }
				>
					{ [ ...Array( placholdersCount ).keys() ].map( ( key ) => (
						<RecommendedExtensionsPlaceholder key={ key } />
					) ) }
				</div>
			) : (
				<div
					className={ clsx(
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

export { RecommendedExtensions };
export { default as RecommendedExtensionsPlaceholder } from './placeholder';

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
