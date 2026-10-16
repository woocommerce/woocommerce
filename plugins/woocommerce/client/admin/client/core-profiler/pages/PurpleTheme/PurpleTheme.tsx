/**
 * External dependencies
 */
import '@wordpress/theme/design-tokens.css';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Badge } from '@wordpress/ui';
import clsx from 'clsx';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../../index';
import { PurpleThemeStepCompletedEvent } from '../../events';
import { Heading } from '../../components/heading/heading';
import { Navigation } from '../../components/navigation/navigation';
import { getPurplePrototypeSettings } from '~/customize-store/purple-prototype/settings';
import purplePreview from '~/customize-store/purple-prototype/assets/purple-preview.jpg';
import './style.scss';

type ThemeOptionProps = {
	image?: string;
	imageAlt: string;
	badge?: string;
	title: string;
	description: string;
	// Badge on its own line above the title, instead of beside it.
	badgeAbove?: boolean;
	children?: ReactNode;
};

const ThemeOption = ( {
	image,
	imageAlt,
	badge,
	title,
	description,
	badgeAbove = false,
	children,
}: ThemeOptionProps ) => (
	<div className="woocommerce-profiler-purple-theme__option">
		<div className="woocommerce-profiler-purple-theme__image">
			{ image && <img src={ image } alt={ imageAlt } /> }
		</div>
		<div className="woocommerce-profiler-purple-theme__body">
			<div
				className={ clsx(
					'woocommerce-profiler-purple-theme__title-row',
					{
						'is-stacked': badgeAbove,
					}
				) }
			>
				{ badge && <Badge intent="informational">{ badge }</Badge> }
				<h2 className="woocommerce-profiler-purple-theme__title">
					{ title }
				</h2>
			</div>
			<p className="woocommerce-profiler-purple-theme__description">
				{ description }
			</p>
			{ children && (
				<div className="woocommerce-profiler-purple-theme__actions">
					{ children }
				</div>
			) }
		</div>
	</div>
);

/**
 * Prototype step after the extensions step: start with Purple, or keep the current theme.
 * Each option has its own button, so nothing is chosen by default. The "Purple only" version
 * from the prototype menu shows a single Purple card with a link to keep the current theme.
 */
export const PurpleTheme = ( {
	sendEvent,
	navigationProgress,
}: {
	sendEvent: ( event: PurpleThemeStepCompletedEvent ) => void;
	navigationProgress: number;
	context: CoreProfilerStateMachineContext;
} ) => {
	const { activeTheme, wizard } = getPurplePrototypeSettings();
	const isPurpleOnly = wizard === 'purple-only';

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
				{ ...( isPurpleOnly
					? {
							skipText: __(
								'Keep my current theme',
								'woocommerce'
							),
						}
					: {} ) }
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
				<div
					className={ clsx(
						'woocommerce-profiler-purple-theme__options',
						{
							'is-purple-only': isPurpleOnly,
						}
					) }
				>
					<ThemeOption
						image={ purplePreview }
						imageAlt={ __(
							'Preview of a store using the Purple theme',
							'woocommerce'
						) }
						badge={ __( 'Free theme', 'woocommerce' ) }
						badgeAbove={ isPurpleOnly }
						title={ __(
							'Built by WooCommerce, for your store',
							'woocommerce'
						) }
						description={ __(
							'A free theme made for stores, so your shop looks polished from day one.',
							'woocommerce'
						) }
					>
						{ ! isPurpleOnly && (
							<>
								<Button
									className="woocommerce-profiler-purple-theme__button"
									variant="primary"
									__next40pxDefaultSize
									onClick={ () => complete( true ) }
								>
									{ __( 'Install Purple', 'woocommerce' ) }
								</Button>
								<p className="woocommerce-profiler-purple-theme__note">
									{ __(
										'We’ll install Purple and activate it as your store’s theme.',
										'woocommerce'
									) }
								</p>
							</>
						) }
					</ThemeOption>
					{ ! isPurpleOnly && (
						<ThemeOption
							image={ activeTheme.screenshot }
							imageAlt=""
							title={ __(
								'Keep your current theme',
								'woocommerce'
							) }
							description={ __(
								'Stay with the theme you have now. You can try Purple later from Customize your store.',
								'woocommerce'
							) }
						>
							<Button
								className="woocommerce-profiler-purple-theme__button"
								variant="secondary"
								__next40pxDefaultSize
								onClick={ () => complete( false ) }
							>
								{ __( 'Keep current theme', 'woocommerce' ) }
							</Button>
							<p className="woocommerce-profiler-purple-theme__note">
								{ __(
									'Your store keeps its current design.',
									'woocommerce'
								) }
							</p>
						</ThemeOption>
					) }
				</div>
				{ isPurpleOnly && (
					<div className="woocommerce-profiler-purple-theme__footer">
						<Button
							className="woocommerce-profiler-purple-theme__continue"
							variant="primary"
							onClick={ () => complete( true ) }
						>
							{ __( 'Install Purple', 'woocommerce' ) }
						</Button>
						<p className="woocommerce-profiler-purple-theme__note">
							{ __(
								'We’ll install Purple and activate it as your store’s theme.',
								'woocommerce'
							) }
						</p>
					</div>
				) }
			</div>
		</div>
	);
};
