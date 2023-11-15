/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TimerImage from './timer.svg';
import { WC_ASSET_URL } from '../../../../utils/admin-settings';

const ProductsHeader = ( { task, goToTask } ) => {
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Products illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/sales-section-illustration.png'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>{ __( 'Add products to sell', 'woocommerce' ) }</h1>
				<p>
					{ __(
						'Build your catalog by adding what you want to sell. You can add products manually or import them from a different store.',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ __( 'Add products', 'woocommerce' ) }
				</Button>
				<p className="woocommerce-task-header__timer">
					<img src={ TimerImage } alt="Timer" />{ ' ' }
					<span>{ __( '2 minutes', 'woocommerce' ) }</span>
				</p>
			</div>
		</div>
	);
};

export default ProductsHeader;
