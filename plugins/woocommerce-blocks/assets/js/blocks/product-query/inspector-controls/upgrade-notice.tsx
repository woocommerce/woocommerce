/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, Button } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

export const UpgradeNotice = ( props: { upgradeBlock: () => void } ) => {
	const notice = createInterpolateElement(
		__(
			'Upgrade all Products (Beta) blocks on this page to <strongText /> for more features!',
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
		'Upgrade to Product Collection',
		'woo-gutenberg-products-block'
	);

	const handleClick = () => {
		props.upgradeBlock();
	};

	return (
		<Notice isDismissible={ false }>
			<>{ notice }</>
			<br />
			<br />
			<Button variant="link" onClick={ handleClick }>
				{ buttonLabel }
			</Button>
		</Notice>
	);
};
