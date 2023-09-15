/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
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
import { useHomeTemplates } from '../hooks/use-home-templates';

export const SidebarNavigationScreenHomepage = () => {
	const { isLoading, homeTemplates } = useHomeTemplates();

	const [ blocks, onChange ] = useEditorBlocks();
	const onClickPattern = useCallback(
		( templateName: string ) => {
			const newMainBlocks = homeTemplates[ templateName ].map(
				( pattern ) => pattern.blocks[ 0 ]
			);
			onChange(
				[ blocks[ 0 ], ...newMainBlocks, blocks[ blocks.length - 1 ] ],
				{ selection: {} }
			);
		},
		[ blocks, onChange, homeTemplates ]
	);

	console.log( homeTemplates );

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

						{ ! isLoading &&
							Object.entries( homeTemplates ).map(
								( [ templateName, patterns ], index ) => {
									if ( patterns.length === 0 ) {
										return null;
									}
									return (
										<BlockPatternList
											key={ index }
											shownPatterns={ [ patterns[ 0 ] ] }
											blockPatterns={ [ patterns[ 0 ] ] }
											onClickPattern={ () => {
												onClickPattern( templateName );
											} }
											label={ 'Hompeage' }
											orientation="vertical"
											category={ 'homepage' }
											isDraggable={ false }
											showTitlesAsTooltip={ false }
										/>
									);
								}
							) }
					</div>
				</>
			}
		/>
	);
};
