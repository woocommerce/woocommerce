/**
 * External dependencies
 */
import '@wordpress/theme/design-tokens.css';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Badge } from '@wordpress/ui';
import clsx from 'clsx';

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

type ThemeChoice = 'purple' | 'current';

type ThemeOptionProps = {
	value: ThemeChoice;
	selected: boolean;
	image?: string;
	imageAlt: string;
	title: string;
	badge?: string;
	description: string;
	onSelect: ( value: ThemeChoice ) => void;
};

const ThemeOption = ( {
	value,
	selected,
	image,
	imageAlt,
	title,
	badge,
	description,
	onSelect,
}: ThemeOptionProps ) => (
	<label
		htmlFor={ `woocommerce-profiler-purple-theme-${ value }` }
		className={ clsx( 'woocommerce-profiler-purple-theme__option', {
			'is-selected': selected,
		} ) }
	>
		<span className="woocommerce-profiler-purple-theme__image">
			{ image && <img src={ image } alt={ imageAlt } /> }
		</span>
		<span className="woocommerce-profiler-purple-theme__body">
			<span className="woocommerce-profiler-purple-theme__title">
				<input
					id={ `woocommerce-profiler-purple-theme-${ value }` }
					className="woocommerce-profiler-purple-theme__radio"
					type="radio"
					name="woocommerce-profiler-purple-theme"
					value={ value }
					checked={ selected }
					onChange={ () => onSelect( value ) }
				/>
				{ title }
				{ badge && <Badge intent="informational">{ badge }</Badge> }
			</span>
			<span className="woocommerce-profiler-purple-theme__description">
				{ description }
			</span>
		</span>
	</label>
);

/**
 * Prototype step after the extensions step: start with Purple, or keep the current theme.
 */
export const PurpleTheme = ( {
	sendEvent,
	navigationProgress,
}: {
	sendEvent: ( event: PurpleThemeStepCompletedEvent ) => void;
	navigationProgress: number;
	context: CoreProfilerStateMachineContext;
} ) => {
	const { activeTheme } = getPurplePrototypeSettings();
	const [ choice, setChoice ] = useState< ThemeChoice >( 'purple' );

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
						'Start with Purple, our free theme designed for stores, or keep the theme you have. You can change it any time.',
						'woocommerce'
					) }
				/>
				<div
					className="woocommerce-profiler-purple-theme__options"
					role="radiogroup"
					aria-label={ __( 'Store theme', 'woocommerce' ) }
				>
					<ThemeOption
						value="purple"
						selected={ choice === 'purple' }
						image={ purplePreview }
						imageAlt={ __(
							'Preview of a store using the Purple theme',
							'woocommerce'
						) }
						title={ __( 'Purple', 'woocommerce' ) }
						badge={ __( 'Recommended', 'woocommerce' ) }
						description={ __(
							'Built by WooCommerce for stores. Every page is designed, from shop to checkout, with ready-made colors and fonts.',
							'woocommerce'
						) }
						onSelect={ setChoice }
					/>
					<ThemeOption
						value="current"
						selected={ choice === 'current' }
						image={ activeTheme.screenshot }
						imageAlt=""
						title={ sprintf(
							/* translators: %s: name of the active theme, for example "Twenty Twenty-Four". */
							__( 'Keep %s', 'woocommerce' ),
							activeTheme.name
						) }
						description={ __(
							'Stay with the theme you have now. You can try Purple later from Customize your store.',
							'woocommerce'
						) }
						onSelect={ setChoice }
					/>
				</div>
				<div className="woocommerce-profiler-purple-theme__footer">
					<Button
						className="woocommerce-profiler-purple-theme__continue"
						variant="primary"
						onClick={ () => complete( choice === 'purple' ) }
					>
						{ __( 'Continue', 'woocommerce' ) }
					</Button>
					<p className="woocommerce-profiler-purple-theme__note">
						{ choice === 'purple'
							? __(
									'We’ll install Purple and set it as your store theme while we set up your features.',
									'woocommerce'
								)
							: __(
									'Your store keeps its current design.',
									'woocommerce'
								) }
					</p>
				</div>
			</div>
		</div>
	);
};
