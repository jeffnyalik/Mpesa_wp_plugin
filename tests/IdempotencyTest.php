<?php

use PHPUnit\Framework\TestCase;

class IdempotencyTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['plug_one_transients'] = array();
		$GLOBALS['plug_one_cache']      = array();
	}

	public function test_claim_complete_and_duplicate() {
		$key = 'callback:ws_CO_1';
		$this->assertTrue( Plug_One_Idempotency::claim( $key, 60 ) );
		$this->assertFalse( Plug_One_Idempotency::claim( $key, 60 ) );
		$this->assertTrue( Plug_One_Idempotency::is_pending( $key ) );

		Plug_One_Idempotency::complete( $key, 60 );
		$this->assertTrue( Plug_One_Idempotency::is_done( $key ) );
		$this->assertFalse( Plug_One_Idempotency::claim( $key, 60 ) );
	}

	public function test_release_allows_retry() {
		$key = 'stk:42';
		$this->assertTrue( Plug_One_Idempotency::claim( $key, 60 ) );
		Plug_One_Idempotency::release( $key );
		$this->assertTrue( Plug_One_Idempotency::claim( $key, 60 ) );
	}
}
