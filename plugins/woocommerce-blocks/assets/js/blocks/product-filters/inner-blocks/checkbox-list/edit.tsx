/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
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
import { EditProps } from './types';

const Edit = ( props: EditProps ): JSX.Element => {
	const {
		clientId,
		context,
		attributes,
		setAttributes,
		isSelected,
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
			'has-option-element-border-color':
				optionElementBorder.color || customOptionElementBorder,
			'has-option-element-selected-color':
				optionElementSelected.color || customOptionElementSelected,
			'has-option-element-color':
				optionElement.color || customOptionElement,
		} ),
		style: {
			'--wc-product-filter-checkbox-list-option-element-border':
				optionElementBorder.color || customOptionElementBorder,
			'--wc-product-filter-checkbox-list-option-element-selected':
				optionElementSelected.color || customOptionElementSelected,
			'--wc-product-filter-checkbox-list-option-element':
				optionElement.color || customOptionElement,
		},
	} );

	if ( ! items ) {
		return <></>;
	}

	if ( isLoading ) {
		return <p>Loading</p>;
	}

	const threshold = 15;
	const isLongList = items.length > threshold;

	return (
		<>
			<div { ...blockProps }>
				<ul className="wc-block-product-filter-checkbox-list__list">
					{ ( isLongList ? items.slice( 0, threshold ) : items ).map(
						( item, index ) => (
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
											<svg
												className="wc-block-product-filter-checkbox-list__mark"
												viewBox="0 0 10 8"
												fill="none"
												xmlns="http://www.w3.org/2000/svg"
											>
												<path
													d="M9.25 1.19922L3.75 6.69922L1 3.94922"
													stroke="currentColor"
													strokeWidth="1.5"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
											</svg>
										</span>
									</span>
									<span className="wc-block-product-filter-checkbox-list__text">
										{ item.label }
									</span>
								</label>
							</li>
						)
					) }
				</ul>
				{ isLongList && (
					<span className="wc-block-product-filter-checkbox-list__show-more">
						<small>{ __( 'Show more…', 'woocommerce' ) }</small>
					</span>
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
