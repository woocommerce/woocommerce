/**
 * External dependencies
 */
import { BlockControls } from '@wordpress/block-editor';
import { Toolbar } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import TextToolbarButton from '@woocommerce/block-components/text-toolbar-button';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';
import EmptyCart from './empty-cart';

/**
 * Component to handle edit mode of "Cart Block".
 */
const CartEditor = ( { className } ) => {
	const [ isFullCartMode, setFullCartMode ] = useState( true );

	const toggleFullCartMode = () => {
		setFullCartMode( ! isFullCartMode );
	};

	const getBlockControls = () => {
		return (
			<BlockControls className="wc-block-cart-toolbar">
				<Toolbar>
					<TextToolbarButton
						onClick={ toggleFullCartMode }
						isToggled={ isFullCartMode }
					>
						{ __( 'Full Cart', 'woo-gutenberg-products-block' ) }
					</TextToolbarButton>
					<TextToolbarButton
						onClick={ toggleFullCartMode }
						isToggled={ ! isFullCartMode }
					>
						{ __( 'Empty Cart', 'woo-gutenberg-products-block' ) }
					</TextToolbarButton>
				</Toolbar>
			</BlockControls>
		);
	};

	return (
		<div className={ className }>
			{ getBlockControls() }
			{ isFullCartMode && <FullCart /> }
			<EmptyCart hidden={ isFullCartMode } />
		</div>
	);
};

CartEditor.propTypes = {
	className: PropTypes.string,
};

export default CartEditor;
