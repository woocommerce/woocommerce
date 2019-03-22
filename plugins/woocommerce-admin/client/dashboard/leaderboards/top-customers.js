/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import Leaderboard from 'analytics/components/leaderboard';

export class TopCustomers extends Component {
    constructor( props ) {
        super( props );

        this.getRowsContent = this.getRowsContent.bind( this );
        this.getHeadersContent = this.getHeadersContent.bind( this );
    }

    getHeadersContent() {
        return [
            {
                label: __( 'Customer Name', 'woocommerce-admin' ),
                key: 'name',
                required: true,
                isLeftAligned: true,
                isSortable: false,
            },
            {
                label: __( 'Orders', 'woocommerce-admin' ),
                key: 'orders_count',
                required: true,
                defaultSort: true,
                isSortable: false,
                isNumeric: true,
            },
            {
                label: __( 'Total Spend', 'woocommerce-admin' ),
                key: 'total_spend',
                isSortable: false,
                isNumeric: true,
            },
        ];
    }

    getRowsContent( data ) {
        const { query } = this.props;
        const persistedQuery = getPersistedQuery( query );
        return map( data, row => {
            const { id, total_spend, name, orders_count } = row;

            const customerUrl = getNewPath( persistedQuery, 'analytics/customers', {
                filter: 'single_customer',
                customers: id,
            } );
            const customerLink = (
                <Link href={ customerUrl } type="wc-admin">
                    { name }
                </Link>
            );

            return [
                {
                    display: customerLink,
                    value: name,
                },
                {
                    display: numberFormat( orders_count ),
                    value: orders_count,
                },
                {
                    display: formatCurrency( total_spend ),
                    value: getCurrencyFormatDecimal( total_spend ),
                },
            ];
        } );
    }

    render() {
        const { query, totalRows } = this.props;
        const tableQuery = {
            orderby: 'total_spend',
            order: 'desc',
            per_page: totalRows,
            extended_info: false,
        };

        return (
            <Leaderboard
                endpoint="customers"
                getHeadersContent={ this.getHeadersContent }
                getRowsContent={ this.getRowsContent }
                query={ query }
                tableQuery={ tableQuery }
                title={ __( 'Top Customers', 'woocommerce-admin' ) }
             />
        );
    }
}

export default TopCustomers;
