<?php

use PHPUnit\Framework\TestCase;

class DarajaUtilTest extends TestCase {

	public function test_password_is_base64_of_shortcode_passkey_timestamp() {
		$this->assertSame(
			base64_encode( '445533pk20260903120509' ),
			Plug_One_Daraja_Client::password( '445533', 'pk', '20260903120509' )
		);
	}

	public function test_timestamp_is_fourteen_digits_in_eat() {
		$ts = Plug_One_Daraja_Client::timestamp();
		$this->assertMatchesRegularExpression( '/^\d{14}$/', $ts );
	}
}
