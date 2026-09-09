<?php

use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase {

	public function test_normalizes_common_kenyan_formats() {
		$this->assertSame( '254712345678', Plug_One_Phone::normalize( '0712345678' ) );
		$this->assertSame( '254712345678', Plug_One_Phone::normalize( '712345678' ) );
		$this->assertSame( '254712345678', Plug_One_Phone::normalize( '254712345678' ) );
		$this->assertSame( '254712345678', Plug_One_Phone::normalize( '+254712345678' ) );
	}

	public function test_validates_safaricom_msisdn_only() {
		$this->assertTrue( Plug_One_Phone::is_valid( '254712345678' ) );
		$this->assertFalse( Plug_One_Phone::is_valid( '254112345678' ) );
		$this->assertFalse( Plug_One_Phone::is_valid( '0712345678' ) ); // not normalized
		$this->assertSame( '254716431039', Plug_One_Phone::validate_or_empty( '0716431039' ) );
		$this->assertSame( '', Plug_One_Phone::validate_or_empty( '0112345678' ) );
	}
}
