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

