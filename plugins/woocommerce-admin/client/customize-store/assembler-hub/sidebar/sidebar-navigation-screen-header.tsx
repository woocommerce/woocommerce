/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { usePatternsByCategory } from '../../hooks/use-patterns';

export const SidebarNavigationScreenHeader = () => {
	const patterns = usePatternsByCategory( 'header' );

	return (
		<SidebarNavigationScreen
			title={ __( 'Change your header', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					"Select a new header from the options below. Your header includes your site's navigation and will be added to every page. You can continue customizing this via the <EditorLink>Editor</EditorLink>.",
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							href={ `${ ADMIN_URL }/site-editor.php` }
							type="external"
						/>
					),
				}
			) }
			content={
				<>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-header">
						<BlockPatternList
							shownPatterns={ patterns }
							blockPatterns={ patterns }
							onClickPattern={ () => {
								console.log( 'clicked!' );
							} }
							label={ 'Headers' }
							orientation="vertical"
							category={ 'header' }
							isDraggable={ false }
							showTitlesAsTooltip={ true }
						/>
					</div>
				</>
			}
		/>
	);
};
