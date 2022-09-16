/* tslint:disable */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InnerBlocks,
	BlockControls,
	InspectorControls,
} from '@wordpress/block-editor';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import { EditorProvider, CartProvider } from '@woocommerce/base-context';
import { previewCart } from '@woocommerce/resource-previews';
import { filledCart, removeCart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './inner-blocks';
import './editor.scss';
import {
	addClassToBody,
	useViewSwitcher,
	useBlockPropsWithLocking,
	useForcedLayout,
	BlockSettings,
} from '../cart-checkout-shared';
import '../cart-checkout-shared/sidebar-notices';
import { CartBlockContext } from './context';

// This is adds a class to body to signal if the selected block is locked
addClassToBody();

// Array of allowed block names.
const ALLOWED_BLOCKS = [
	'woocommerce/filled-cart-block',
	'woocommerce/empty-cart-block',
];

const views = [
	{
		view: 'woocommerce/filled-cart-block',
		label: __( 'Filled Cart', 'woo-gutenberg-products-block' ),
		icon: <Icon icon={ filledCart } />,
	},
	{
		view: 'woocommerce/empty-cart-block',
		label: __( 'Empty Cart', 'woo-gutenberg-products-block' ),
		icon: <Icon icon={ removeCart } />,
	},
];

export const Edit = ( { className, attributes, setAttributes, clientId } ) => {
	const { hasDarkControls } = attributes;
	const { currentView, component: ViewSwitcherComponent } = useViewSwitcher(
		clientId,
		views
	);
	const defaultTemplate = [
		[ 'woocommerce/filled-cart-block', {}, [] ],
		[ 'woocommerce/empty-cart-block', {}, [] ],
	];
	const blockProps = useBlockPropsWithLocking( {
		className: classnames( className, 'wp-block-woocommerce-cart', {
			'is-editor-preview': attributes.isPreview,
		} ),
	} );
	useForcedLayout( {
		clientId,
		registeredBlocks: ALLOWED_BLOCKS,
		defaultTemplate,
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<BlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<BlockErrorBoundary
				header={ __(
					'Cart Block Error',
					'woo-gutenberg-products-block'
				) }
				text={ __(
					'There was an error whilst rendering the cart block. If this problem continues, try re-creating the block.',
					'woo-gutenberg-products-block'
				) }
				showErrorMessage={ true }
				errorMessagePrefix={ __(
					'Error message:',
					'woo-gutenberg-products-block'
				) }
			>
				<EditorProvider
					currentView={ currentView }
					previewData={ { previewCart } }
				>
					<BlockControls>{ ViewSwitcherComponent }</BlockControls>
					<CartBlockContext.Provider
						value={ {
							hasDarkControls,
						} }
					>
						<CartProvider>
							<InnerBlocks
								allowedBlocks={ ALLOWED_BLOCKS }
								template={ defaultTemplate }
								templateLock={ false }
							/>
						</CartProvider>
					</CartBlockContext.Provider>
				</EditorProvider>
			</BlockErrorBoundary>
		</div>
	);
};

export const Save = () => {
	return (
		<div
			{ ...useBlockProps.save( {
				className: 'is-loading',
			} ) }
		>
			<InnerBlocks.Content />
		</div>
	);
};
