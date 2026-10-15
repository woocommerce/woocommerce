/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Badge, Button, Link, Text } from '@wordpress/ui';
import { Icon, check, error } from '@wordpress/icons';
import clsx from 'clsx';
import type { ComponentProps, MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { ThemeCard, LinkButton } from './theme-card';
import { completeCustomizeStoreTask } from '../task-completion';
import type { PurpleState } from '../settings';
import {
	getLivePreviewUrl,
	getPreviewDemoUrl,
	rememberPreviewReturn,
	getSiteEditorUrl,
} from '../urls';
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
		badge: __( 'Installed, not active', 'woocommerce' ),
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
					"Activating changes your store's design. Your products, orders and pages stay as they are.",
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
					"Purple couldn't be set up. Your store hasn't changed. Try again, or go to Appearance > Themes.",
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
			__( 'Every page designed, from shop to checkout', 'woocommerce' ),
			__( 'Ready-made colors and fonts', 'woocommerce' ),
			__( 'Updated by the WooCommerce team', 'woocommerce' ),
		].map( ( benefit ) => (
			<li key={ benefit }>
				<Icon icon={ check } size={ 20 } />
				<Text>{ benefit }</Text>
			</li>
		) ) }
	</ul>
);

/**
 * Opens WordPress's Live Preview of Purple. A theme has to be installed to preview it, and the
 * prototype's forced states only fake the install, so make sure Purple is installed first. It
 * isn't activated, so the store doesn't change.
 */
const LivePreviewButton = ( {
	slug,
	disabled,
}: {
	slug: string;
	disabled: boolean;
} ) => {
	const [ isPreparing, setIsPreparing ] = useState( false );
	const href = getLivePreviewUrl( slug );

	if ( isPreparing ) {
		return (
			<Button variant="outline" loading disabled>
				{ __( 'Preview on your store', 'woocommerce' ) }
			</Button>
		);
	}

	return (
		<LinkButton
			variant="outline"
			href={ href }
			disabled={ disabled }
			completesTask={ false }
			onClick={ async ( event: MouseEvent< HTMLElement > ) => {
				event.preventDefault();
				setIsPreparing( true );
				await Promise.all( [
					completeCustomizeStoreTask(),
					apiFetch( {
						path: '/wc-admin/onboarding/themes/install',
						method: 'POST',
						data: { theme: slug },
					} ).catch( () => undefined ),
				] );
				window.location.href = href;
			} }
		>
			{ __( 'Preview on your store', 'woocommerce' ) }
		</LinkButton>
	);
};

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
			href={ getPreviewDemoUrl( slug ) }
			completesTask={ false }
			onClick={ rememberPreviewReturn }
			disabled={ disabled }
		>
			{ __( 'Preview demo', 'woocommerce' ) }
		</LinkButton>
	);
	const livePreview = ( disabled = false ) => (
		<LivePreviewButton slug={ slug } disabled={ disabled } />
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
						{ __( 'Activate Purple', 'woocommerce' ) }
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
					<Link
						href={ homeUrl }
						openInNewTab
						onClick={ () => void completeCustomizeStoreTask() }
					>
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
			className="purple-cys-theme-card--purple"
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
					'A free theme made for stores, so your shop looks polished from day one.',
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
