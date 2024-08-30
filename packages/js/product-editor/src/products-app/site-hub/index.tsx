/**
 * External dependencies
 */
import { createElement, memo, forwardRef } from '@wordpress/element';
import classNames from 'classnames';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import { filterURLForDisplay } from '@wordpress/url';
import {
	Button,
	// @ts-expect-error missing types.
	__experimentalHStack as HStack,
	VisuallyHidden,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import SiteIcon from './site-icon';
import { unlock } from '../../lock-unlock';

const SiteHub = memo(
	forwardRef(
		(
			{ isTransparent }: { isTransparent: boolean },
			ref: React.Ref< HTMLAnchorElement >
		) => {
			const { dashboardLink, homeUrl, siteTitle } = useSelect(
				( select ) => {
					const { getSettings } = unlock(
						select( 'core/edit-site' )
					);

					const {
						getSite,
						getUnstableBase, // Site index.
					} = select( coreStore );
					const _site: undefined | { title: string; url: string } =
						getSite();

					const base: { home: string } | undefined =
						getUnstableBase();
					return {
						dashboardLink:
							getSettings().__experimentalDashboardLink ||
							'index.php',
						homeUrl: base?.home,
						siteTitle:
							! _site?.title && !! _site?.url
								? filterURLForDisplay( _site?.url )
								: _site?.title,
					};
				},
				[]
			);

			return (
				<div className="edit-site-site-hub">
					<HStack justify="flex-start" spacing="0">
						<div
							className={ classNames(
								'edit-site-site-hub__view-mode-toggle-container',
								{
									'has-transparent-background': isTransparent,
								}
							) }
						>
							<Button
								ref={ ref }
								href={ dashboardLink }
								label={ __(
									'Go to the Dashboard',
									'woocommerce'
								) }
								className="edit-site-layout__view-mode-toggle"
								style={ {
									transform: 'scale(0.5)',
									borderRadius: 4,
								} }
							>
								<SiteIcon className="edit-site-layout__view-mode-toggle-icon" />
							</Button>
						</div>

						<HStack>
							<div className="edit-site-site-hub__title">
								<Button
									variant="link"
									href={ homeUrl }
									target="_blank"
								>
									{ siteTitle && decodeEntities( siteTitle ) }
									<VisuallyHidden as="span">
										{
											/* translators: accessibility text */
											__(
												'(opens in a new tab)',
												'woocommerce'
											)
										}
									</VisuallyHidden>
								</Button>
							</div>
						</HStack>
					</HStack>
				</div>
			);
		}
	)
);

export default SiteHub;
