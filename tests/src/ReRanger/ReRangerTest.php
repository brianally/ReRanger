<?php

// 52, 60, 91--8, 472--85, 532(r), 544--6(r)
// 52
// 52, 60
// 91--8
// 91--8, 472--85
// 532(r), 544--6(r)

use ReRanger\ReRanger;

class ReRangerTest extends \PHPUnit_Framework_TestCase {
	
	public function testProcessSeriesReturnsIncrementedNumber() {
		$input            = "52";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = 6;
		$expected         = "58";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	
	public function testProcessSeriesReturnsDecrementedNumber() {
		$input            = "52";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$expected         = "50";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesReturnsMultipleDecrementedNumbers() {
		$input            = "52, 60";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$expected         = "50, 58";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesReturnsDecrementedRange() {
		$input            = "91--8";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$expected         = "89--96";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesReturnsMultipleDecrementedRanges() {
		$input            = "91--8, 472--85";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$expected         = "89--96, 470--83";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesHandlesNotes() {
		$input            = "532(r), 544--6(r)";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$ref_str          = "(r)";
		$expected         = "530(r), 542--4(r)";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page, $ref_str);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesIgnoresMinPages() {
		$input            = "52, 60, 91--8, 472--85, 532(r), 544--6(r)";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 68;
		$increment        = 6;
		$ref_str          = "(r)";
		$expected         = "52, 60, 97--104, 478--91, 538(r), 550--2(r)";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page, $ref_str);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
}