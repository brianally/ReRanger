<?php

// 52, 60, 91--8, 472--85, 532(r), 544--6(r)
// 52
// 52, 60
// 91--8
// 91--8, 472--85
// 532(r), 544--6(r)

use ReRanger\ReRanger;

class ReRangerTest extends \PHPUnit_Framework_TestCase {

	public function incrementedNumberProvider() {
		return [
			[", ", "--", 6, "52", "58"],
			[", ", "--", -2, "91", "89"]
		];
	}

	public function expandRangeEndProvider() {
		return [
			[", ", "--", 1, "91", "8", "98"],
			[", ", "--", 1, "29", "38", "38"],
			[", ", "--", 1, "94", "101", "101"]
		];
	}

	public function collapseRangeEndProvider() {
		return [
			[", ", "--", 1, "32", "38", "8"],
			[", ", "--", 1, "45", "68", "68"],
			[", ", "--", 1, "114", "116", "16"]
		];
	}

	public function multipleIncrementedNumbersProvider() {
		return [
			[", ", "--", 4, "92, 106", "96, 110"],
			[", ", "--", 3, "23, 28, 44, 91", "26, 31, 47, 94"],
			[", ", "--", -3, "23, 28, 44, 91", "20, 25, 41, 88"]
		];
	}

	public function incrementedRangeProvider() {
		return [
			[", ", "--", 8, "32--8", "40--6"],
			[", ", "--", 12, "51--63", "63--75"],
			[", ", "--", 2, "111--14", "113--16"],
			[", ", "--", -8, "32--8", "24--30"],
			[", ", "--", -12, "51--63", "39--51"],
			[", ", "--", -2, "111--14", "109--12"]
		];
	}

	public function multipleIncrementedRangesProvider() {
		return [
			[", ", "--", 8, "32--8, 41--5", "40--6, 49--53"],
			[", ", "--", 12, "51--63, 122--31", "63--75, 134--43"],
			[", ", "--", 2, "111--14, 118--119", "113--16, 120--1"],
			[", ", "--", -8, "32--8, 55--7", "24--30, 47--9"],
			[", ", "--", -12, "51--63, 66--81", "39--51, 54--69"],
			[", ", "--", -2, "111--14, 257--9", "109--12, 255--7"]
		];
	}

	public function referencesProvider() {
		return [
			[", ", "--", 8, 1, "(r)", "523(r)", "531(r)"],
			[", ", "--", 8, 1, "(n)", "523(n), 544--6(n)", "531(n), 552--4(n)"],
			[", ", "--", -8, 1, "[r]", "523[r], 544--6[r]", "515[r], 536--8[r]"],
			[", ", "--", 8, 1, "(r)", "523[r]", "523[r]"] // should pass this unaffected
		];
	}

	public function minPagesProvider() {
		return [
			[", ", "--", 2, 50, "(r)", "32, 45, 51, 129", "32, 45, 53, 131"],
			[", ", "--", 2, 50, "(r)", "32, 45, 49--51, 129", "32, 45, 49--53, 131"]
		];
	}
	
	/**
	 * @dataProvider incrementedNumberProvider
	 */
	public function testStepReturnsIncrementedNumber($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->step($input) );
	}
	

	/**
	 * @dataProvider expandRangeEndProvider
	 */
	public function testExpandRangeEnd($sd, $rd, $inc, $a, $b, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->expandRangeEnd($a, $b) );
	}
	

	/**
	 * @dataProvider collapseRangeEndProvider
	 */
	public function testCollapseRangeEnd($sd, $rd, $inc, $a, $b, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->collapseRangeEnd($a, $b) );
	}
	

	/**
	 * @dataProvider multipleIncrementedNumbersProvider
	 */
	public function testProcessSeriesReturnsMultipleIncrementedNumbers($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @dataProvider incrementedRangeProvider
	 */
	public function testProcessSeriesReturnsIncrementedRange($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @dataProvider multipleIncrementedRangesProvider
	 */
	public function testProcessSeriesReturnsMultipleIncrementedRanges($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @dataProvider referencesProvider
	 */
	public function testProcessSeriesHandlesReferences($sd, $rd, $inc, $min, $ref, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min, $ref);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @dataProvider minPagesProvider
	 */
	public function testProcessSeriesIgnoresMinPages($sd, $rd, $inc, $min, $ref, $input, $expected) {


		$reRanger = new ReRanger($sd, $rd, $inc, $min, $ref);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
}