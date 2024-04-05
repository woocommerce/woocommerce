/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
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

const autoColumnsLabel = __( 'Column Mode', 'woocommerce' );
const autoModeLabel = __( 'Auto', 'woocommerce' );
const manualModeLabel = __( 'Manual', 'woocommerce' );
const minimumColumnWidthLabel = __( 'Minimum Column Width', 'woocommerce' );
const columnsLabel = __( 'Columns', 'woocommerce' );

const ColumnsControl = ( props: TemplateLayoutControlProps ) => {
	const templateLayout = props.templateLayout as ProductCollectionLayoutGrid;

	// Rather than serializing whether or not they're using
	// automatic columns we can just check the current
	// layout. When they're using a minimum width we
	// are in auto mode, and if nothing is set, we
	// will just default to auto mode.
	const [ autoColumns, setAutoColumns ] = useState(
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
	const [ lastLayout, setLastLayout ] = useState(
		autoColumns
			? DEFAULT_LAYOUT_GRID_COLUMNS
			: DEFAULT_LAYOUT_GRID_MINIMUM_COLUMN_WIDTH
	);

	const onAutoColumnsChange = ( value: boolean ) => {
		// When we switch back and forth we should restore the last layout value
		// to the template layout and store the opposite value in case we
		// want to switch back to the other and not lose the value.
		if ( value ) {
			setTemplateLayout( {
				minimumColumnWidth: lastLayout as string,
			} );
			setLastLayout( templateLayout.columnCount as number );
		} else {
			setTemplateLayout( {
				columnCount: lastLayout as number,
			} );
			setLastLayout( templateLayout.minimumColumnWidth as string );
		}

		setAutoColumns( value );
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
				label={ autoColumnsLabel }
				hasValue={ () => true }
				isShownByDefault
			>
				<ToggleGroupControl
					label={ autoColumnsLabel }
					value={ autoColumns }
					onChange={ onAutoColumnsChange }
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

			{ autoColumns && (
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

			{ ! autoColumns && (
				<ToolsPanelItem
					label={ columnsLabel }
					hasValue={ () => true }
					isShownByDefault
				>
					<RangeControl
						label={ columnsLabel }
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
