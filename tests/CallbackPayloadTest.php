<?php

use PHPUnit\Framework\TestCase;

class CallbackPayloadTest extends TestCase {

	public function test_summarize_success_callback() {
		$payload = array(
			'Body' => array(
				'stkCallback' => array(
					'MerchantRequestID' => 'mrc',
					'CheckoutRequestID' => 'ws_CO_TEST',
					'ResultCode'        => 0,
					'ResultDesc'        => 'The service request is processed successfully',
					'CallbackMetadata'  => array(
						'Item' => array(
							array( 'Name' => 'Amount', 'Value' => 1 ),
							array( 'Name' => 'MpesaReceiptNumber', 'Value' => 'UI830645WY' ),
							array( 'Name' => 'TransactionDate', 'Value' => '20260908210201' ),
						),
					),
				),
			),
		);

		$stk = Plug_One_Callback_Payload::stk_callback( $payload );
		$this->assertNotNull( $stk );

		$summary = Plug_One_Callback_Payload::summarize( $stk );
		$this->assertSame( 'ws_CO_TEST', $summary['checkout_id'] );
		$this->assertSame( 0, $summary['result_code'] );
		$this->assertSame( 'UI830645WY', $summary['receipt'] );
		$this->assertSame( 1, $summary['amount'] );
	}

	public function test_invalid_payload_returns_null() {
		$this->assertNull( Plug_One_Callback_Payload::stk_callback( array() ) );
		$this->assertNull( Plug_One_Callback_Payload::stk_callback( array( 'Body' => array() ) ) );
	}

	public function test_failure_status_mapping() {
		$this->assertSame( 'cancelled', Plug_One_Callback_Payload::failure_status( 1032 ) );
		$this->assertSame( 'timed_out', Plug_One_Callback_Payload::failure_status( 1037 ) );
		$this->assertSame( 'failed', Plug_One_Callback_Payload::failure_status( 1 ) );
	}
}
