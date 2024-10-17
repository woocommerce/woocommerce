/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { useBlockProps } from '@wordpress/block-editor';
import Rating from '@woocommerce/base-components/product-rating';
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean, isObject, objectHasProp } from '@woocommerce/types';
import { useState, useMemo, useEffect } from '@wordpress/element';
import { CheckboxList } from '@woocommerce/blocks-components';
import { Disabled, Notice, withSpokenMessages } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { previewOptions } from './preview';
import { getActiveFilters } from './utils';
import { useSetWraperVisibility } from '../../../filter-wrapper/context';
import { Inspector } from './components/inspector';
import { PreviewDropdown } from '../components/preview-dropdown';
import type { Attributes } from './types';
import './style.scss';

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

const Edit = ( props: BlockEditProps< Attributes > ) => {
	const { attributes, setAttributes } = props;

	const blockProps = useBlockProps();

	const setWrapperVisibility = useSetWraperVisibility();
	const [ queryState ] = useQueryStateByContext();

	const { results: filteredCounts, isLoading: filteredCountsLoading } =
		useCollectionData( {
			queryRating: true,
			queryState,
			isEditor: true,
		} );

	const [ displayedOptions, setDisplayedOptions ] = useState(
		attributes.isPreview ? previewOptions : []
	);

	const isLoading =
		! attributes.isPreview &&
		filteredCountsLoading &&
		displayedOptions.length === 0;

	const isDisabled = ! attributes.isPreview && filteredCountsLoading;

	const initialFilters = useMemo(
		() => getActiveFilters( 'rating_filter' ),
		[]
	);

	const [ checked ] = useState( initialFilters );

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

		if ( filteredCountsLoading || attributes.isPreview ) {
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
								attributes.showCounts ? item?.count : null
							}
						/>
					),
					value: item?.rating?.toString(),
				};
			} );

		setDisplayedOptions( newOptions );
	}, [
		attributes.showCounts,
		attributes.isPreview,
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

			<div { ...blockProps }>
				<Disabled>
					{ displayNoProductRatingsNotice && <NoRatings /> }
					<div
						className={ clsx(
							`style-${ attributes.displayStyle }`,
							{
								'is-loading': isLoading,
							}
						) }
					>
						{ attributes.displayStyle === 'dropdown' ? (
							<PreviewDropdown
								placeholder={
									attributes.selectType === 'single'
										? __( 'Select a rating', 'woocommerce' )
										: __( 'Select ratings', 'woocommerce' )
								}
							/>
						) : (
							<CheckboxList
								options={ displayedOptions }
								checked={ checked }
								onChange={ () => {
									// noop
								} }
								isLoading={ isLoading }
								isDisabled={ isDisabled }
							/>
						) }
					</div>
				</Disabled>
			</div>
		</>
	);
};

export default withSpokenMessages( Edit );
