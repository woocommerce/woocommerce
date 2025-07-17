/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import clsx from 'clsx';
import { decodeHtmlEntities } from '@woocommerce/utils';
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
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import './editor.scss';
import { getColorClasses, getColorVars } from './utils';

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
	const { filterData } = context;
	const { isLoading, items, showCounts } = filterData;

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-chips', {
			'is-loading': isLoading,
			...getColorClasses( attributes ),
		} ),
		style: getColorVars( attributes ),
	} );

	const loadingState = useMemo( () => {
		return [ ...Array( 10 ) ].map( ( _, i ) => (
			<div
				className="wc-block-product-filter-chips__item"
				key={ i }
				style={ {
					/* stylelint-disable */
					width: Math.floor( Math.random() * ( 100 - 25 ) ) + '%',
				} }
			>
				&nbsp;
			</div>
		) );
	}, [] );

	if ( ! items ) {
		return <></>;
	}

	const threshold = 15;
	const isLongList = items.length > threshold;

	return (
		<>
			<div { ...blockProps }>
				<div className="wc-block-product-filter-chips__items">
					{ isLoading && loadingState }
					{ ! isLoading &&
						( isLongList
							? items.slice( 0, threshold )
							: items
						).map( ( item, index ) => (
							<div
								key={ index }
								className="wc-block-product-filter-chips__item"
								aria-checked={ !! item.selected }
							>
								<span className="wc-block-product-filter-chips__label">
									<span className="wc-block-product-filter-chips__text">
										{ typeof item.label === 'string'
											? decodeHtmlEntities( item.label )
											: item.label }
									</span>
									{ showCounts && (
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
			</div>
			<InspectorControls group="color">
				{ colorGradientSettings.hasColorsOrGradients && (
					<ColorGradientSettingsDropdown
						__experimentalIsRenderedInSidebar
						settings={ [
							{
								label: __(
									'Unselected Chip Text',
									'woocommerce'
								),
								colorValue: chipText.color || customChipText,
								onColorChange: ( colorValue: string ) => {
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
							{
								label: __(
									'Unselected Chip Background',
									'woocommerce'
								),
								colorValue:
									chipBackground.color ||
									customChipBackground,
								onColorChange: ( colorValue: string ) => {
									setChipBackground( colorValue );
									setAttributes( {
										customChipBackground: colorValue,
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
								onColorChange: ( colorValue: string ) => {
									setSelectedChipText( colorValue );
									setAttributes( {
										customSelectedChipText: colorValue,
									} );
								},
								resetAllFilter: () => {
									setSelectedChipText( '' );
									setAttributes( {
										customSelectedChipText: '',
									} );
								},
							},
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
							{
								label: __(
									'Selected Chip Background',
									'woocommerce'
								),
								colorValue:
									selectedChipBackground.color ||
									customSelectedChipBackground,
								onColorChange: ( colorValue: string ) => {
									setSelectedChipBackground( colorValue );
									setAttributes( {
										customSelectedChipBackground:
											colorValue,
									} );
								},
								resetAllFilter: () => {
									setSelectedChipBackground( '' );
									setAttributes( {
										customSelectedChipBackground: '',
									} );
								},
							},
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
