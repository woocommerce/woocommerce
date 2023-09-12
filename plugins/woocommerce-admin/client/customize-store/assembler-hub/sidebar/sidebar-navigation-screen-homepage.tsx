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

export const SidebarNavigationScreenHomepage = () => {
	const { isLoading, patterns } = usePatternsByCategory( 'wireframe' );
	const [ blocks, onChange ] = useEditorBlocks();

	console.log( 'blocks', blocks );
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
						if ( block.attributes?.slug === 'homepage' ) {
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
			title={ __( 'Change your homepage', 'woocommerce' ) }
			description={ createInterpolateElement(
				__(
					'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the <EditorLink>Editor</EditorLink>.',
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
					<div className="edit-site-sidebar-navigation-screen-patterns__group-homepage">
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
								label={ 'Hompeage' }
								orientation="vertical"
								category={ 'homepage' }
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
