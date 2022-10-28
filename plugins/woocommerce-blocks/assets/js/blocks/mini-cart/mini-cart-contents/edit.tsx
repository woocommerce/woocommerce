/* eslint-disable jsdoc/check-alignment */
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
import { filledCart, removeCart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { EditorProvider } from '@woocommerce/base-context';
import type { TemplateArray } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useViewSwitcher, useForcedLayout } from '../../cart-checkout-shared';
import { MiniCartInnerBlocksStyle } from './inner-blocks-style';
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
		icon: <Icon icon={ filledCart } />,
	},
	{
		view: 'woocommerce/empty-mini-cart-contents-block',
		label: __( 'Empty Mini Cart', 'woo-gutenberg-products-block' ),
		icon: <Icon icon={ removeCart } />,
	},
];

interface Props {
	clientId: string;
}

const Edit = ( { clientId }: Props ): ReactElement => {
	const blockProps = useBlockProps( {
		/**
		 * This is a workaround for the Site Editor to calculate the
		 * correct height of the Mini Cart template part on the first load.
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5825
		 */
		style: {
			minHeight: '100vh',
		},
	} );

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

	/**
	 * This is a workaround for the Site Editor to set the correct
	 * background color of the Mini Cart Contents block base on
	 * the main background color set by the theme.
	 */
	useEffect( () => {
		const canvasEl = document.querySelector(
			'.edit-site-visual-editor__editor-canvas'
		);
		if ( ! ( canvasEl instanceof HTMLIFrameElement ) ) {
			return;
		}
		const canvas =
			canvasEl.contentDocument || canvasEl.contentWindow?.document;
		if ( ! canvas ) {
			return;
		}
		if ( canvas.getElementById( 'mini-cart-contents-background-color' ) ) {
			return;
		}
		const styles = canvas.querySelectorAll( 'style' );
		const [ cssRule ] = Array.from( styles )
			.map( ( style ) => Array.from( style.sheet?.cssRules || [] ) )
			.flatMap( ( style ) => style )
			.filter( Boolean )
			.filter(
				( rule ) =>
					rule.selectorText === '.editor-styles-wrapper' &&
					rule.style.backgroundColor
			);
		if ( ! cssRule ) {
			return;
		}
		const backgroundColor = cssRule.style.backgroundColor;
		if ( ! backgroundColor ) {
			return;
		}
		const style = document.createElement( 'style' );
		style.id = 'mini-cart-contents-background-color';
		style.appendChild(
			document.createTextNode(
				`:where(.wp-block-woocommerce-mini-cart-contents) {
				background-color: ${ backgroundColor };
			}`
			)
		);
		const body = canvas.querySelector( '.editor-styles-wrapper' );
		if ( ! body ) {
			return;
		}
		body.appendChild( style );
	}, [] );

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
			<MiniCartInnerBlocksStyle style={ blockProps.style } />
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
