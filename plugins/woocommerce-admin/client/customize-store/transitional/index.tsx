/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import {
	Button,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';
// @ts-ignore No types for this exist yet.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';

/**
 * Internal dependencies
 */
import { SiteHub } from '../assembler-hub/site-hub';
import { ADMIN_URL } from '~/utils/admin-settings';

import './style.scss';

export type events = { type: 'GO_BACK_TO_HOME' };

export const Transitional = ( {
	editor,
	sendEvent,
}: {
	editor: React.ReactNode;
	sendEvent: ( event: events ) => void;
} ) => {
	const homeUrl: string = getSetting( 'homeUrl', '' );
	const isEditorLoading = useIsSiteEditorLoading();

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
					onClick={ () => {
						recordEvent(
							'customize_your_store_transitional_preview_store_click'
						);
						window.open( homeUrl, '_blank' );
					} }
				>
					{ __( 'Preview store', 'woocommerce' ) }
				</Button>

				<div
					className={ classNames(
						'woocommerce-customize-store__transitional-site-preview-container',
						{
							'is-loading': isEditorLoading,
						}
					) }
				>
					{ editor }
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
							onClick={ () => {
								recordEvent(
									'customize_your_store_transitional_editor_click'
								);
								window.location.href = `${ ADMIN_URL }site-editor.php`;
							} }
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
							onClick={ () => {
								recordEvent(
									'customize_your_store_transitional_home_click'
								);
								sendEvent( {
									type: 'GO_BACK_TO_HOME',
								} );
							} }
						>
							{ __( 'Back to Home', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</div>
		</div>
	);
};
