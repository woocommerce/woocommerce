/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	withColors,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';

/**
 * Ideally, this component should belong  to packages/interactivity-components.
 * But we haven't export it as a packages so we place it here temporary.
 */
export const Preview = ( { items }: { items: string[] } ) => {
	const threshold = 15;
	const isLongList = items.length > threshold;
	return (
		<div className="wc-block-interactivity-components-checkbox-list">
			<ul className="wc-block-interactivity-components-checkbox-list__list">
				{ ( isLongList ? items.slice( 0, threshold ) : items ).map(
					( item, index ) => (
						<li
							key={ index }
							className="wc-block-interactivity-components-checkbox-list__item"
						>
							<label
								htmlFor={ `interactive-checkbox-${ index }` }
								className=" wc-block-interactivity-components-checkbox-list__label"
							>
								<span className="wc-block-interactive-components-checkbox-list__input-wrapper">
									<input
										name={ `interactive-checkbox-${ index }` }
										type="checkbox"
										className="wc-block-interactivity-components-checkbox-list__input"
									/>
									<svg
										className="wc-block-interactivity-components-checkbox-list__mark"
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
								<span className="wc-block-interactivity-components-checkbox-list__text">
									{ item }
								</span>
							</label>
						</li>
					)
				) }
			</ul>
			<span className="wc-block-interactivity-components-checkbox-list__show-more">
				<small>{ __( 'Show more…', 'woocommerce' ) }</small>
			</span>
		</div>
	);
};

type Color = {
	slug: string;
	class: string;
	name: string;
	color: string;
};

export type EditProps = {
	optionBorder: Color;
	setOptionBorder: ( value: string ) => void;
	optionBackground: Color;
	setOptionBackground: ( value: string ) => void;
	optionSelected: Color;
	setOptionSelected: ( value: string ) => void;
};

export type Attributes = {
	optionBorder: string;
	customOptionBorder: string;
	opionBackground: string;
	customOptionBackground: string;
	optionSelected: string;
	customOptionSelected: string;
};

export const attributesConfig = {
	optionBorder: {
		type: 'string',
	},
	customOptionBorder: {
		type: 'string',
	},
	optionBackground: {
		type: 'string',
	},
	customOptionBackground: {
		type: 'string',
	},
	optionSelected: {
		type: 'string',
	},
	customOptionSelected: {
		type: 'string',
	},
};

export const Inspector = < T extends Record< string, unknown > >(
	props: BlockEditProps< T | Attributes > & EditProps
) => {
	const {
		attributes,
		setAttributes,
		optionBorder,
		setOptionBorder,
		optionBackground,
		setOptionBackground,
		optionSelected,
		setOptionSelected,
		clientId,
	} = props;

	const { customOptionBorder, customOptionBackground, customOptionSelected } =
		attributes;
	const colorGradientSettings = useMultipleOriginColorsAndGradients();

	return (
		<InspectorControls group="color">
			<ColorGradientSettingsDropdown
				settings={ [
					{
						label: __( 'Option Element Border', 'woocommerce' ),
						colorValue: optionBorder.color || customOptionBorder,
						isShownByDefault: false,
						enableAlpha: true,
						onColorChange: ( value: string ) => {
							setOptionBorder( value );
							setAttributes( { customOptionBorder: value } );
						},
					},
					{
						label: __( 'Option Element Background', 'woocommerce' ),
						colorValue:
							optionBackground.color || customOptionBackground,
						isShownByDefault: false,
						enableAlpha: true,
						onColorChange: ( value: string ) => {
							setOptionBackground( value );
							setAttributes( { customOptionBackground: value } );
						},
					},
					{
						label: __( 'Option Element Selected', 'woocommerce' ),
						colorValue:
							optionSelected.color || customOptionSelected,
						isShownByDefault: false,
						enableAlpha: true,
						onColorChange: ( value: string ) => {
							setOptionSelected( value );
							setAttributes( { customOptionSelected: value } );
						},
					},
				] }
				panelId={ clientId }
				{ ...colorGradientSettings }
			/>
		</InspectorControls>
	);
};

export const withCheckboxListColors = withColors( {
	optionBorder: 'option-border',
	optionBackground: 'option-background',
	optionSelected: 'option-selected',
} );
