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

const getHelpText = ( type: WidthOptions ) => {
	if ( type === WidthOptions.FILL ) {
		return __( 'Stretch to fill available space.', 'woocommerce' );
	}

	return __( 'Specify a fixed width.', 'woocommerce' );
};

const WidthOptionsControl = ( {
	dimensions,
	setAttributes,
}: DimensionsControlProps ) => {
	const { widthType, fixedWidth = '' } = dimensions;
	const setDimensions = ( type: WidthOptions ) => {
		setAttributes( {
			dimensions: {
				...dimensions,
				widthType: type,
			},
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Width', 'woocommerce' ) }
			hasValue={ () => widthType !== WidthOptions.FILL }
			isShownByDefault
		>
			<ToggleGroupControl
				label={ __( 'Width', 'woocommerce' ) }
				value={ widthType }
				help={ getHelpText( widthType ) }
				onChange={ ( value: WidthOptions ) => setDimensions( value ) }
				isBlock
			>
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
					onChange={ ( value: string ) => {
						setAttributes( {
							dimensions: {
								...dimensions,
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
