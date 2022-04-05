import { Card, CardHeader, CardFooter, Button } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { EllipsisMenu, List, Pill } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';


export const PaymentsRecommendationsBanner = () => {
	return (
		<Card size="medium" className="woocommerce-recommended-payments-card">
			<CardHeader>
				<div className="woocommerce-recommended-payments-card__header">
					<Text
						variant="title.small"
						as="p"
						size="20"
						lineHeight="28px"
					>
						{ __( 'Additional ways to get paid', 'woocommerce' ) }
					</Text>
					<Text
						className={
							'woocommerce-recommended-payments__header-heading'
						}
						variant="caption"
						as="p"
						size="12"
						lineHeight="16px"
					>
						{ __(
							'We recommend adding one of the following payment extensions to your store. The extension will be installed and activated for you when you click "Get started".',
							'woocommerce'
						) }
					</Text>
				</div>
				<div className="woocommerce-card__menu woocommerce-card__header-item">
					<EllipsisMenu
						label={ __( 'Task List Options', 'woocommerce' ) }
						renderContent={ () => (
							<div className="woocommerce-review-activity-card__section-controls">
								<Button>
									{ __( 'Hide this', 'woocommerce' ) }
								</Button>
							</div>
						) }
					/>
				</div>
			</CardHeader>
			<CardFooter>
                {/* TODO fix font colour and add icons */}
					{ __( 'Accepted payment methods include:', 'woocommerce' ) }
			</CardFooter>
		</Card>
	);
};
