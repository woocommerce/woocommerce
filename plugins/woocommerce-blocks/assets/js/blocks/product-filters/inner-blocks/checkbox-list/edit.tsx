/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';
import { checkMark } from '@woocommerce/icons';
import { useMemo } from '@wordpress/element';
import {
	useBlockProps,
	withColors,
	InspectorControls,
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
import './style.scss';
import './editor.scss';
import { EditProps } from './types';
import { getColorClasses, getColorVars } from './utils';

const Edit = ( props: EditProps ): JSX.Element => {
	const {
		clientId,
		context,
		attributes,
		setAttributes,
		optionElementBorder,
		setOptionElementBorder,
		optionElementSelected,
		setOptionElementSelected,
		optionElement,
		setOptionElement,
	} = props;

	const {
		customOptionElementBorder,
		customOptionElementSelected,
		customOptionElement,
	} = attributes;
	const { filterData } = context;
	const { isLoading, items } = filterData;

	const colorGradientSettings = useMultipleOriginColorsAndGradients();
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-checkbox-list', {
			'is-loading': isLoading,
			...getColorClasses( attributes ),
		} ),
		style: getColorVars( attributes ),
	} );

	const loadingState = useMemo( () => {
		return [ ...Array( 5 ) ].map( ( x, i ) => (
			<li
				key={ i }
				style={ {
					/* stylelint-disable */
					width: Math.floor( Math.random() * ( 100 - 25 ) ) + '%',
				} }
			>
				&nbsp;
			</li>
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
				<ul className="wc-block-product-filter-checkbox-list__list">
					{ isLoading && loadingState }
					{ ! isLoading &&
						( isLongList
							? items.slice( 0, threshold )
							: items
						).map( ( item, index ) => (
							<li
								key={ index }
								className="wc-block-product-filter-checkbox-list__item"
							>
								<label
									htmlFor={ `interactive-checkbox-${ index }` }
									className=" wc-block-product-filter-checkbox-list__label"
								>
									<span className="wc-block-interactive-components-checkbox-list__input-wrapper">
										<span className="wc-block-product-filter-checkbox-list__input-wrapper">
											<input
												name={ `interactive-checkbox-${ index }` }
												type="checkbox"
												className="wc-block-product-filter-checkbox-list__input"
												defaultChecked={
													!! item.selected
												}
											/>
											<Icon
												className="wc-block-product-filter-checkbox-list__mark"
												icon={ checkMark }
											/>
										</span>
									</span>
									<span className="wc-block-product-filter-checkbox-list__text">
										{ item.label }
									</span>
								</label>
							</li>
						) ) }
				</ul>
				{ ! isLoading && isLongList && (
					<button className="wc-block-product-filter-checkbox-list__show-more">
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
									'Option Element Border',
									'woocommerce'
								),
								colorValue:
									optionElementBorder.color ||
									customOptionElementBorder,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setOptionElementBorder( colorValue );
									setAttributes( {
										customOptionElementBorder: colorValue,
									} );
								},
								resetAllFilter: () => {
									setOptionElementBorder( '' );
									setAttributes( {
										customOptionElementBorder: '',
									} );
								},
							},
							{
								label: __(
									'Option Element (Selected)',
									'woocommerce'
								),
								colorValue:
									optionElementSelected.color ||
									customOptionElementSelected,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setOptionElementSelected( colorValue );
									setAttributes( {
										customOptionElementSelected: colorValue,
									} );
								},
								resetAllFilter: () => {
									setOptionElementSelected( '' );
									setAttributes( {
										customOptionElementSelected: '',
									} );
								},
							},
							{
								label: __( 'Option Element', 'woocommerce' ),
								colorValue:
									optionElement.color || customOptionElement,
								isShownByDefault: true,
								enableAlpha: true,
								onColorChange: ( colorValue: string ) => {
									setOptionElement( colorValue );
									setAttributes( {
										customOptionElement: colorValue,
									} );
								},
								resetAllFilter: () => {
									setOptionElement( '' );
									setAttributes( {
										customOptionElement: '',
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
	optionElementBorder: 'option-element-border',
	optionElementSelected: 'option-element-border',
	optionElement: 'option-element',
} )( Edit );
