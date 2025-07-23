/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean } from '@woocommerce/types';
import { useState, useMemo, useEffect } from '@wordpress/element';
import { withSpokenMessages } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { previewOptions } from './preview';
import { getActiveFilters } from './utils';
import { Inspector } from './components/inspector';
import { getAllowedBlocks } from '../../utils/get-allowed-blocks';
import { EXCLUDED_BLOCKS } from '../../constants';
import { Notice } from '../../components/notice';
import type { Attributes } from './types';
import { InitialDisabled } from '../../components/initial-disabled';
import RatingStars from './components/rating-stars';

const RatingFilterEdit = ( props: BlockEditProps< Attributes > ) => {
	const { attributes, setAttributes, clientId } = props;

	const { isPreview, showCounts, minRating } = attributes;

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
			template: [
				[
					'core/heading',
					{
						level: 3,
						content: __( 'Rating', 'woocommerce' ),
						style: {
							spacing: {
								margin: {
									bottom: '0.625rem',
									top: '0',
								},
							},
						},
					},
				],
				[ 'woocommerce/product-filter-checkbox-list' ],
			],
		}
	);

	const [ queryState ] = useQueryStateByContext();

	const { data: collectionFilters, isLoading: filteredCountsLoading } =
		useCollectionData( {
			queryRating: true,
			queryState,
			isEditor: true,
		} );

	const [ displayedOptions, setDisplayedOptions ] = useState(
		isPreview ? previewOptions : []
	);

	const isLoading =
		! isPreview && filteredCountsLoading && displayedOptions.length === 0;

	const initialFilters = useMemo(
		() => getActiveFilters( 'rating_filter' ),
		[]
	);

	const [ productRatingsQuery ] = useQueryStateByKey(
		'rating',
		initialFilters
	);

	/**
	 * Compare intersection of all ratings
	 * and filtered counts to get a list of options to display.
	 */
	useEffect( () => {
		if ( filteredCountsLoading || isPreview ) {
			return;
		}

		if (
			! collectionFilters?.rating_counts ||
			collectionFilters?.rating_counts?.length === 0
		) {
			setDisplayedOptions( previewOptions );
			return;
		}

		const minimumRating =
			typeof minRating === 'string' ? parseFloat( minRating ) : 0;

		/*
		 * Process the ratings counts:
		 * - Sort the ratings in descending order
		 *   Todo: consider to handle this in the API request
		 * - Filter out ratings below the minimum rating
		 * - Map the ratings to the format expected by the filter component
		 */
		const productsRating = collectionFilters?.rating_counts?.length
			? collectionFilters.rating_counts
					.sort( ( a, b ) => b.rating - a.rating )
					.filter( ( { rating } ) => rating >= minimumRating )
					.map( ( { rating, count }, index ) => ( {
						label: <RatingStars key={ rating } stars={ rating } />,
						ariaLabel: sprintf(
							/* translators: %d: rating value. Example: Rated 4 out of 5. */
							__( 'Rated %d out of 5', 'woocommerce' ),
							rating
						),
						value: rating?.toString(),
						selected: index === 0,
						count,
					} ) )
			: [];

		setDisplayedOptions( productsRating );
	}, [
		showCounts,
		isPreview,
		collectionFilters,
		filteredCountsLoading,
		productRatingsQuery,
		minRating,
	] );

	if ( ! filteredCountsLoading && displayedOptions.length === 0 ) {
		return null;
	}

	const hasFilterableProducts = getSettingWithCoercion(
		'hasFilterableProducts',
		false,
		isBoolean
	);

	if ( ! hasFilterableProducts ) {
		return null;
	}

	const showNoProductsNotice =
		! filteredCountsLoading && ! collectionFilters.rating_counts?.length;

	return (
		<>
			<Inspector
				clientId={ clientId }
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<div { ...innerBlocksProps }>
				<InitialDisabled>
					{ showNoProductsNotice && (
						<Notice>
							{ __(
								"Your store doesn't have any products with ratings yet. This filter option will display when a product receives a review.",
								'woocommerce'
							) }
						</Notice>
					) }
					<div
						className={ clsx( {
							'is-loading': isLoading,
						} ) }
					>
						<BlockContextProvider
							value={ {
								filterData: {
									items: displayedOptions,
									isLoading,
									showCounts,
								},
							} }
						>
							{ children }
						</BlockContextProvider>
					</div>
				</InitialDisabled>
			</div>
		</>
	);
};

export default withSpokenMessages( RatingFilterEdit );
