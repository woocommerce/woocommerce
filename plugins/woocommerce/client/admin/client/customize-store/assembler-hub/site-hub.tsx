// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/site-hub/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import clsx from 'clsx';
import { useSelect } from '@wordpress/data';
import {
	__unstableMotion as motion,
	__unstableAnimatePresence as AnimatePresence,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { useReducedMotion } from '@wordpress/compose';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import { forwardRef } from '@wordpress/element';
// @ts-expect-error No types for this exist yet.
import SiteIcon from '@wordpress/edit-site/build-module/components/site-icon';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import WordPressLogo from '~/lib/wordpress-logo';

/**
 * Internal dependencies
 */
import { isEntrepreneurFlow } from '~/customize-store/design-with-ai/entrepreneur-flow';
import './gutenberg-styles/site-hub.scss';

const HUB_ANIMATION_DURATION = 0.3;

export const SiteHub = forwardRef<
	HTMLDivElement,
	{
		isTransparent: boolean;
		className: string;
		variants?: Record< string, Record< string, string | number > >;
	}
>( ( { isTransparent, ...restProps }, ref ) => {
	const { siteTitle } = useSelect( ( select ) => {
		// @ts-expect-error No types for this exist yet.
		const { getSite } = select( coreStore );

		return {
			siteTitle: getSite()?.title,
		};
	}, [] );

	const disableMotion = useReducedMotion();

	return (
		<motion.div
			ref={ ref }
			{ ...restProps }
			className={ clsx(
				'woocommerce-edit-site-site-hub',
				restProps.className
			) }
			initial={ false }
			transition={ {
				type: 'tween',
				duration: disableMotion ? 0 : HUB_ANIMATION_DURATION,
				ease: 'easeOut',
			} }
		>
			<HStack
				justify="space-between"
				alignment="center"
				className="woocommerce-edit-site-site-hub__container"
			>
				<HStack
					justify="flex-start"
					className="woocommerce-edit-site-site-hub__text-content"
					spacing="0"
				>
					<div
						className={ clsx(
							'woocommerce-edit-site-site-hub__view-mode-toggle-container',
							{
								'has-transparent-background': isTransparent,
							}
						) }
					>
						<Link
							href={ getNewPath( getPersistedQuery(), '/', {} ) }
							type="wp-admin"
						>
							{ isEntrepreneurFlow() ? (
								<WordPressLogo
									size={ 24 }
									className="woocommerce-cys-wordpress-header-logo"
								/>
							) : (
								<SiteIcon className="woocommerce-edit-site-layout__view-mode-toggle-icon" />
							) }
						</Link>
					</div>

					{ ! isEntrepreneurFlow() && (
						<AnimatePresence>
							<motion.div
								layout={ false }
								animate={ {
									opacity: 1,
								} }
								exit={ {
									opacity: 0,
								} }
								className={ clsx(
									'woocommerce-edit-site-site-hub__site-title',
									{ 'is-transparent': isTransparent }
								) }
								transition={ {
									type: 'tween',
									duration: disableMotion ? 0 : 0.2,
									ease: 'easeOut',
									delay: 0.1,
								} }
							>
								{ decodeEntities( siteTitle ) }
							</motion.div>
						</AnimatePresence>
					) }
				</HStack>
			</HStack>
		</motion.div>
	);
} );
