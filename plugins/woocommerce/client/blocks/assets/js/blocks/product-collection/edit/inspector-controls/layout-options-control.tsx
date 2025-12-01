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
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DisplayLayoutControlProps, LayoutOptions } from '../../types';

const getHelpText = ( layoutOptions: LayoutOptions ) => {
	switch ( layoutOptions ) {
		case LayoutOptions.GRID:
			return __(
				'Display products using rows and columns.',
				'woocommerce'
			);
		case LayoutOptions.STACK:
			return __( 'Display products in a single column.', 'woocommerce' );
		case LayoutOptions.CAROUSEL:
			return __(
				'Display products in a carousel. It displays a single row of products.',
				'woocommerce'
			);
		default:
			return '';
	}
};

const DEFAULT_VALUE = LayoutOptions.GRID;

const LayoutOptionsControl = ( props: DisplayLayoutControlProps ) => {
	const { type, columns, shrinkColumns } = props.displayLayout;
	const setDisplayLayout = ( displayLayout: LayoutOptions ) => {
		props.setAttributes( {
			displayLayout: {
				type: displayLayout,
				columns,
				shrinkColumns,
			},
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Layout', 'woocommerce' ) }
			hasValue={ () => type !== DEFAULT_VALUE }
			isShownByDefault
			onDeselect={ () => {
				setDisplayLayout( LayoutOptions.GRID );
			} }
		>
			<ToggleGroupControl
				label={ __( 'Layout', 'woocommerce' ) }
				isBlock
				onChange={ ( value: LayoutOptions ) => {
					setDisplayLayout( value );
				} }
				help={ getHelpText( type ) }
				value={ type }
			>
				<ToggleGroupControlOption
					value={ LayoutOptions.STACK }
					label={ __( 'Stack', 'woocommerce' ) }
				/>
				<ToggleGroupControlOption
					value={ LayoutOptions.GRID }
					label={ __( 'Grid', 'woocommerce' ) }
				/>
				<ToggleGroupControlOption
					value={ LayoutOptions.CAROUSEL }
					label={ __( 'Carousel', 'woocommerce' ) }
				/>
			</ToggleGroupControl>
		</ToolsPanelItem>
	);
};

export default LayoutOptionsControl;
