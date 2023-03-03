<?php

/**
 * Class WC_Customer_Download_Data_Store_Test.
 */
class WC_Customer_Download_Data_Store_Test extends WC_Unit_Test_Case {

	/**
	 * Array of Download objects used for testing.
	 *
	 * @var Array(WC_Customer_Download)
	 */
	private $downloads;

	/**
	 * Array of test customer.
	 *
	 * @var Array(Array)
	 */
	private $customers;

	/**
	 * Array of download logs.
	 *
	 * @var Array(WC_Customer_Download_Log)
	 */
	private $download_logs;

	/**
	 * Array of downloads per order.
	 *
	 * @var Array(WC_Customer_Download)
	 */
	private $download_for_order;

	/**
	 * Array of downloads per customer.
	 *
	 * @var Array(WC_Customer_Download)
	 */
	private $download_for_customer;

	/**
	 * Array of downloads per download_id.
	 *
	 * @var Array(WC_Customer_Download)
	 */
	private $download_for_download_id;


	/**
	 * Tests set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Customer 1.
		$this->customers[] = array(
			'id'     => 1,
			'email'  => 'test_1@example.com',
			'orders' => array(
				1,
				2,
			),
		);

		// Customer 2.
		$this->customers[] = array(
			'id'     => 2,
			'email'  => 'test_2@example.com',
			'orders' => array(
				3,
				4,
			),
		);

		// Create a download permission for each customer.
		foreach ( $this->customers as $customer ) {
			$this->download_for_customer[ $customer['id'] ] = array();

			foreach ( $customer['orders'] as $order_id ) {
				$download_id = "{$customer['id']}-{$order_id}_1";
				$this->create_download( $customer, $order_id, $download_id );

				// Every other order has 2 downloads.
				if ( $order_id % 2 ) {
					$download_id = "{$customer['id']}-{$order_id}_2";
					$this->create_download( $customer, $order_id, $download_id );
				}
			}
		}
	}

	/**
	 * Create download to store some data in the db.
	 *
	 * @param array  $customer Associative array with an id, email and an array of orders for a customer.
	 * @param int    $order_id Order id.
	 * @param string $download_id Download id.
	 *
	 * @return void
	 */
	private function create_download( array $customer, int $order_id, string $download_id ) {
		$download = new WC_Customer_Download();
		$download->set_user_id( $customer['id'] );
		$download->set_user_email( $customer['email'] );
		$download->set_order_id( $order_id );
		$download->set_download_id( $download_id );
		$download->set_access_granted( '2018-01-22 00:00:00' );
		$d_id                                    = $download->save();
		$this->downloads[ $d_id ]                = $download;
		$this->download_for_order[ $order_id ][] = $download;
		$this->download_for_customer[ $customer['id'] ][] = $download;
		if ( isset( $this->download_for_download_id[ $download_id ] ) && is_array( $this->download_for_download_id[ $download_id ] ) ) {
			$this->download_for_download_id[ $download_id ][] = $download;
		} else {
			$this->download_for_download_id[ $download_id ] = array( $download );
		}
	}

	/**
	 * Test clean up.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up Customer Downloads.
		foreach ( $this->downloads as $download ) {
			$download->delete();
		}

		// Clear temp instance variables.
		$this->customers                = array();
		$this->downloads                = array();
		$this->download_logs            = array();
		$this->download_for_order       = array();
		$this->download_for_customer    = array();
		$this->download_for_download_id = array();
	}

	/**
	 * Create $max_logs download log entries for each download permission record from $this->downloads[].
	 *
	 * @param int $max_logs Number of logs per download permission.
	 *
	 * @return void
	 */
	private function create_download_logs( int $max_logs ) {
		foreach ( $this->downloads as $download ) {
			for ( $i = 0; $i < $max_logs; $i ++ ) {
				$download_log = new WC_Customer_Download_Log();
				$download_log->set_permission_id( $download->get_id() );
				$download_log->set_user_id( $download->get_user_id() );
				$download_log->set_user_ip_address( '1.2.3.4' );
				$dl_id                         = $download_log->save();
				$this->download_logs[ $dl_id ] = $download_log;
			}
		}
	}

	/**
	 * @testdox Deletes the download permission record and related download logs.
	 */
	public function test_delete() {
		$logs_per_permission = 2;
		$this->create_download_logs( $logs_per_permission );
		$data_store = WC_Data_Store::load( 'customer-download' );

		$download_ids = array_keys( $this->downloads );
		$downloads    = array_values( $this->downloads );

		$data_store->delete( $downloads[0] );
		// Id is set to 0 after delete.
		$this->assertEquals( 0, $downloads[0]->get_id() );

		// Try to (re)load the deleted download again, it shouldn't exist anymore.
		$this->expectException( \Exception::class );
		$cd = $data_store->read( $downloads[0] );

		// Test that respective download logs are also gone.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_ids[0] ) );

		// But the other download permissions and logs are still intact.
		$rest_of_download_ids = array_slice( $download_ids, 1, null, true );
		foreach ( $rest_of_download_ids as $download_id ) {
			$reread_download = $data_store->read( $this->downloads[ $download_id ] );
			$this->assertEquals( $this->downloads[ $download_id ]->get_id(), $reread_download->get_id() );
			$this->assertEquals( $logs_per_permission, $log_data_store->get_download_logs_for_permission( $download_id ) );

		}

		// Clean up.
		$count_download_ids = count( $download_ids );
		for ( $i = 1; $i < $count_download_ids; $i ++ ) {
			$data_store->delete( $downloads[ $i ] );
			$this->assertEquals( 0, $downloads[ $i ]->get_id() );
			$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_ids[ $i ] ) );
		}
	}

	/**
	 * @testdox Deletes the download permission record and related download logs referred to by permission id.
	 */
	public function test_delete_by_id() {
		$logs_per_permission = 2;
		$this->create_download_logs( $logs_per_permission );
		$data_store = WC_Data_Store::load( 'customer-download' );

		$download_ids = array_keys( $this->downloads );
		$downloads    = array_values( $this->downloads );

		$data_store->delete_by_id( $download_ids[0] );

		/**
		 * It is rather odd that only delete() sets the download's id to 0, other delete_* methods don't.
		 * Instead, try to (re)load the deleted download again, it shouldn't exist anymore.
		 */
		$this->expectException( \Exception::class );
		$not_used = $data_store->read( $downloads[0] );

		// Test that respective download logs are also gone.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_ids[0] ) );

		// But the other download permissions and logs are still intact.
		$rest_of_download_ids = array_slice( $download_ids, 1, null, true );
		foreach ( $rest_of_download_ids as $download_id ) {
			$reread_download = $data_store->read( $this->downloads[ $download_id ] );
			$this->assertEquals( $this->downloads[ $download_id ]->get_id(), $reread_download->get_id() );
			$this->assertEquals( $logs_per_permission, $log_data_store->get_download_logs_for_permission( $download_id ) );
		}

		// Clean up.
		$count_download_ids = count( $download_ids );
		for ( $i = 1; $i < $count_download_ids; $i ++ ) {
			$data_store->delete_by_id( $download_ids[ $i ] );
			$this->assertEquals( 0, $downloads[ $i ]->get_id() );
			$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_ids[ $i ] ) );
		}
	}

	/**
	 * @testdox Deletes the download permission record and related download logs referred to by order id.
	 */
	public function test_delete_by_order_id() {
		$logs_per_permission = 2;
		$this->create_download_logs( $logs_per_permission );
		$data_store = WC_Data_Store::load( 'customer-download' );

		$downloads = array_values( $this->downloads );

		$first_order_id = $downloads[0]->get_order_id();
		$data_store->delete_by_order_id( $first_order_id );
		/**
		 * It is rather odd that only delete() sets the download's id to 0, other delete_* methods don't.
		 * Instead, try to (re)load the deleted download again, it shouldn't exist anymore.
		 */
		$this->assertEmpty(
			$data_store->get_downloads(
				array(
					'order_id' => $first_order_id,
				)
			)
		);

		// Test that respective download logs are also gone.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		foreach ( $this->download_for_order[ $first_order_id ] as $download_permission ) {
			$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
		}

		// Test that the other downloads and download logs are intact.
		foreach ( $this->download_for_order as $order_id => $download_permissions ) {
			if ( $first_order_id === $order_id ) {
				// Already checked and deleted.
				continue;
			}

			$dp_for_order = $data_store->get_downloads(
				array(
					'order_id' => $order_id,
				)
			);
			$this->assertEquals( count( $download_permissions ), count( $dp_for_order ) );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEquals( $logs_per_permission, count( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) ) );
			}
		}

		// Clean up, order by order.
		foreach ( $this->download_for_order as $order_id => $download_permissions ) {
			if ( $first_order_id === $order_id ) {
				// Already checked and deleted.
				continue;
			}

			$data_store->delete_by_order_id( $order_id );
			$dp_for_order = $data_store->get_downloads(
				array(
					'order_id' => $order_id,
				)
			);
			$this->assertEmpty( $dp_for_order );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
			}
		}
	}

	/**
	 * @testdox Deletes the download permission record and related download logs referred to by download id.
	 */
	public function test_delete_by_download_id() {
		$logs_per_permission = 2;
		$this->create_download_logs( $logs_per_permission );
		$data_store = WC_Data_Store::load( 'customer-download' );

		$downloads = array_values( $this->downloads );

		$first_download_id = $downloads[0]->get_download_id();
		$data_store->delete_by_download_id( $first_download_id );
		/**
		 * It is rather odd that only delete() sets the download's id to 0, other delete_* methods don't.
		 * Instead, try to (re)load the deleted download again, it shouldn't exist anymore.
		 */
		$this->assertEmpty(
			$data_store->get_downloads(
				array(
					'download_id' => $first_download_id,
				)
			)
		);

		// Test that respective download logs are also gone.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		foreach ( $this->download_for_download_id[ $first_download_id ] as $download_permission ) {
			$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
		}

		// Test that the other downloads and download logs are intact.
		foreach ( $this->download_for_download_id as $download_id => $download_permissions ) {
			if ( $first_download_id === $download_id ) {
				// Already checked and deleted.
				continue;
			}

			$dp_for_download_id = $data_store->get_downloads(
				array(
					'download_id' => $download_id,
				)
			);
			$this->assertEquals( count( $download_permissions ), count( $dp_for_download_id ) );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEquals( $logs_per_permission, count( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) ) );
			}
		}

		// Clean up.
		foreach ( $this->download_for_download_id as $download_id => $download_permissions ) {
			if ( $first_download_id === $download_id ) {
				// Already checked and deleted.
				continue;
			}

			$data_store->delete_by_download_id( $download_id );
			$dp_for_download_id = $data_store->get_downloads(
				array(
					'download_id' => $download_id,
				)
			);
			$this->assertEmpty( $dp_for_download_id );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
			}
		}
	}

	/**
	 * @testdox Deletes the download permission record and related download logs referred to by user id.
	 */
	public function test_delete_by_user_id() {
		$logs_per_permission = 2;
		$this->create_download_logs( $logs_per_permission );
		$data_store = WC_Data_Store::load( 'customer-download' );

		$downloads = array_values( $this->downloads );

		$first_user_id = $downloads[0]->get_user_id();
		$data_store->delete_by_user_id( $first_user_id );
		/**
		 * It is rather odd that only delete() sets the download's id to 0, other delete_* methods don't.
		 * Instead, try to (re)load the deleted download again, it shouldn't exist anymore.
		 */
		$this->assertEmpty(
			$data_store->get_downloads(
				array(
					'user_id' => $first_user_id,
				)
			)
		);

		// Test that respective download logs are also gone.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		foreach ( $this->download_for_customer[ $first_user_id ] as $download_permission ) {
			$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
		}

		// Test that the other downloads and download logs are intact.
		foreach ( $this->download_for_customer as $user_id => $download_permissions ) {
			if ( $first_user_id === $user_id ) {
				// Already checked and deleted.
				continue;
			}

			$dp_for_user = $data_store->get_downloads(
				array(
					'user_id' => $user_id,
				)
			);
			$this->assertEquals( count( $download_permissions ), count( $dp_for_user ) );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEquals( $logs_per_permission, count( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) ) );
			}
		}

		// Clean up.
		foreach ( $this->download_for_customer as $user_id => $download_permissions ) {
			if ( $first_user_id === $user_id ) {
				// Already checked and deleted.
				continue;
			}

			$data_store->delete_by_user_id( $user_id );
			$dp_for_user = $data_store->get_downloads(
				array(
					'user_id' => $user_id,
				)
			);
			$this->assertEmpty( $dp_for_user );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
			}
		}
	}

	/**
	 * @testdox Deletes the download permission record and related download logs referred to by user email.
	 */
	public function test_delete_by_user_email() {
		$logs_per_permission = 2;
		$this->create_download_logs( $logs_per_permission );
		$data_store = WC_Data_Store::load( 'customer-download' );

		$downloads = array_values( $this->downloads );

		$first_user_email = $downloads[0]->get_user_email();
		$first_user_id    = $downloads[0]->get_user_id();
		$data_store->delete_by_user_email( $first_user_email );
		/**
		 * It is rather odd that only delete() sets the download's id to 0, other delete_* methods don't.
		 * Instead, try to (re)load the deleted download again, it shouldn't exist anymore.
		 */
		$this->assertEmpty(
			$data_store->get_downloads(
				array(
					'user_email' => $first_user_email,
				)
			)
		);

		// Test that respective download logs are also gone.
		$log_data_store = WC_Data_Store::load( 'customer-download-log' );
		foreach ( $this->download_for_customer[ $first_user_id ] as $download_permission ) {
			$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
		}

		// Test that the other downloads and download logs are intact.
		foreach ( $this->download_for_customer as $user_id => $download_permissions ) {
			if ( $first_user_id === $user_id ) {
				// Already checked and deleted.
				continue;
			}
			$user_email  = $download_permissions[0]->get_user_email();
			$dp_for_user = $data_store->get_downloads(
				array(
					'user_email' => $user_email,
				)
			);
			$this->assertEquals( count( $download_permissions ), count( $dp_for_user ) );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEquals( $logs_per_permission, count( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) ) );
			}
		}

		// Clean up.
		foreach ( $this->download_for_customer as $user_id => $download_permissions ) {
			if ( $first_user_id === $user_id ) {
				// Already checked and deleted.
				continue;
			}
			$user_email = $download_permissions[0]->get_user_email();
			$data_store->delete_by_user_email( $user_email );
			$dp_for_user = $data_store->get_downloads(
				array(
					'user_email' => $user_email,
				)
			);
			$this->assertEmpty( $dp_for_user );

			foreach ( $download_permissions as $download_permission ) {
				$this->assertEmpty( $log_data_store->get_download_logs_for_permission( $download_permission->get_id() ) );
			}
		}
	}
}
