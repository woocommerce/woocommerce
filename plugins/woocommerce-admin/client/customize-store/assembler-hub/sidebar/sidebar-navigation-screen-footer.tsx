/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useCallback } from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { Spinner } from '@wordpress/components';
// @ts-expect-error Missing type in core-data.
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { useEditorBlocks } from '../hooks/use-editor-blocks';
import { usePatternsByCategory } from '../hooks/use-pattern';

export const SidebarNavigationScreenFooter = () => {
	const { isLoading, patterns } = usePatternsByCategory( 'footer' );
	const [ blocks, onChange ] = useEditorBlocks();

	const onClickHeaderPattern = useCallback(
		( _pattern, selectedBlocks ) => {
			const newHeaderBlock = {
				...selectedBlocks[ 0 ],
				attributes: {
					...selectedBlocks[ 0 ].attributes,
					slug: 'footer',
				},
			};

			onChange(
				[
					...blocks.map( ( block ) => {
						if ( block.attributes?.slug === 'footer' ) {
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
			title={ __( 'Change your footer', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					"Select a new footer from the options below. Your footer includes your site's secondary navigation and will be added to every page. You can continue customizing this via the <EditorLink>Editor</EditorLink>.",
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
					<div className="edit-site-sidebar-navigation-screen-patterns__group-footer">
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
								label={ 'Footers' }
								orientation="vertical"
								category={ 'footers' }
								isDraggable={ false }
								showTitlesAsTooltip={ true }
							/>
						) }
					</div>
				</>
			}
		/>
	);
};
