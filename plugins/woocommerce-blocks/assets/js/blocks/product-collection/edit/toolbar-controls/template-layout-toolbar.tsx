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
	TemplateLayoutControlProps,
	LayoutOptions,
	ProductCollectionLayout,
} from '../../types';

const TemplateLayoutToolbar = ( props: TemplateLayoutControlProps ) => {
	const { type: layoutType } = props.templateLayout;
	const setTemplateLayout = ( newLayoutType: LayoutOptions ) => {
		props.setAttributes( {
			templateLayout: {
				...props.templateLayout,
				type: newLayoutType,
			} as ProductCollectionLayout,
		} );
	};

	const displayLayoutControls = [
		{
			icon: list,
			title: __( 'List view', 'woocommerce' ),
			onClick: () => setTemplateLayout( LayoutOptions.STACK ),
			isActive: layoutType === LayoutOptions.STACK,
		},
		{
			icon: grid,
			title: __( 'Grid view', 'woocommerce' ),
			onClick: () => setTemplateLayout( LayoutOptions.GRID ),
			isActive: layoutType === LayoutOptions.GRID,
		},
	];

	return <ToolbarGroup controls={ displayLayoutControls } />;
};

export default TemplateLayoutToolbar;
