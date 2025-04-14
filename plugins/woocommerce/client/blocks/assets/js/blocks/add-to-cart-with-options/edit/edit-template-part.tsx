/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
import { useEntityBlockEditor, store as coreStore } from '@wordpress/core-data';
import {
	InnerBlocks,
	useInnerBlocksProps,
	useBlockProps,
} from '@wordpress/block-editor';

const TemplatePartInnerBlocks = ( {
	blockProps,
	templatePartId,
}: {
	blockProps: Record< string, unknown >;
	templatePartId: string | undefined;
} ) => {
	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'wp_template_part',
		{ id: templatePartId }
	);

	const { isLoading } = useSelect(
		( select ) => {
			const { hasFinishedResolution } = select( coreStore );

			const hasResolvedEntity = templatePartId
				? hasFinishedResolution( 'getEditedEntityRecord', [
						'postType',
						'wp_template_part',
						templatePartId,
				  ] )
				: false;

			return {
				isLoading: ! hasResolvedEntity,
			};
		},
		[ templatePartId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: blocks,
		onInput,
		onChange,
		renderAppender: () =>
			! isLoading && blocks.length === 0
				? InnerBlocks.ButtonBlockAppender
				: null,
	} );

	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return <div { ...innerBlocksProps } />;
};

export const AddToCartWithOptionsEditTemplatePart = ( {
	productType,
}: {
	productType: string;
} ) => {
	const { templatePartId } = useSelect(
		( select ) => {
			const {
				getCurrentTheme,
				getEditedEntityRecord,
				hasFinishedResolution,
			} = select( coreStore );

			const currentTheme = getCurrentTheme()?.stylesheet;

			if ( ! currentTheme ) {
				return {
					templatePartId: null,
				};
			}

			const templatePartSlug = `${ productType }-product-add-to-cart-with-options`;
			const themeTemplatePartId = `${ currentTheme }//${ templatePartSlug }`;
			const wooCommerceTemplatePartId = `woocommerce/woocommerce//${ templatePartSlug }`;

			const getThemeEntityArgs = [
				'postType',
				'wp_template_part',
				themeTemplatePartId,
			] as const;
			const themeEntityRecord = themeTemplatePartId
				? getEditedEntityRecord( ...getThemeEntityArgs )
				: null;
			const hasResolvedEntity = themeTemplatePartId
				? hasFinishedResolution(
						'getEditedEntityRecord',
						getThemeEntityArgs
				  )
				: false;

			const themeTemplatePartIsMissing =
				hasResolvedEntity &&
				( ! themeEntityRecord ||
					Object.keys( themeEntityRecord ).length === 0 );

			return {
				templatePartId: themeTemplatePartIsMissing
					? wooCommerceTemplatePartId
					: themeTemplatePartId,
			};
		},
		[ productType ]
	);

	const blockProps = useBlockProps();

	if ( ! templatePartId ) {
		return (
			<div { ...blockProps }>
				<Spinner />
			</div>
		);
	}

	return (
		<TemplatePartInnerBlocks
			blockProps={ blockProps }
			templatePartId={ templatePartId }
		/>
	);
};
