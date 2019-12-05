/**
 * External dependencies
 */
import { BlockControls } from '@wordpress/block-editor';
import { Toolbar } from '@wordpress/components';
import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import TextToolbarButton from '@woocommerce/block-components/text-toolbar-button';

/**
 * Internal dependencies
 */
import FullCart from './components/full-cart';
import EmptyCart from './components/empty-cart';

/**
 * Component to handle edit mode of "Cart Block".
 */
const Cart = () => {
	const [ isFullCartMode, setFullCartMode ] = useState( true );

	const toggleFullCartMode = () => {
		setFullCartMode( ! isFullCartMode );
	};

	const getBlockControls = () => {
		return (
			<BlockControls className='wc-block-cart-toolbar'>
				<Toolbar>
					<TextToolbarButton
						onClick={ toggleFullCartMode }
						isToggled={ isFullCartMode }>
						{  __(
							'Full Cart',
							'woo-gutenberg-products-block'
						) }
					</TextToolbarButton>
					<TextToolbarButton
						onClick={ toggleFullCartMode }
						isToggled={ ! isFullCartMode }>
						{  __(
							'Empty Cart',
							'woo-gutenberg-products-block'
						) }
					</TextToolbarButton>
				</Toolbar>
			</BlockControls>
		);
	};

	const cart = isFullCartMode ? <FullCart /> : <EmptyCart />;

	return (
		<Fragment>
			{ getBlockControls() }
			{ cart }
		</Fragment>
	);
};

Cart.propTypes = {};

export default Cart;
