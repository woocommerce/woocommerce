/**
 * External dependencies
 */
import { List } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './cards.scss';

type Card = {
	key: string;
	title: string;
	content: string | JSX.Element;
	before: JSX.Element;
};

type CardListProps = {
	items: Card[];
};

const CardList: React.FC< CardListProps > = ( { items } ) => {
	return (
		<div className="woocommerce-products-card-list">
			<List items={ items } />
		</div>
	);
};

export default CardList;
