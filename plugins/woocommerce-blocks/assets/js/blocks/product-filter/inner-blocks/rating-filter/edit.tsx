/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
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

/**
 * Internal dependencies
 */
import { previewOptions } from './preview';
import { Attributes } from './types';
import { getActiveFilters } from './utils';
import { useSetWraperVisibility } from '../../../filter-wrapper/context';
import { Inspector } from './components/inspector';
import { PreviewDropdown } from '../components/preview-dropdown';
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
	const blockAttributes = props.attributes;

	const blockProps = useBlockProps();

	const isEditor = true;

	const setWrapperVisibility = useSetWraperVisibility();
	const [ queryState ] = useQueryStateByContext();

	const { results: filteredCounts, isLoading: filteredCountsLoading } =
		useCollectionData( {
			queryRating: true,
			queryState,
			isEditor,
		} );

	const [ displayedOptions, setDisplayedOptions ] = useState(
		blockAttributes.isPreview ? previewOptions : []
	);

	const isLoading =
		! blockAttributes.isPreview &&
		filteredCountsLoading &&
		displayedOptions.length === 0;

	const isDisabled = ! blockAttributes.isPreview && filteredCountsLoading;

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
	 * Compare intersection of all ratings and filtered counts to get a list of options to display.
	 */
	useEffect( () => {
		/**
		 * Checks if a status slug is in the query state.
		 *
		 * @param {string} queryStatus The status slug to check.
		 */

		if ( filteredCountsLoading || blockAttributes.isPreview ) {
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
								blockAttributes.showCounts ? item?.count : null
							}
						/>
					),
					value: item?.rating?.toString(),
				};
			} );

		setDisplayedOptions( newOptions );
	}, [
		blockAttributes.showCounts,
		blockAttributes.isPreview,
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
			<Inspector { ...props } />
			<div { ...blockProps }>
				<Disabled>
					{ displayNoProductRatingsNotice && <NoRatings /> }
					<div
						className={ classnames(
							`style-${ blockAttributes.displayStyle }`,
							{
								'is-loading': isLoading,
							}
						) }
					>
						{ blockAttributes.displayStyle === 'dropdown' ? (
							<>
								<PreviewDropdown
									placeholder={
										blockAttributes.selectType === 'single'
											? __(
													'Select a rating',
													'woocommerce'
											  )
											: __(
													'Select ratings',
													'woocommerce'
											  )
									}
								/>
							</>
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
