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
import { getCarouselColumns } from '../../constants';
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
	const { type, columns, shrinkColumns, carouselColumns } =
		props.displayLayout;
	const isGridLayout = type === LayoutOptions.GRID;
	const isCarouselLayout = type === LayoutOptions.CAROUSEL;
	const showColumnsControl = isGridLayout || isCarouselLayout;

	const defaultLayout = getDefaultDisplayLayout();
	const savedCarouselColumns = getCarouselColumns( carouselColumns );
	const defaultMaxColumns = 6;
	const effectiveMaxColumns = maxColumns ?? defaultMaxColumns;
	// Unset carouselColumns keeps the responsive widths. The slider still needs a number.
	const columnCount = isCarouselLayout
		? ( savedCarouselColumns ??
			Math.min( defaultLayout.columns, effectiveMaxColumns ) )
		: ( columns ?? defaultLayout.columns );

	const onShrinkColumnsToggleChange = ( value: boolean ) => {
		props.setAttributes( {
			displayLayout: sanitizeDisplayLayout( {
				...props.displayLayout,
				shrinkColumns: value,
				// The carousel maximum has to be saved before shrinking can apply.
				...( isCarouselLayout &&
					value &&
					savedCarouselColumns === undefined && {
						carouselColumns: columnCount,
					} ),
			} ),
		} );
	};

	const onPanelDeselect = () => {
		if ( isCarouselLayout ) {
			props.setAttributes( {
				displayLayout: sanitizeDisplayLayout( {
					...props.displayLayout,
					carouselColumns: undefined,
				} ),
			} );
			return;
		}

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
				...( isCarouselLayout
					? { carouselColumns: value }
					: { columns: value } ),
			} ),
		} );
	};

	const hasCustomColumnCount = () =>
		isCarouselLayout
			? savedCarouselColumns !== undefined
			: defaultLayout.columns !== columnCount;

	const hasCustomShrinkColumns = () =>
		isCarouselLayout
			? !! shrinkColumns
			: defaultLayout?.shrinkColumns !== shrinkColumns;

	const onShrinkColumnsDeselect = () => {
		if ( ! isCarouselLayout ) {
			onPanelDeselect();
			return;
		}

		props.setAttributes( {
			displayLayout: sanitizeDisplayLayout( {
				...props.displayLayout,
				shrinkColumns: false,
			} ),
		} );
	};

	return showColumnsControl ? (
		<>
			<ToolsPanelItem
				label={ columnsLabel }
				hasValue={ hasCustomColumnCount }
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
					hasValue={ hasCustomShrinkColumns }
					isShownByDefault
					onDeselect={ onShrinkColumnsDeselect }
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
