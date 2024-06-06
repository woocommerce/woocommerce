/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToolbarGroup } from '@wordpress/components';
import { list, grid } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import {
	DisplayLayoutControlProps,
	ProductCollectionDisplayLayout,
	LayoutOptions,
} from '../../types';

const DisplayLayoutToolbar = ( props: DisplayLayoutControlProps ) => {
	const { type, columns, shrinkColumns } = props.displayLayout;
	const setDisplayLayout = (
		displayLayout: ProductCollectionDisplayLayout
	) => {
		props.setAttributes( { displayLayout } );
	};

	const displayLayoutControls = [
		{
			icon: list,
			title: __( 'List view', 'woocommerce' ),
			onClick: () =>
				setDisplayLayout( {
					type: LayoutOptions.STACK,
					columns,
					shrinkColumns,
				} ),
			isActive: type === LayoutOptions.STACK,
		},
		{
			icon: grid,
			title: __( 'Grid view', 'woocommerce' ),
			onClick: () =>
				setDisplayLayout( {
					type: LayoutOptions.GRID,
					columns,
					shrinkColumns,
				} ),
			isActive: type === LayoutOptions.GRID,
		},
	];

	return <ToolbarGroup controls={ displayLayoutControls } />;
};

export default DisplayLayoutToolbar;
