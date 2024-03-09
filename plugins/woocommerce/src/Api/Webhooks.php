<?php

namespace Automattic\WooCommerce\Api;

use Automattic\WooCommerce\Api\Enums\WebhookStatus;
use Automattic\WooCommerce\Api\InputTypes\CreateWebhookInput;
use Automattic\WooCommerce\Api\ObjectTypes\AnInt;
use Automattic\WooCommerce\Api\ObjectTypes\User;
use Automattic\WooCommerce\Api\ObjectTypes\Webhook;

#[Description( 'Webhooks API class.' )]
class Webhooks {
	#[WebMutation( 'CreateWebhook' )]
	#[Description( 'Create a new webhook.' )]
	#[ArgDescription( 'input', 'Data for the new webhook to be created.' )]
	public function create_webhook( CreateWebhookInput $input, ?array $_fields_info = null ): Webhook {
		$core_webhook = new \WC_Webhook();

		$core_webhook->set_api_version( $input->api_version );
		$core_webhook->set_date_created( gmdate( 'Y-m-d H:i:s' ) );

		$core_webhook->set_delivery_url( $input->delivery_url );
		$core_webhook->set_name( $input->name );
		$core_webhook->set_failure_count( 0 );
		$core_webhook->set_pending_delivery( false );
		$core_webhook->set_secret( $input->secret );
		$core_webhook->set_status( $input->status );
		$core_webhook->set_topic( $input->topic );
		$core_webhook->set_user_id( $input->user_id );

		$core_webhook->save();

		return $this->get_webhook( $core_webhook->get_id(), $_fields_info );
	}

	#[WebQuery( 'Webhooks' )]
	#[ArrayType( Webhook::class )]
	#[Description( 'Get all the existing webhooks, optionally only those with the given status.' )]
	public function get_webhooks(
		#[EnumType( WebhookStatus::class )]
		?string $status = '',
		?array $_fields_info = null ): array {

		$data_store  = \WC_Data_Store::load( 'webhook' );
		$webhook_ids = $data_store->get_webhooks_ids( $status );
		return array_map( fn( $id) => $this->get_webhook( $id, $_fields_info ), $webhook_ids );
	}

	#[WebQuery( 'Webhook' )]
	#[Description( 'Get one single webhook by id.' )]
	public function get_webhook(
		#[Description( 'Id of the webhook to get.' )]
		int $id,
		?array $_fields_info = null ): ?Webhook {

		$core_webhook = wc_get_webhook( $id );
		if ( is_null( $core_webhook ) ) {
			return null;
		}

		$w                   = new Webhook();
		$w->api_version      = $core_webhook->get_api_version();
		$w->date_created     = $core_webhook->get_date_created();
		$w->id               = $id;
		$w->delivery_url     = $core_webhook->get_delivery_url();
		$w->name             = $core_webhook->get_name();
		$w->failure_count    = $core_webhook->get_failure_count();
		$w->pending_delivery = $core_webhook->get_pending_delivery();
		$w->secret           = $core_webhook->get_secret();
		$w->status           = $core_webhook->get_status();
		$w->topic            = $core_webhook->get_topic();
		if ( is_null( $_fields_info ) || isset( $_fields_info['user'] ) ) {
			$w->user        = new User();
			$w->user->id    = $core_webhook->get_user_id();
			$core_user      = get_user_by( 'id', $w->user->id );
			$w->user->email = $core_user->user_email;
			$w->user->login = $core_user->user_login;
		}

		return $w;
	}
}
