/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { withSpokenMessages } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TaxonomyFilterInspectorControls } from './inspector';
import { termOptionsPreview } from './constants';
import { EditProps } from './types';
import { getAllowedBlocks } from '../../utils/get-allowed-blocks';
import { EXCLUDED_BLOCKS } from '../../constants';
import type { FilterOptionItem } from '../../types';
import { InitialDisabled } from '../../components/initial-disabled';
import { Notice } from '../../components/notice';
import { getTaxonomyLabel } from './utils';

const Edit = ( props: EditProps ) => {
	const { attributes: blockAttributes } = props;

	const { taxonomy, isPreview, displayStyle, showCounts, sortOrder } =
		blockAttributes;

	// For now, use fake data - will be replaced with real taxonomy data later
	const [ termOptions, setTermOptions ] = useState< FilterOptionItem[] >(
		[]
	);
	const [ isOptionsLoading, setIsOptionsLoading ] =
		useState< boolean >( true );

	useEffect( () => {
		setTermOptions( [
			...termOptionsPreview.sort( ( a, b ) => {
				switch ( sortOrder ) {
					case 'name-asc':
						return a.value > b.value ? 1 : -1;
					case 'name-desc':
						return a.value < b.value ? 1 : -1;
					case 'count-asc':
						return a.count > b.count ? 1 : -1;
					default:
						return a.count < b.count ? 1 : -1;
				}
			} ),
		] );
		setIsOptionsLoading( false );
	}, [ sortOrder ] );

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
			template: [
				[
					'core/heading',
					{
						level: 3,
						content: getTaxonomyLabel( taxonomy ),
						style: {
							spacing: {
								margin: {
									bottom: '0.625rem',
									top: '0',
								},
							},
						},
					},
				],
				[ displayStyle ],
			],
		}
	);

	if ( ! taxonomy )
		return (
			<div { ...innerBlocksProps }>
				<TaxonomyFilterInspectorControls { ...props } />
				<Notice>
					<p>
						{ __(
							'Please select a taxonomy to use this filter!',
							'woocommerce'
						) }
					</p>
				</Notice>
			</div>
		);

	return (
		<div { ...innerBlocksProps }>
			<TaxonomyFilterInspectorControls { ...props } />
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						filterData: {
							items:
								termOptions.length === 0 && isPreview
									? termOptionsPreview
									: termOptions,
							isLoading: isOptionsLoading,
							showCounts,
						},
					} }
				>
					{ children }
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default withSpokenMessages( Edit );
