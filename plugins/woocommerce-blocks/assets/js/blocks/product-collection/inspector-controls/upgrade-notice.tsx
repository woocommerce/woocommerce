/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, Button } from '@wordpress/components';
import { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes } from '../types';

const FormattedNotice = ( { notice }: { notice: string } ) => {
	const strongText = 'Product Collection';
	const [ before, after ] = notice.split( strongText );

	return (
		<>
			{ before }
			<strong>{ strongText }</strong>
			{ after }
		</>
	);
};

const UpgradeNotice = (
	props: BlockEditProps< ProductCollectionAttributes > & {
		revertMigration?: () => void;
	}
) => {
	const { displayUpgradeNotice } = props.attributes;
	const notice = __(
		'Products (Beta) block was upgraded to Product Collection, an updated version with new features and simplified settings.',
		'woo-gutenberg-products-block'
	);

	const buttonLabel = __(
		'Revert to Products (Beta)',
		'woo-gutenberg-products-block'
	);

	const handleRemove = () => {
		// @todo: this logic needs to be extended to be hidden for all
		// Product Collection blocks and whole store
		props.setAttributes( {
			displayUpgradeNotice: false,
		} );
	};

	const handleClick = () => {
		// @todo: Provide logic to revert blocks migration
		// props.revertMigration();
	};

	return displayUpgradeNotice ? (
		<Notice onRemove={ handleRemove }>
			<FormattedNotice notice={ notice } />
			<br />
			<br />
			<Button variant="link" onClick={ handleClick }>
				{ buttonLabel }
			</Button>
		</Notice>
	) : null;
};

export default UpgradeNotice;
