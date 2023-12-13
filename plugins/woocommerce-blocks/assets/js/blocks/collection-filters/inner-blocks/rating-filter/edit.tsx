/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, chevronDown } from '@wordpress/icons';
import classnames from 'classnames';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import Rating, {
	RatingValues,
} from '@woocommerce/base-components/product-rating';
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isBoolean, isObject, objectHasProp } from '@woocommerce/types';
import { useState, useMemo, useEffect } from '@wordpress/element';
import { CheckboxList } from '@woocommerce/blocks-components';
import FormTokenField from '@woocommerce/base-components/form-token-field';
import type { ReactElement } from 'react';
import {
	Disabled,
	Notice,
	PanelBody,
	ToggleControl,
	withSpokenMessages,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { previewOptions } from './preview';
import './style.scss';
import { Attributes } from './types';
import { formatSlug, getActiveFilters, generateUniqueId } from './utils';
import { useSetWraperVisibility } from '../../../filter-wrapper/context';
import './editor.scss';

const noRatingsNotice = (
	<Notice status="warning" isDismissible={ false }>
		<p>
			{ __(
				"Your store doesn't have any products with ratings yet. This filter option will display when a product receives a review.",
				'woocommerce'
			) }
		</p>
	</Notice>
);

/**
 * Component displaying a rating filter in the block editor.
 */
const RatingFilterEditBlock = ( {
	attributes: blockAttributes,
	noRatingsNotice = null,
}: {
	attributes: Attributes;
	noRatingsNotice?: ReactElement | null;
} ) => {
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

	const [ checked, setChecked ] = useState( initialFilters );

	const [ productRatingsQuery, setProductRatingsQuery ] = useQueryStateByKey(
		'rating',
		initialFilters
	);

	/*
		FormTokenField forces the dropdown to reopen on reset, so we create a unique ID to use as the components key.
		This will force the component to remount on reset when we change this value.
		More info: https://github.com/woocommerce/woocommerce-blocks/pull/6920#issuecomment-1222402482
	 */
	const [ remountKey, setRemountKey ] = useState( generateUniqueId() );
	const [ displayNoProductRatingsNotice, setDisplayNoProductRatingsNotice ] =
		useState( false );

	const multiple = blockAttributes.selectType !== 'single';

	const showChevron = multiple
		? ! isLoading && checked.length < displayedOptions.length
		: ! isLoading && checked.length === 0;

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
		setRemountKey( generateUniqueId() );
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
			{ displayNoProductRatingsNotice && noRatingsNotice }
			<div
				className={ classnames(
					'wc-block-rating-filter',
					`style-${ blockAttributes.displayStyle }`,
					{
						'is-loading': isLoading,
					}
				) }
			>
				{ blockAttributes.displayStyle === 'dropdown' ? (
					<>
						<FormTokenField
							key={ remountKey }
							className={ classnames( {
								'single-selection': ! multiple,
								'is-loading': isLoading,
							} ) }
							style={ {
								borderStyle: 'none',
							} }
							suggestions={ displayedOptions
								.filter(
									( option ) =>
										! checked.includes( option.value )
								)
								.map( ( option ) => option.value ) }
							disabled={ isLoading }
							placeholder={ __( 'Select Rating', 'woocommerce' ) }
							onChange={ () => {
								// noop
							} }
							value={ checked }
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore - FormTokenField doesn't accept custom components, forcing it here to display component
							displayTransform={ ( value ) => {
								const resultWithZeroCount = {
									value,
									label: (
										<Rating
											key={
												Number( value ) as RatingValues
											}
											rating={
												Number( value ) as RatingValues
											}
											ratedProductsCount={ 0 }
										/>
									),
								};
								const resultWithNonZeroCount =
									displayedOptions.find(
										( option ) => option.value === value
									);

								const displayedResult =
									resultWithNonZeroCount ||
									resultWithZeroCount;

								const { label, value: rawValue } =
									displayedResult;

								// A label - JSX component - is extended with faked string methods to allow using JSX element as an option in FormTokenField
								const extendedLabel = Object.assign(
									{},
									label,
									{
										toLocaleLowerCase: () => rawValue,
										substring: (
											start: number,
											end: number
										) =>
											start === 0 && end === 1
												? label
												: '',
									}
								);
								return extendedLabel;
							} }
							saveTransform={ formatSlug }
							messages={ {
								added: __(
									'Rating filter added.',
									'woocommerce'
								),
								removed: __(
									'Rating filter removed.',
									'woocommerce'
								),
								remove: __(
									'Remove rating filter.',
									'woocommerce'
								),
								__experimentalInvalid: __(
									'Invalid rating filter.',
									'woocommerce'
								),
							} }
						/>
						{ showChevron && (
							<Icon icon={ chevronDown } size={ 30 } />
						) }
					</>
				) : (
					<CheckboxList
						className={ 'wc-block-rating-filter-list' }
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
		</>
	);
};

const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< Attributes > ) => {
	const {
		className,
		displayStyle,
		showCounts,
		showFilterButton,
		selectType,
	} = attributes;

	const blockProps = useBlockProps( {
		className: classnames( 'wc-block-rating-filter', className ),
	} );

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Display Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Display product count', 'woocommerce' ) }
						checked={ showCounts }
						onChange={ () =>
							setAttributes( {
								showCounts: ! showCounts,
							} )
						}
					/>
					<ToggleGroupControl
						label={ __(
							'Allow selecting multiple options?',
							'woocommerce'
						) }
						value={ selectType || 'multiple' }
						onChange={ ( value: string ) =>
							setAttributes( {
								selectType: value,
							} )
						}
						className="wc-block-attribute-filter__multiple-toggle"
					>
						<ToggleGroupControlOption
							value="multiple"
							label={ __( 'Multiple', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="single"
							label={ __( 'Single', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
					<ToggleGroupControl
						label={ __( 'Display Style', 'woocommerce' ) }
						value={ displayStyle }
						onChange={ ( value: string ) =>
							setAttributes( {
								displayStyle: value,
							} )
						}
						className="wc-block-attribute-filter__display-toggle"
					>
						<ToggleGroupControlOption
							value="list"
							label={ __( 'List', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="dropdown"
							label={ __( 'Dropdown', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
					<ToggleControl
						label={ __(
							"Show 'Apply filters' button",
							'woocommerce'
						) }
						help={ __(
							'Products will update when the button is clicked.',
							'woocommerce'
						) }
						checked={ showFilterButton }
						onChange={ ( value ) =>
							setAttributes( {
								showFilterButton: value,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
		);
	};

	return (
		<>
			{ getInspectorControls() }
			{
				<div { ...blockProps }>
					<Disabled>
						<RatingFilterEditBlock
							attributes={ attributes }
							noRatingsNotice={ noRatingsNotice }
						/>
					</Disabled>
				</div>
			}
		</>
	);
};

export default withSpokenMessages( Edit );
