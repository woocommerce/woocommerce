/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { isImportProduct } from '~/task-lists/fills/utils';
import { WC_ASSET_URL } from '../../../../utils/admin-settings';

const ProductsHeader = ( { task, goToTask } ) => {
	const isImportProductHeader = isImportProduct();
	return (
		<div className="woocommerce-task-header__contents-container">
			<img
				alt={ __( 'Products illustration', 'woocommerce' ) }
				src={
					WC_ASSET_URL +
					'images/task_list/sales-section-illustration.svg'
				}
				className="svg-background"
			/>
			<div className="woocommerce-task-header__contents">
				<h1>
					{ isImportProductHeader
						? __( 'Import your products', 'woocommerce' )
						: __( 'List your products', 'woocommerce' ) }
				</h1>
				<p>
					{ __(
						'Start selling by adding products or services to your store. Choose to list products manually, or import them from a different store. ',
						'woocommerce'
					) }
				</p>
				<Button
					isSecondary={ task.isComplete }
					isPrimary={ ! task.isComplete }
					onClick={ goToTask }
				>
					{ isImportProductHeader
						? __( 'Import products', 'woocommerce' )
						: __( 'Add products', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default ProductsHeader;
