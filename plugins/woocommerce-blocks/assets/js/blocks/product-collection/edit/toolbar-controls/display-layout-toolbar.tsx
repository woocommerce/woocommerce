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
	DisplayLayoutToolbarProps,
	ProductCollectionDisplayLayout,
	LayoutOptions,
} from '../../types';

const DisplayLayoutToolbar = ( props: DisplayLayoutToolbarProps ) => {
	const { type, columns, shrinkColumns } = props.displayLayout;
	const setDisplayLayout = (
		displayLayout: ProductCollectionDisplayLayout
	) => {
		props.setAttributes( { displayLayout } );
	};

	const displayLayoutControls = [
		{
			icon: list,
			title: __( 'List view', 'woo-gutenberg-products-block' ),
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
			title: __( 'Grid view', 'woo-gutenberg-products-block' ),
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
