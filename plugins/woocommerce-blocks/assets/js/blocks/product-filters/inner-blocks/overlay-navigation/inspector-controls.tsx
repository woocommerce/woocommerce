/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { filter, filterThreeLines } from '@woocommerce/icons';
import { Icon, menu, settings } from '@wordpress/icons';
import {
	PanelBody,
	RadioControl,
	SelectControl,
	RangeControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';

interface ButtonStyle {
	value: string;
	label: string;
}

interface InspectorProps {
	attributes: BlockEditProps< BlockAttributes >[ 'attributes' ];
	setAttributes: BlockEditProps< BlockAttributes >[ 'setAttributes' ];
	buttonStyles: ButtonStyle[];
}

export const Inspector = ( {
	attributes,
	setAttributes,
	buttonStyles,
}: InspectorProps ) => {
	const { navigationStyle, buttonStyle, iconSize, overlayIcon, triggerType } =
		attributes;
	return (
		<InspectorControls group="styles">
			<PanelBody title={ __( 'Style', 'woocommerce' ) }>
				<RadioControl
					selected={ navigationStyle }
					options={ [
						{
							label: __( 'Label and icon', 'woocommerce' ),
							value: 'label-and-icon',
						},
						{
							label: __( 'Label only', 'woocommerce' ),
							value: 'label-only',
						},
						{
							label: __( 'Icon only', 'woocommerce' ),
							value: 'icon-only',
						},
					] }
					onChange={ (
						value: BlockAttributes[ 'navigationStyle' ]
					) =>
						setAttributes( {
							navigationStyle: value,
						} )
					}
				/>

				{ buttonStyles.length <= 3 && (
					<ToggleGroupControl
						label={ __( 'Button', 'woocommerce' ) }
						value={ buttonStyle }
						isBlock
						onChange={ (
							value: BlockAttributes[ 'buttonStyle' ]
						) =>
							setAttributes( {
								buttonStyle: value,
							} )
						}
					>
						{ buttonStyles.map( ( option ) => (
							<ToggleGroupControlOption
								key={ option.value }
								label={ option.label }
								value={ option.value }
							/>
						) ) }
					</ToggleGroupControl>
				) }
				{ buttonStyles.length > 3 && (
					<SelectControl
						label={ __( 'Button', 'woocommerce' ) }
						value={ buttonStyle }
						options={ buttonStyles }
						onChange={ (
							value: BlockAttributes[ 'buttonStyle' ]
						) =>
							setAttributes( {
								buttonStyle: value,
							} )
						}
					/>
				) }

				{ triggerType === 'open-overlay' &&
					navigationStyle !== 'label-only' && (
						<ToggleGroupControl
							label={ __( 'Icon', 'woocommerce' ) }
							className="wc-block-editor-product-filters__overlay-button-toggle"
							isBlock={ true }
							value={ overlayIcon }
							onChange={ (
								value: BlockAttributes[ 'overlayIcon' ]
							) => {
								setAttributes( {
									overlayIcon: value,
								} );
							} }
						>
							<ToggleGroupControlOption
								value={ 'filter-icon-1' }
								aria-label={ __(
									'Filter icon 1',
									'woocommerce'
								) }
								label={ <Icon size={ 32 } icon={ filter } /> }
							/>
							<ToggleGroupControlOption
								value={ 'filter-icon-2' }
								aria-label={ __(
									'Filter icon 2',
									'woocommerce'
								) }
								label={
									<Icon
										size={ 32 }
										icon={ filterThreeLines }
									/>
								}
							/>
							<ToggleGroupControlOption
								value={ 'filter-icon-3' }
								aria-label={ __(
									'Filter icon 3',
									'woocommerce'
								) }
								label={ <Icon size={ 32 } icon={ menu } /> }
							/>
							<ToggleGroupControlOption
								value={ 'filter-icon-4' }
								aria-label={ __(
									'Filter icon 4',
									'woocommerce'
								) }
								label={ <Icon size={ 32 } icon={ settings } /> }
							/>
						</ToggleGroupControl>
					) }

				{ navigationStyle !== 'label-only' && (
					<RangeControl
						className="wc-block-product-filters-overlay-navigation__icon-size-control"
						label={ __( 'Icon Size', 'woocommerce' ) }
						value={ iconSize }
						onChange={ ( newSize: number ) => {
							setAttributes( { iconSize: newSize } );
						} }
						min={ 0 }
						max={ 300 }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
};
