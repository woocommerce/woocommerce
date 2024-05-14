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
import {
	TemplateLayoutControlProps,
	LayoutOptions,
	ProductCollectionLayout,
} from '../../types';

const getHelpText = ( layoutOptions: LayoutOptions ) => {
	switch ( layoutOptions ) {
		case LayoutOptions.GRID:
			return __(
				'Display products using rows and columns.',
				'woocommerce'
			);
		case LayoutOptions.STACK:
			return __( 'Display products in a single column.', 'woocommerce' );
		default:
			return '';
	}
};

const DEFAULT_VALUE = LayoutOptions.GRID;

const LayoutOptionsControl = ( props: TemplateLayoutControlProps ) => {
	const { type: layoutType } = props.templateLayout;
	const setTemplateLayout = ( newLayoutType: LayoutOptions ) => {
		props.setAttributes( {
			templateLayout: {
				...props.templateLayout,
				type: newLayoutType,
			} as ProductCollectionLayout,
		} );
	};

	return (
		<ToolsPanelItem
			label={ __( 'Layout', 'woocommerce' ) }
			hasValue={ () => layoutType !== DEFAULT_VALUE }
			isShownByDefault
			onDeselect={ () => {
				setTemplateLayout( LayoutOptions.GRID );
			} }
		>
			<ToggleGroupControl
				label={ __( 'Layout', 'woocommerce' ) }
				isBlock
				onChange={ ( value: LayoutOptions ) => {
					setTemplateLayout( value );
				} }
				help={ getHelpText( layoutType ) }
				value={ layoutType }
			>
				<ToggleGroupControlOption
					value={ LayoutOptions.STACK }
					label={ __( 'Stack', 'woocommerce' ) }
				/>
				<ToggleGroupControlOption
					value={ LayoutOptions.GRID }
					label={ __( 'Grid', 'woocommerce' ) }
				/>
			</ToggleGroupControl>
		</ToolsPanelItem>
	);
};

export default LayoutOptionsControl;
