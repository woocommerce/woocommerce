/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import AddProducts from './illustrations/add-products.js';

const ProductsHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<AddProducts
				class="svg-background"
				style={ {
					right: '6%',
					bottom: '-27px',
				} }
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Add products to start selling' ) }</h1>
				<p>
					{ __(
						'Add your first products and see them shine on your store! You can add your products manually or import them.'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add products', 'woocommerce-admin' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ task.time }</span>
				</p>
			</div>
		</div>
	);
};

export default ProductsHeader;
