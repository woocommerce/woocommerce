/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, Button } from '@wordpress/components';
import { BlockEditProps } from '@wordpress/blocks';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductCollectionAttributes } from '../types';

const UpgradeNotice = (
	props: BlockEditProps< ProductCollectionAttributes > & {
		revertMigration: () => void;
	}
) => {
	const { displayUpgradeNotice } = props.attributes;
	const notice = createInterpolateElement(
		__(
			'Products (Beta) block was upgraded to <strongText />, an updated version with new features and simplified settings.',
			'woo-gutenberg-products-block'
		),
		{
			strongText: (
				<strong>
					{ __(
						`Product Collection`,
						'woo-gutenberg-products-block'
					) }
				</strong>
			),
		}
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
		props.revertMigration();
	};

	return displayUpgradeNotice ? (
		<Notice onRemove={ handleRemove }>
			<>{ notice } </>
			<br />
			<br />
			<Button variant="link" onClick={ handleClick }>
				{ buttonLabel }
			</Button>
		</Notice>
	) : null;
};

export default UpgradeNotice;
