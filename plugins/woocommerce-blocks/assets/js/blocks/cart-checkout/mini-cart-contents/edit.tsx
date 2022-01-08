/**
 * External dependencies
 */
import type { ReactElement } from 'react';
import {
	useBlockProps,
	InnerBlocks,
	BlockControls,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Icon, filledCart, removeCart } from '@woocommerce/icons';
import { EditorProvider } from '@woocommerce/base-context';
import type { TemplateArray } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { useViewSwitcher, useForcedLayout } from '../shared';
import './editor.scss';

// Array of allowed block names.
const ALLOWED_BLOCKS = [
	'woocommerce/filled-mini-cart-contents-block',
	'woocommerce/empty-mini-cart-contents-block',
];

const views = [
	{
		view: 'woocommerce/filled-mini-cart-contents-block',
		label: __( 'Filled Mini Cart', 'woo-gutenberg-products-block' ),
		icon: <Icon srcElement={ filledCart } />,
	},
	{
		view: 'woocommerce/empty-mini-cart-contents-block',
		label: __( 'Empty Mini Cart', 'woo-gutenberg-products-block' ),
		icon: <Icon srcElement={ removeCart } />,
	},
];

interface Props {
	clientId: string;
}

const Edit = ( { clientId }: Props ): ReactElement => {
	const blockProps = useBlockProps();

	const defaultTemplate = [
		[ 'woocommerce/filled-mini-cart-contents-block', {}, [] ],
		[ 'woocommerce/empty-mini-cart-contents-block', {}, [] ],
	] as TemplateArray;

	const { currentView, component: ViewSwitcherComponent } = useViewSwitcher(
		clientId,
		views
	);

	useForcedLayout( {
		clientId,
		registeredBlocks: ALLOWED_BLOCKS,
		defaultTemplate,
	} );

	return (
		<div { ...blockProps }>
			<EditorProvider currentView={ currentView }>
				<BlockControls>{ ViewSwitcherComponent }</BlockControls>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ defaultTemplate }
					templateLock={ false }
				/>
			</EditorProvider>
		</div>
	);
};

export default Edit;

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
