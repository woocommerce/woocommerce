/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, createInterpolateElement } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { Spinner } from '@wordpress/components';
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { usePatternsByCategory } from '../../hooks/use-patterns';
import { useEditBlocks } from '../../hooks/use-edit-blocks';

export const SidebarNavigationScreenHeader = () => {
	const { isLoading, patterns } = usePatternsByCategory( 'header' );
	const [ blocks, , onChange ] = useEditBlocks();

	const onClickHeaderPattern = useCallback(
		( _pattern, selectedBlocks ) => {
			const newHeaderBlock = {
				...selectedBlocks[ 0 ],
				attributes: {
					...selectedBlocks[ 0 ].attributes,
					slug: 'header',
				},
			};

			onChange(
				[
					...blocks.map( ( block ) => {
						if ( block.attributes?.slug === 'header' ) {
							return newHeaderBlock;
						}
						return block;
					} ),
				],
				{ selection: {} }
			);
		},
		[ blocks, onChange ]
	);

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
							href={ `${ ADMIN_URL }site-editor.php` }
							type="external"
						/>
					),
				}
			) }
			content={
				<>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-header">
						{ isLoading && (
							<span className="components-placeholder__preview">
								<Spinner />
							</span>
						) }

						{ ! isLoading && (
							<BlockPatternList
								shownPatterns={ patterns }
								blockPatterns={ patterns }
								onClickPattern={ onClickHeaderPattern }
								label={ 'Headers' }
								orientation="vertical"
								category={ 'header' }
								isDraggable={ false }
								showTitlesAsTooltip={ true }
							/>
						) }
					</div>
				</>
			}
		/>
	);

	// return null;
};
