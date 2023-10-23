/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	RangeControl,
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DisplayLayoutToolbarProps } from '../types';
import { getDefaultDisplayLayout } from '../constants';

const toggleLabel = __(
	'Shrink columns to fit',
	'woo-gutenberg-products-block'
);

const toggleHelp = __(
	'Reduce the number of columns to better fit smaller screens and spaces.',
	'woo-gutenberg-products-block'
);

const getColumnsLabel = ( shrinkColumns: boolean ) =>
	shrinkColumns
		? __( 'Max Columns', 'woo-gutenberg-products-block' )
		: __( 'Columns', 'woo-gutenberg-products-block' );

const ColumnsControl = ( props: DisplayLayoutToolbarProps ) => {
	const { type, columns, shrinkColumns } = props.displayLayout;
	const showColumnsControl = type === 'flex';

	const defaultLayout = getDefaultDisplayLayout();

	const onShrinkColumnsToggleChange = ( value: boolean ) => {
		props.setAttributes( {
			displayLayout: {
				...props.displayLayout,
				shrinkColumns: value,
			},
		} );
	};

	const onPanelDeselect = () => {
		props.setAttributes( {
			displayLayout: defaultLayout,
		} );
	};

	const onColumnsChange = ( value: number ) =>
		props.setAttributes( {
			displayLayout: {
				...props.displayLayout,
				columns: value,
			},
		} );

	return showColumnsControl ? (
		<>
			<ToolsPanelItem
				hasValue={ () =>
					defaultLayout?.shrinkColumns !== shrinkColumns
				}
				isShownByDefault
				onDeselect={ onPanelDeselect }
			>
				<ToggleControl
					checked={ !! shrinkColumns }
					label={ toggleLabel }
					help={ toggleHelp }
					onChange={ onShrinkColumnsToggleChange }
				/>
			</ToolsPanelItem>
			<ToolsPanelItem
				hasValue={ () =>
					defaultLayout?.columns !== columns ||
					defaultLayout?.type !== type
				}
				isShownByDefault
				onDeselect={ onPanelDeselect }
			>
				<RangeControl
					label={ getColumnsLabel( !! shrinkColumns ) }
					onChange={ onColumnsChange }
					value={ columns }
					min={ 2 }
					max={ Math.max( 6, columns ) }
				/>
			</ToolsPanelItem>
		</>
	) : null;
};

export default ColumnsControl;
