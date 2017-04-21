<?php

// 52, 60, 91--8, 472--85, 532(r), 544--6(r)
// 52
// 52, 60
// 91--8
// 91--8, 472--85
// 532(r), 544--6(r)

use ReRanger\ReRanger;

class ReRangerTest extends \PHPUnit_Framework_TestCase {
	
	public function testStepReturnsIncrementedNumber() {
		$input            = "52";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = 6;
		$expected         = "58";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->step($input) );
	}
	
	public function testStepReturnsDecrementedNumber() {
		$input            = "52";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$expected         = "50";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment, $min_page);

		$this->assertEquals( $expected, $reRanger->step($input) );
	}

	public function testExpandRangeEnd() {
		$input1           = "91";
		$input2           = "8";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$increment        = 1;
		$expected         = "98";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment);

		$this->assertEquals( $expected, $reRanger->expandRangeEnd($input1, $input2) );
	}

	public function testCollapseRangeEnd() {
		$input1           = "105";
		$input2           = "124";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$increment        = 1;
		$expected         = "24";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment);

		$this->assertEquals( $expected, $reRanger->collapseRangeEnd($input1, $input2) );
	}

	public function testProcessSeriesReturnsMultipleIncrementedNumbers() {
		$input            = "52, 129";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = 2;
		$expected         = "54, 131";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesReturnsMultipleDecrementedNumbers() {
		$input            = "52, 60";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = -2;
		$expected         = "50, 58";

		$reRanger = new ReRanger($series_delimiter, $range_delimiter, $increment);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}

	public function testProcessSeriesReturnsIncrementedRange() {
		$input            = "91--9";
		$series_delimiter = ", ";
		$range_delimiter  = "--";
		$min_page         = 1;
		$increment        = 2;
		$expected         = "93--101";

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