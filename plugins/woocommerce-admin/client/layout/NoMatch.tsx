/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

const NoMatch: React.FC = () => {
	return (
		<div className="woocommerce-layout__no-match">
			<Card>
				<CardBody>
					<Text>
						{ __(
							'Sorry, you are not allowed to access this page.',
							'woocommerce'
						) }
					</Text>
				</CardBody>
			</Card>
		</div>
	);
};

export { NoMatch };
