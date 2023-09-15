/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import {
	Button,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SiteHub } from '../assembler-hub/site-hub';
import { MShotsImage } from './mshots-image';
import { ADMIN_URL } from '~/utils/admin-settings';
import './style.scss';
export * as services from './services';

export type events = { type: 'GO_BACK_TO_HOME' };
export const PREVIEW_IMAGE_OPTION = {
	vpw: 1200,
	vph: 742,
	w: 588,
	h: 363.58,
	requeue: true,
};

export const Transitional = ( {
	sendEvent,
}: {
	sendEvent: ( event: events ) => void;
} ) => {
	const homeUrl: string = getSetting( 'homeUrl', '' );

	return (
		<div className="woocommerce-customize-store__transitional">
			<SiteHub
				as={ motion.div }
				variants={ {
					view: { x: 0 },
				} }
				isTransparent={ false }
				className="edit-site-layout__hub"
			/>

			<div className="woocommerce-customize-store__transitional-content">
				<h1 className="woocommerce-customize-store__transitional-heading">
					{ __( 'Your store looks great!', 'woocommerce' ) }
				</h1>
				<h2 className="woocommerce-customize-store__transitional-subheading">
					{ __(
						"Your store is a reflection of your unique style and personality, and we're thrilled to see it come to life.",
						'woocommerce'
					) }
				</h2>
				<Button
					className="woocommerce-customize-store__transitional-preview-button"
					variant="primary"
					href={ homeUrl }
					target="_blank"
				>
					{ __( 'Preview store', 'woocommerce' ) }
				</Button>

				<div className="woocommerce-customize-store__transitional-site-img-container">
					<MShotsImage
						url={ homeUrl }
						alt={ __( 'Your store screenshot', 'woocommerce' ) }
						aria-labelledby={ __(
							'Your store screenshot',
							'woocommerce'
						) }
						options={ PREVIEW_IMAGE_OPTION }
					/>
				</div>
				<div className="woocommerce-customize-store__transitional-actions">
					<div className="woocommerce-customize-store__transitional-action">
						<h3>
							{ __( 'Fine-tune your design', 'woocommerce' ) }
						</h3>
						<p>
							{ __(
								'Head to the Editor to change your images and text, add more pages, and make any further customizations.',
								'woocommerce'
							) }
						</p>
						<Button
							variant="tertiary"
							href={ `${ ADMIN_URL }site-editor.php` }
						>
							{ __( 'Go to the Editor', 'woocommerce' ) }
						</Button>
					</div>

					<div className="woocommerce-customize-store__transitional-action">
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
							variant="tertiary"
							onClick={ () =>
								sendEvent( {
									type: 'GO_BACK_TO_HOME',
								} )
							}
						>
							{ __( 'Back to Home', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</div>
		</div>
	);
};
