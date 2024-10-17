/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import Rating from '@woocommerce/base-components/product-rating';
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean, isObject, objectHasProp } from '@woocommerce/types';
import { useState, useMemo, useEffect } from '@wordpress/element';
import { Notice, withSpokenMessages } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { previewOptions } from './preview';
import { getActiveFilters } from './utils';
import { useSetWraperVisibility } from '../../../filter-wrapper/context';
import { Inspector } from './components/inspector';
import { InitialDisabled } from '../../components/initial-disabled';
import { PreviewDropdown } from '../components/preview-dropdown';
import { getAllowedBlocks } from '../../utils';
import { EXCLUDED_BLOCKS } from '../../constants';
import './style.scss';
import type { Attributes } from './types';

const NoRatings = () => (
	<Notice status="warning" isDismissible={ false }>
		<p>
			{ __(
				"Your store doesn't have any products with ratings yet. This filter option will display when a product receives a review.",
				'woocommerce'
			) }
		</p>
	</Notice>
);

const RatingFilterEdit = ( props: BlockEditProps< Attributes > ) => {
	const { attributes, setAttributes } = props;

	const { displayStyle, isPreview, showCounts, selectType } = attributes;

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
			template: [
				[
					'core/group',
					{
						layout: {
							type: 'flex',
							flexWrap: 'nowrap',
						},
						metadata: {
							name: __( 'Header', 'woocommerce' ),
						},
						style: {
							spacing: {
								blockGap: '0',
							},
						},
					},
					[
						[
							'core/heading',
							{
								level: 3,
								content: __( 'Rating', 'woocommerce' ),
							},
						],
						[
							'woocommerce/product-filter-clear-button',
							{
								lock: {
									remove: true,
									move: false,
								},
							},
						],
					],
				],
				[
					displayStyle,
					{
						lock: {
							remove: true,
						},
					},
				],
			],
		}
	);

	const setWrapperVisibility = useSetWraperVisibility();
	const [ queryState ] = useQueryStateByContext();

	const { results: filteredCounts, isLoading: filteredCountsLoading } =
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

	const [ displayNoProductRatingsNotice, setDisplayNoProductRatingsNotice ] =
		useState( false );

	/**
	 * Compare intersection of all ratings
	 * and filtered counts to get a list of options to display.
	 */
	useEffect( () => {
		/**
		 * Checks if a status slug is in the query state.
		 *
		 * @param {string} queryStatus The status slug to check.
		 */

		if ( filteredCountsLoading || isPreview ) {
			return;
		}

		const orderedRatings =
			! filteredCountsLoading &&
			objectHasProp( filteredCounts, 'rating_counts' ) &&
			Array.isArray( filteredCounts.rating_counts )
				? [ ...filteredCounts.rating_counts ].reverse()
				: [];

		if ( orderedRatings.length === 0 ) {
			setDisplayedOptions( previewOptions );
			setDisplayNoProductRatingsNotice( true );
			return;
		}

		const newOptions = orderedRatings
			.filter(
				( item ) => isObject( item ) && Object.keys( item ).length > 0
			)
			.map( ( item ) => {
				return {
					label: (
						<Rating
							key={ item?.rating }
							rating={ item?.rating }
							ratedProductsCount={
								showCounts ? item?.count : null
							}
						/>
					),
					value: item?.rating?.toString(),
				};
			} );

		setDisplayedOptions( newOptions );
	}, [
		showCounts,
		isPreview,
		filteredCounts,
		filteredCountsLoading,
		productRatingsQuery,
	] );

	if ( ! filteredCountsLoading && displayedOptions.length === 0 ) {
		setWrapperVisibility( false );
		return null;
	}

	const hasFilterableProducts = getSettingWithCoercion(
		'hasFilterableProducts',
		false,
		isBoolean
	);

	if ( ! hasFilterableProducts ) {
		setWrapperVisibility( false );
		return null;
	}

	setWrapperVisibility( true );

	return (
		<>
			<Inspector
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>

			<div { ...innerBlocksProps }>
				<InitialDisabled>
					{ displayNoProductRatingsNotice && <NoRatings /> }
					<div
						className={ clsx( `style-${ displayStyle }`, {
							'is-loading': isLoading,
						} ) }
					>
						{ displayStyle === 'dropdown' ? (
							<PreviewDropdown
								placeholder={
									selectType === 'single'
										? __( 'Select a rating', 'woocommerce' )
										: __( 'Select ratings', 'woocommerce' )
								}
							/>
						) : (
							<BlockContextProvider
								value={ {
									filterData: {
										items: displayedOptions,
										isLoading,
									},
								} }
							>
								{ children }
							</BlockContextProvider>
						) }
					</div>
				</InitialDisabled>
			</div>
		</>
	);
};

export default withSpokenMessages( RatingFilterEdit );
