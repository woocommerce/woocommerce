/**
 * External dependencies
 */
import '@wordpress/theme/design-tokens.css';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Badge } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../../index';
import { PurpleThemeStepCompletedEvent } from '../../events';
import { Heading } from '../../components/heading/heading';
import { Navigation } from '../../components/navigation/navigation';
import purplePreview from '~/customize-store/purple-prototype/assets/purple-preview.jpg';
import './style.scss';

/**
 * Prototype step after the extensions step: start with Purple, or keep the current theme.
 * One Purple card, with the choice made by the two buttons below it.
 */
export const PurpleTheme = ( {
	sendEvent,
	navigationProgress,
}: {
	sendEvent: ( event: PurpleThemeStepCompletedEvent ) => void;
	navigationProgress: number;
	context: CoreProfilerStateMachineContext;
} ) => {
	const complete = ( usePurple: boolean ) =>
		sendEvent( {
			type: 'PURPLE_THEME_STEP_COMPLETED',
			payload: { usePurple },
		} );

	return (
		<div
			className="woocommerce-profiler-purple-theme"
			data-testid="core-profiler-purple-theme"
		>
			<Navigation
				percentage={ navigationProgress }
				onSkip={ () => complete( false ) }
			/>
			<div className="woocommerce-profiler-page__content woocommerce-profiler-purple-theme__content">
				<Heading
					className="woocommerce-profiler__stepper-heading"
					title={ __( 'Choose how your store looks', 'woocommerce' ) }
					subTitle={ __(
						'Start with Purple, our free theme for stores, or keep your current one. You can change it any time.',
						'woocommerce'
					) }
				/>
				<div className="woocommerce-profiler-purple-theme__option">
					<div className="woocommerce-profiler-purple-theme__image">
						<img
							src={ purplePreview }
							alt={ __(
								'Preview of a store using the Purple theme',
								'woocommerce'
							) }
						/>
					</div>
					<div className="woocommerce-profiler-purple-theme__body">
						<Badge intent="informational">
							{ __( 'Free theme', 'woocommerce' ) }
						</Badge>
						<h2 className="woocommerce-profiler-purple-theme__title">
							{ __(
								'Built by WooCommerce, for your store',
								'woocommerce'
							) }
						</h2>
						<p className="woocommerce-profiler-purple-theme__description">
							{ __(
								'A free theme made for stores, so your shop looks polished from day one.',
								'woocommerce'
							) }
						</p>
					</div>
				</div>
				<div className="woocommerce-profiler-purple-theme__footer">
					<div className="woocommerce-profiler-purple-theme__buttons">
						<Button
							className="woocommerce-profiler-purple-theme__continue"
							variant="primary"
							onClick={ () => complete( true ) }
						>
							{ __( 'Install Purple', 'woocommerce' ) }
						</Button>
						<Button
							className="woocommerce-profiler-purple-theme__continue"
							variant="secondary"
							onClick={ () => complete( false ) }
						>
							{ __( 'Keep my current theme', 'woocommerce' ) }
						</Button>
					</div>
					<p className="woocommerce-profiler-purple-theme__note">
						{ __(
							'We’ll install Purple and activate it, so it becomes your store’s theme right away. You can switch back any time in Appearance > Themes.',
							'woocommerce'
						) }
					</p>
				</div>
			</div>
		</div>
	);
};
