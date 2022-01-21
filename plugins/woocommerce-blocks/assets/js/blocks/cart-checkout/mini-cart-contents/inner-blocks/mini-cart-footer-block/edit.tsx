/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Block from './block';
import { blockName as miniCartContentsBlockName } from '../../attributes';
import { useColorProps } from '../../../../../hooks/style-attributes';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();

	const parentAttributes = useSelect( ( select ) => {
		const { getBlockAttributes, getBlockParentsByBlockName } = select(
			'core/block-editor'
		);
		const parentBlockIds = getBlockParentsByBlockName(
			clientId,
			miniCartContentsBlockName
		);

		if ( parentBlockIds.length !== 1 ) {
			return {};
		}

		return getBlockAttributes( parentBlockIds[ 0 ] );
	} );

	const parentColorProps = useColorProps( parentAttributes );

	return (
		<div { ...blockProps }>
			<Noninteractive>
				<Block { ...parentColorProps.style } />
			</Noninteractive>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() }></div>;
};
