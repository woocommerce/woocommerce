/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToolbarGroup } from '@wordpress/components';
import { list, grid } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { ProductCollectionDisplayLayout } from '../types';

type DisplayLayoutObject = {
	displayLayout: ProductCollectionDisplayLayout;
};

type DisplayLayoutControlProps = DisplayLayoutObject & {
	setAttributes: ( attrs: DisplayLayoutObject ) => void;
};

const DisplayLayoutControl = ( props: DisplayLayoutControlProps ) => {
	const { type, columns } = props.displayLayout;
	const setDisplayLayout = (
		displayLayout: ProductCollectionDisplayLayout
	) => {
		props.setAttributes( { displayLayout } );
	};

	const displayLayoutControls = [
		{
			icon: list,
			title: __( 'List view', 'woo-gutenberg-products-block' ),
			onClick: () => setDisplayLayout( { type: 'list', columns } ),
			isActive: type === 'list',
		},
		{
			icon: grid,
			title: __( 'Grid view', 'woo-gutenberg-products-block' ),
			onClick: () => setDisplayLayout( { type: 'flex', columns } ),
			isActive: type === 'flex',
		},
	];

	return <ToolbarGroup controls={ displayLayoutControls } />;
};

export default DisplayLayoutControl;
