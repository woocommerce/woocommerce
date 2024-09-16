/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControlOption` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalUnitControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DimensionsControlProps, WidthOptions } from '../../types';
// import { DisplayLayoutControlProps, LayoutOptions } from '../../types';

const getHelpText = ( type: WidthOptions ) => {
	switch ( type ) {
		case WidthOptions.FIT:
			return __( 'Fit contents.', 'woocommerce' );
		case WidthOptions.FILL:
			return __( 'Stretch to fill available space.', 'woocommerce' );
		case WidthOptions.FIXED:
			return __( 'Specify a fixed width.', 'woocommerce' );
		default:
			return '';
	}
};

const WidthOptionsControl = ( props: DimensionsControlProps ) => {
	const { widthType, fixedWidth = '', minHeight } = props.dimensions;
	const setDimensions = ( type: WidthOptions ) => {
		props.setAttributes( {
			dimensions: {
				...props.dimensions,
				widthType: type,
			},
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Width', 'woocommerce' ) }
			hasValue={ () => true }
		>
			<ToggleGroupControl
				label={ __( 'Width', 'woocommerce' ) }
				value={ 'fit' }
				help={ getHelpText( widthType ) }
				onChange={ ( value: WidthOptions ) => setDimensions( value ) }
				isBlock
			>
				<ToggleGroupControlOption
					value={ WidthOptions.FIT }
					label={ __( 'Fit', 'woocommerce' ) }
				/>
				<ToggleGroupControlOption
					value={ WidthOptions.FILL }
					label={ __( 'Fill', 'woocommerce' ) }
				/>
				<ToggleGroupControlOption
					value={ WidthOptions.FIXED }
					label={ __( 'Fixed', 'woocommerce' ) }
				/>
			</ToggleGroupControl>
			{ widthType === WidthOptions.FIXED && (
				<UnitControl
					onChange={ ( value: number ) => {
						props.setAttributes( {
							dimensions: {
								...props.dimensions,
								fixedWidth: value,
							},
						} );
					} }
					value={ fixedWidth }
				/>
			) }
		</ToolsPanelItem>
	);
};

export default WidthOptionsControl;
