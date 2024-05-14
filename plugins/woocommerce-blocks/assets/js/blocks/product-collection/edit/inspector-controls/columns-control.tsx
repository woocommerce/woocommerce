/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import {
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUnitControl as UnitControl,
	RangeControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	LayoutOptions,
	ProductCollectionLayoutGrid,
	TemplateLayoutControlProps,
} from '../../types';
import {
	DEFAULT_LAYOUT_GRID_COLUMNS,
	DEFAULT_LAYOUT_GRID_MINIMUM_COLUMN_WIDTH,
} from '../../constants';

const columnModeLabel = __( 'Column Mode', 'woocommerce' );
const autoModeLabel = __( 'Auto', 'woocommerce' );
const manualModeLabel = __( 'Manual', 'woocommerce' );
const minimumColumnWidthLabel = __( 'Min. Column Width', 'woocommerce' );
const columnCountLabel = __( 'Columns', 'woocommerce' );

const ColumnsControl = ( props: TemplateLayoutControlProps ) => {
	const templateLayout = props.templateLayout as ProductCollectionLayoutGrid;

	// Rather than serializing whether or not they're using
	// automatic columns we can just check the current
	// layout. When they're using a minimum width we
	// are in auto mode, and if nothing is set, we
	// will just default to auto mode.
	const [ isAutoColumnLayout, setIsAutoColumnLayout ] = useState(
		templateLayout.minimumColumnWidth !== undefined ||
			templateLayout.columnCount === undefined
	);

	const setTemplateLayout = (
		newTemplateLayout: Partial< ProductCollectionLayoutGrid >
	) => {
		props.setAttributes( {
			templateLayout: {
				type: LayoutOptions.GRID,
				...newTemplateLayout,
			},
		} );
	};

	// Keep track of the last layout values so that we can switch back and forth
	// without needing to serialize the previous value to the block.
	const lastLayoutValue = useRef(
		isAutoColumnLayout
			? DEFAULT_LAYOUT_GRID_COLUMNS
			: DEFAULT_LAYOUT_GRID_MINIMUM_COLUMN_WIDTH
	);

	const onIsAutoColumnLayout = ( value: boolean ) => {
		// When we switch back and forth we should restore the last layout value
		// to the template layout and store the opposite value in case we
		// want to switch back to the other and not lose the value.
		if ( value ) {
			setTemplateLayout( {
				minimumColumnWidth: lastLayoutValue.current as string,
			} );
			lastLayoutValue.current = templateLayout.columnCount as number;
		} else {
			setTemplateLayout( {
				columnCount: lastLayoutValue.current as number,
			} );
			lastLayoutValue.current =
				templateLayout.minimumColumnWidth as string;
		}

		setIsAutoColumnLayout( value );
	};

	const onColumnCountChange = ( value: number ) =>
		props.setAttributes( {
			templateLayout: {
				type: LayoutOptions.GRID,
				columnCount: value,
			},
		} );

	const onMinimumColumnWidthChange = ( value: string ) =>
		props.setAttributes( {
			templateLayout: {
				type: LayoutOptions.GRID,
				minimumColumnWidth: value,
			},
		} );

	return (
		<>
			<ToolsPanelItem
				label={ columnModeLabel }
				hasValue={ () => true }
				isShownByDefault
			>
				<ToggleGroupControl
					label={ columnModeLabel }
					value={ isAutoColumnLayout }
					onChange={ onIsAutoColumnLayout }
					isBlock
				>
					<ToggleGroupControlOption
						value={ true }
						label={ autoModeLabel }
					/>
					<ToggleGroupControlOption
						value={ false }
						label={ manualModeLabel }
					/>
				</ToggleGroupControl>
			</ToolsPanelItem>

			{ isAutoColumnLayout && (
				<ToolsPanelItem
					label={ minimumColumnWidthLabel }
					hasValue={ () => true }
					isShownByDefault
				>
					<UnitControl
						label={ minimumColumnWidthLabel }
						value={ templateLayout.minimumColumnWidth as string }
						onChange={ onMinimumColumnWidthChange }
					/>
				</ToolsPanelItem>
			) }

			{ ! isAutoColumnLayout && (
				<ToolsPanelItem
					label={ columnCountLabel }
					hasValue={ () => true }
					isShownByDefault
				>
					<RangeControl
						label={ columnCountLabel }
						onChange={ onColumnCountChange }
						value={ templateLayout.columnCount as number }
						min={ 2 }
						max={ 6 }
					/>
				</ToolsPanelItem>
			) }
		</>
	);
};

export default ColumnsControl;
