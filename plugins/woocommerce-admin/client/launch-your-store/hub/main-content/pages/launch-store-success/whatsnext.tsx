/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { Button } from '@wordpress/components';
// import { createInterpolateElement, useState } from '@wordpress/element';
// import { Link } from '@woocommerce/components';
// import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { ADMIN_URL } from '~/utils/admin-settings';

export const WhatsNext = ( { goToHome }: { goToHome: () => void } ) => {
	return (
		<div className="woocommerce-launch-store__congrats-main-actions">
			<div className="woocommerce-launch-store__congrats-action">
				<div className="woocommerce-launch-store__congrats-action__content">
					<h3>{ __( 'Add your products', 'woocommerce' ) }</h3>
					<p>
						{ __(
							'Start stocking your virtual shelves by adding or importing your products, or edit the sample products.',
							'woocommerce'
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => {
							recordEvent(
								'launch_you_store_congrats_product_list_click'
							);
							location.href = `${ ADMIN_URL }edit.php?post_type=product`;
						} }
					>
						{ __( 'Go to Products', 'woocommerce' ) }
					</Button>
				</div>
			</div>

			<div className="woocommerce-launch-store__congrats-action">
				<div className="woocommerce-launch-store__congrats-action__content">
					<h3>{ __( 'Fine-tune your design', 'woocommerce' ) }</h3>
					<p>
						{ __(
							'Head to the Editor to change your images and text, add more pages, and make any further customizations.',
							'woocommerce'
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => {
							recordEvent(
								'launch_you_store_congrats_editor_click'
							);
							location.href = `${ ADMIN_URL }site-editor.php`;
						} }
					>
						{ __( 'Go to the Editor', 'woocommerce' ) }
					</Button>
				</div>
			</div>

			<div className="woocommerce-launch-store__congrats-action">
				<div className="woocommerce-launch-store__congrats-action__content">
					<h3>
						{ __(
							'Continue setting up your store',
							'woocommerce'
						) }
					</h3>
					<p>
						{ __(
							'Go back to the Home screen to complete your store setup and start selling',
							'woocommerce'
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => {
							recordEvent(
								'launch_you_store_congrats_home_click'
							);
							goToHome();
						} }
					>
						{ __( 'Back to Home', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};
