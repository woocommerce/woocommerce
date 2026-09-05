/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import clsx from 'clsx';
import { decodeHtmlEntities } from '@woocommerce/utils';
import { getSetting } from '@woocommerce/settings';
import {
	InspectorControls,
	useBlockProps,
	withColors,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseBorderProps as useBorderProps,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalGetSpacingClassesAndStyles as useSpacingProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import './editor.scss';
import { getColorClasses, getColorVars } from './utils';
import {
	getVisualAttributeTermStyle,
	isVisualAttributeTermEmpty,
} from '../../../../base/utils/visual-attribute-terms';

const LOADING_WIDTHS = [
	'42%',
	'67%',
	'31%',
	'55%',
	'73%',
	'28%',
	'48%',
	'61%',
];

const Edit = ( props: EditProps ): JSX.Element => {
	const colorGradientSettings = useMultipleOriginColorsAndGradients();
	const {
		context,
		clientId,
		attributes,
		setAttributes,
		chipText,
		setChipText,
		chipBackground,
		setChipBackground,
		chipBorder,
		setChipBorder,
		selectedChipText,
		setSelectedChipText,
		selectedChipBackground,
		setSelectedChipBackground,
		selectedChipBorder,
		setSelectedChipBorder,
	} = props;
	const {
		customChipText,
		customChipBackground,
		customChipBorder,
		customSelectedChipText,
		customSelectedChipBackground,
		customSelectedChipBorder,
	} = attributes;
	const { isLoading = false, items = [] } =
		context?.[ 'woocommerce/selectableItems' ] ?? {};

	const hasVisualSwatches = items.some( ( item ) => 'visual' in item );

	const globalColors = getSetting< { background?: string; text?: string } >(
		'globalStylesColors',
		{}
	);
	const colorVars = getColorVars( attributes );
	const borderProps = useBorderProps( attributes );
	const spacingProps = useSpacingProps( attributes );

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-chips', {
			'is-loading': isLoading,
			'is-style-swatch': hasVisualSwatches,
			...getColorClasses( attributes ),
		} ),
		style: {
			...colorVars,
			'--wc-product-filter-chips-text':
				colorVars[ '--wc-product-filter-chips-text' ] ||
				globalColors.text ||
				undefined,
			'--wc-product-filter-chips-background':
				colorVars[ '--wc-product-filter-chips-background' ] ||
				globalColors.background ||
				undefined,
		},
	} );

	if ( ! items ) {
		return <></>;
	}

	const threshold = 15;
	const isLongList = items.length > threshold;

	const chipItemClassName = clsx(
		'wc-block-product-filter-chips__item',
		! hasVisualSwatches && borderProps.className,
		! hasVisualSwatches && spacingProps.className
	);
	const chipItemStyle = hasVisualSwatches
		? undefined
		: { ...borderProps.style, ...spacingProps.style };
	const loadingState = LOADING_WIDTHS.map( ( width, i ) => (
		<div
			className={ chipItemClassName }
			key={ i }
			style={ {
				...chipItemStyle,
				/* stylelint-disable */
				width,
			} }
		>
			&nbsp;
		</div>
	) );

	return (
		<>
			<div { ...blockProps }>
				<Disabled>
					<div className="wc-block-product-filter-chips__items">
						{ isLoading && loadingState }
						{ ! isLoading &&
							( isLongList
								? items.slice( 0, threshold )
								: items
							).map( ( item, index ) => (
								<div
									key={ index }
									className={ chipItemClassName }
									style={ chipItemStyle }
									aria-checked={ !! item.selected }
								>
									<span className="wc-block-product-filter-chips__label">
										<span
											className={ clsx(
												'wc-block-product-filter-chips__swatch',
												{
													'wc-block-product-filter-chips__swatch--no-color':
														isVisualAttributeTermEmpty(
															item.visual
														),
												}
											) }
											style={ getVisualAttributeTermStyle(
												item.visual
											) }
											aria-hidden="true"
										/>
										<span className="wc-block-product-filter-chips__text">
											{ typeof item.label === 'string'
												? decodeHtmlEntities(
														item.label
												  )
												: item.label }
										</span>
										{ item.count !== undefined && (
											<span className="wc-block-product-filter-chips__count">
												{ ` (${ item.count })` }
											</span>
										) }
									</span>
								</div>
							) ) }
					</div>
					{ ! isLoading && isLongList && (
						<button className="wc-block-product-filter-chips__show-more">
							{ __( 'Show more…', 'woocommerce' ) }
						</button>
					) }
				</Disabled>
			</div>
			<InspectorControls group="color">
				{ colorGradientSettings.hasColorsOrGradients && (
					<ColorGradientSettingsDropdown
						__experimentalIsRenderedInSidebar
						settings={ [
							...( ! hasVisualSwatches
								? [
										{
											label: __(
												'Unselected Chip Text',
												'woocommerce'
											),
											colorValue:
												chipText.color ||
												customChipText,
											onColorChange: (
												colorValue: string
											) => {
												setChipText( colorValue );
												setAttributes( {
													customChipText: colorValue,
												} );
											},
											resetAllFilter: () => {
												setChipText( '' );
												setAttributes( {
													customChipText: '',
												} );
											},
										},
								  ]
								: [] ),
							{
								label: __(
									'Unselected Chip Border',
									'woocommerce'
								),
								colorValue:
									chipBorder.color || customChipBorder,
								onColorChange: ( colorValue: string ) => {
									setChipBorder( colorValue );
									setAttributes( {
										customChipBorder: colorValue,
									} );
								},
								resetAllFilter: () => {
									setChipBorder( '' );
									setAttributes( {
										customChipBorder: '',
									} );
								},
							},
							...( ! hasVisualSwatches
								? [
										{
											label: __(
												'Unselected Chip Background',
												'woocommerce'
											),
											colorValue:
												chipBackground.color ||
												customChipBackground,
											onColorChange: (
												colorValue: string
											) => {
												setChipBackground( colorValue );
												setAttributes( {
													customChipBackground:
														colorValue,
												} );
											},
											resetAllFilter: () => {
												setChipBackground( '' );
												setAttributes( {
													customChipBackground: '',
												} );
											},
										},
										{
											label: __(
												'Selected Chip Text',
												'woocommerce'
											),
											colorValue:
												selectedChipText.color ||
												customSelectedChipText,
											onColorChange: (
												colorValue: string
											) => {
												setSelectedChipText(
													colorValue
												);
												setAttributes( {
													customSelectedChipText:
														colorValue,
												} );
											},
											resetAllFilter: () => {
												setSelectedChipText( '' );
												setAttributes( {
													customSelectedChipText: '',
												} );
											},
										},
								  ]
								: [] ),
							{
								label: __(
									'Selected Chip Border',
									'woocommerce'
								),
								colorValue:
									selectedChipBorder.color ||
									customSelectedChipBorder,
								onColorChange: ( colorValue: string ) => {
									setSelectedChipBorder( colorValue );
									setAttributes( {
										customSelectedChipBorder: colorValue,
									} );
								},
								resetAllFilter: () => {
									setSelectedChipBorder( '' );
									setAttributes( {
										customSelectedChipBorder: '',
									} );
								},
							},
							...( ! hasVisualSwatches
								? [
										{
											label: __(
												'Selected Chip Background',
												'woocommerce'
											),
											colorValue:
												selectedChipBackground.color ||
												customSelectedChipBackground,
											onColorChange: (
												colorValue: string
											) => {
												setSelectedChipBackground(
													colorValue
												);
												setAttributes( {
													customSelectedChipBackground:
														colorValue,
												} );
											},
											resetAllFilter: () => {
												setSelectedChipBackground( '' );
												setAttributes( {
													customSelectedChipBackground:
														'',
												} );
											},
										},
								  ]
								: [] ),
						] }
						panelId={ clientId }
						{ ...colorGradientSettings }
					/>
				) }
			</InspectorControls>
		</>
	);
};

export default withColors( {
	chipText: 'chip-text',
	chipBorder: 'chip-border',
	chipBackground: 'chip-background',
	selectedChipText: 'selected-chip-text',
	selectedChipBorder: 'selected-chip-border',
	selectedChipBackground: 'selected-chip-background',
} )( Edit );
