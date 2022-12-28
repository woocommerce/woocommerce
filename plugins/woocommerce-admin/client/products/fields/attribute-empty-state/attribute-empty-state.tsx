/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './attribute-empty-state.scss';
import AttributeEmptyStateLogo from './attribute-empty-state-logo.svg';

type AttributeEmptyStateProps = {
	image?: string;
	subtitle?: string;
	addNewLabel?: string;
	onNewClick?: () => void;
};

export const AttributeEmptyState: React.FC< AttributeEmptyStateProps > = ( {
	image = AttributeEmptyStateLogo,
	subtitle = __( 'No attributes yet', 'woocommerce' ),
	addNewLabel = __( 'Add first attribute', 'woocommerce' ),
	onNewClick,
} ) => {
	return (
		<Card>
			<CardBody>
				<div className="woocommerce-attribute-empty-state">
					<img
						src={ image }
						alt="Completed"
						className="woocommerce-attribute-empty-state__image"
					/>
					<Text
						variant="subtitle.small"
						weight="600"
						size="14"
						lineHeight="20px"
						className="woocommerce-attribute-empty-state__subtitle"
					>
						{ subtitle }
					</Text>
					{ typeof onNewClick === 'function' && (
						<Button
							variant="secondary"
							className="woocommerce-attribute-empty-state__add-new"
							onClick={ onNewClick }
						>
							{ addNewLabel }
						</Button>
					) }
				</div>
			</CardBody>
		</Card>
	);
};
