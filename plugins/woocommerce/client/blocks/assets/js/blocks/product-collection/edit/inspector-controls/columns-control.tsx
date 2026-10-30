/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	RangeControl,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DisplayLayoutControlProps, LayoutOptions } from '../../types';
import { getDefaultDisplayLayout, sanitizeDisplayLayout } from '../../utils';

const columnsLabel = __( 'Columns', 'woocommerce' );
const toggleLabel = __( 'Responsive', 'woocommerce' );
const toggleHelp = __(
	'Automatically adjust the number of columns to better fit smaller screens.',
	'woocommerce'
);

interface ColumnsControlProps extends DisplayLayoutControlProps {
	maxColumns?: number;
	hideResponsiveToggle?: boolean;
}

const ColumnsControl = ( props: ColumnsControlProps ) => {
	const { maxColumns, hideResponsiveToggle } = props;
	const { type, columns, shrinkColumns } = props.displayLayout;
	const showColumnsControl = type === LayoutOptions.GRID;

	const defaultLayout = getDefaultDisplayLayout();
	const columnCount = columns ?? defaultLayout.columns;

	const onShrinkColumnsToggleChange = ( value: boolean ) => {
		props.setAttributes( {
			displayLayout: sanitizeDisplayLayout( {
				...props.displayLayout,
				shrinkColumns: value,
			} ),
		} );
	};

	const onPanelDeselect = () => {
		props.setAttributes( {
			displayLayout: sanitizeDisplayLayout( defaultLayout ),
		} );
	};

	const onColumnsChange = ( value?: number ) => {
		if ( value === undefined ) {
			return;
		}
		props.setAttributes( {
			displayLayout: sanitizeDisplayLayout( {
				...props.displayLayout,
				columns: value,
			} ),
		} );
	};

	const defaultMaxColumns = 6;
	const effectiveMaxColumns = maxColumns ?? defaultMaxColumns;

	return showColumnsControl ? (
		<>
			<ToolsPanelItem
				label={ columnsLabel }
				hasValue={ () => defaultLayout.columns !== columnCount }
				isShownByDefault
				onDeselect={ onPanelDeselect }
			>
				<RangeControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ columnsLabel }
					onChange={ onColumnsChange }
					value={ columnCount }
					min={ 1 }
					max={ Math.max( effectiveMaxColumns, columnCount ) }
				/>
			</ToolsPanelItem>
			{ ! hideResponsiveToggle && (
				<ToolsPanelItem
					label={ toggleLabel }
					hasValue={ () =>
						defaultLayout?.shrinkColumns !== shrinkColumns
					}
					isShownByDefault
					onDeselect={ onPanelDeselect }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						checked={ !! shrinkColumns }
						label={ toggleLabel }
						help={ toggleHelp }
						onChange={ onShrinkColumnsToggleChange }
					/>
				</ToolsPanelItem>
			) }
		</>
	) : null;
};

export default ColumnsControl;
