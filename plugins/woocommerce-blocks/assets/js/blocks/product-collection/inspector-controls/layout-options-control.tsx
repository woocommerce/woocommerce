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
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DisplayLayoutControlProps, LayoutOptions } from '../types';

const getHelpText = ( layoutOptions: LayoutOptions ) => {
	switch ( layoutOptions ) {
		case LayoutOptions.GRID:
			return __(
				'Display products using rows and columns.',
				'woo-gutenberg-products-block'
			);
		case LayoutOptions.STACK:
			return __(
				'Display products in a single column.',
				'woo-gutenberg-products-block'
			);
		default:
			return '';
	}
};

const DEFAULT_VALUE = LayoutOptions.GRID;

const LayoutOptionsControl = ( props: DisplayLayoutControlProps ) => {
	const { type, columns } = props.displayLayout;
	const setDisplayLayout = ( displayLayout: LayoutOptions ) => {
		props.setAttributes( {
			displayLayout: {
				type: displayLayout,
				columns,
			},
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Layout', 'woo-gutenberg-products-block' ) }
			hasValue={ () => type !== DEFAULT_VALUE }
			isShownByDefault
			onDeselect={ () => {
				setDisplayLayout( LayoutOptions.GRID );
			} }
		>
			<ToggleGroupControl
				label={ __( 'Layout', 'woo-gutenberg-products-block' ) }
				isBlock
				onChange={ ( value: LayoutOptions ) => {
					setDisplayLayout( value );
				} }
				help={ getHelpText( type ) }
				value={ type }
			>
				<ToggleGroupControlOption
					value={ LayoutOptions.STACK }
					label={ __( 'Stack', 'woo-gutenberg-products-block' ) }
				/>
				<ToggleGroupControlOption
					value={ LayoutOptions.GRID }
					label={ __( 'Grid', 'woo-gutenberg-products-block' ) }
				/>
			</ToggleGroupControl>
		</ToolsPanelItem>
	);
};

export default LayoutOptionsControl;
