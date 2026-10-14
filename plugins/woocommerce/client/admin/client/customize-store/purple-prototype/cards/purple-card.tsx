/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Badge, Button, Link, Text } from '@wordpress/ui';
import { Icon, check, error } from '@wordpress/icons';
import clsx from 'clsx';
import type { ComponentProps } from 'react';

/**
 * Internal dependencies
 */
import { ThemeCard, LinkButton } from './theme-card';
import type { PurpleState } from '../settings';
import { PURPLE_DEMO_URL, getLivePreviewUrl, getSiteEditorUrl } from '../urls';
import purplePreview from '../assets/purple-preview.jpg';

type PurpleCardProps = {
	state: PurpleState;
	slug: string;
	homeUrl: string;
	onInstall: () => void;
	onActivate: () => void;
	onRetry: () => void;
};

type BadgeIntent = ComponentProps< typeof Badge >[ 'intent' ];

/**
 * Badge and helper text for each state. The title, description and benefits stay the same,
 * so the card keeps its height when the state changes.
 */
const getStatusCopy = ( state: PurpleState ) => {
	const freeTheme = {
		badge: __( 'Free theme', 'woocommerce' ),
		badgeIntent: 'informational' as BadgeIntent,
	};
	const installed = {
		badge: __( 'Installed, not active yet', 'woocommerce' ),
		badgeIntent: 'stable' as BadgeIntent,
	};

	switch ( state ) {
		case 'installing':
			return {
				...freeTheme,
				helper: __(
					'Installing Purple. This takes a few seconds.',
					'woocommerce'
				),
			};
		case 'installed':
			return {
				...installed,
				helper: __(
					"Activating changes your store's design: layout, menus and homepage. Your products, orders and page content stay.",
					'woocommerce'
				),
			};
		case 'activating':
			return {
				...installed,
				helper: __(
					'Activating Purple. This takes a few seconds.',
					'woocommerce'
				),
			};
		case 'active':
			return {
				badge: __( 'Active theme', 'woocommerce' ),
				badgeIntent: 'stable' as BadgeIntent,
				helper: '',
			};
		case 'error':
			return {
				...freeTheme,
				helper: __(
					"Something went wrong. Purple couldn't be installed or activated. Your store hasn't changed. Try again, or use Appearance > Themes.",
					'woocommerce'
				),
			};
		default:
			return {
				...freeTheme,
				helper: __(
					'Nothing changes on your store until you activate Purple.',
					'woocommerce'
				),
			};
	}
};

const Benefits = () => (
	<ul className="purple-cys-benefits">
		{ [
			__(
				'Every store page designed, from shop to checkout',
				'woocommerce'
			),
			__( 'Ready-made color palettes and font styles', 'woocommerce' ),
			__( 'Kept up to date by the WooCommerce team', 'woocommerce' ),
		].map( ( benefit ) => (
			<li key={ benefit }>
				<Icon icon={ check } size={ 20 } />
				<Text>{ benefit }</Text>
			</li>
		) ) }
	</ul>
);

const Actions = ( {
	state,
	slug,
	homeUrl,
	onInstall,
	onActivate,
	onRetry,
}: PurpleCardProps ) => {
	const previewDemo = ( disabled = false ) => (
		<LinkButton
			variant="outline"
			href={ PURPLE_DEMO_URL }
			isExternal
			disabled={ disabled }
		>
			{ __( 'Preview demo', 'woocommerce' ) }
		</LinkButton>
	);
	const livePreview = ( disabled = false ) => (
		<LinkButton
			variant="outline"
			href={ getLivePreviewUrl( slug ) }
			disabled={ disabled }
		>
			{ __( 'Live Preview', 'woocommerce' ) }
		</LinkButton>
	);

	switch ( state ) {
		case 'installing':
			return (
				<>
					<Button loading disabled>
						{ __( 'Installing…', 'woocommerce' ) }
					</Button>
					{ previewDemo( true ) }
				</>
			);
		case 'installed':
			return (
				<>
					<Button onClick={ onActivate }>
						{ __( 'Activate', 'woocommerce' ) }
					</Button>
					{ livePreview() }
				</>
			);
		case 'activating':
			return (
				<>
					<Button loading disabled>
						{ __( 'Activating…', 'woocommerce' ) }
					</Button>
					{ livePreview( true ) }
				</>
			);
		case 'active':
			return (
				<>
					<LinkButton href={ getSiteEditorUrl() }>
						{ __( 'Customize in Site Editor', 'woocommerce' ) }
					</LinkButton>
					<Link href={ homeUrl } openInNewTab>
						{ __( 'View store', 'woocommerce' ) }
					</Link>
				</>
			);
		case 'error':
			return (
				<>
					<Button onClick={ onRetry }>
						{ __( 'Try again', 'woocommerce' ) }
					</Button>
					{ previewDemo() }
				</>
			);
		default:
			return (
				<>
					<Button onClick={ onInstall }>
						{ __( 'Install Purple', 'woocommerce' ) }
					</Button>
					{ previewDemo() }
				</>
			);
	}
};

export const PurpleCard = ( props: PurpleCardProps ) => {
	const { state } = props;
	const copy = getStatusCopy( state );

	return (
		<ThemeCard
			className={ clsx( 'purple-cys-theme-card--purple', {
				'is-active': state === 'active',
			} ) }
			media={
				<img
					src={ purplePreview }
					alt={ __(
						'Preview of a store using the Purple theme',
						'woocommerce'
					) }
				/>
			}
		>
			<Badge intent={ copy.badgeIntent }>{ copy.badge }</Badge>
			<Text variant="heading-xl" render={ <h2 /> }>
				{ __( 'Built by WooCommerce, for your store', 'woocommerce' ) }
			</Text>
			<Text
				className="purple-cys-theme-card__description"
				render={ <p /> }
			>
				{ __(
					'The Purple theme gives your shop, product, cart and checkout pages a polished design from day one, so you can focus on your products.',
					'woocommerce'
				) }
			</Text>
			<Benefits />
			<div className="purple-cys-theme-card__actions">
				<Actions { ...props } />
			</div>
			{ copy.helper && (
				<Text
					variant="body-sm"
					className={ clsx( 'purple-cys-theme-card__helper', {
						'is-error': state === 'error',
					} ) }
					render={ <p /> }
				>
					{ state === 'error' && <Icon icon={ error } size={ 16 } /> }
					{ copy.helper }
				</Text>
			) }
		</ThemeCard>
	);
};
