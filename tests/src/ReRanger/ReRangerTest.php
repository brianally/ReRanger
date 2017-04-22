<?php
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
			[", ", "--", -8, 1, "[r]", "523[r], 544--6[r]", "515[r], 536--8[r]"]
		];
	}

	public function minPagesProvider() {
		return [
			[", ", "--", 2, 50, "32, 45, 51, 129", "32, 45, 53, 131"],
			[", ", "--", 2, 50, "32, 45, 129", "32, 45, 131"],
			[", ", "--", 2, 50, "32, 45, 48--51, 129", "32, 45, 48--50, 53, 131"],
			[", ", "--", -2, 50, "32, 45, 129", "32, 45, 127"]
		];
	}

	public function splitRangeProvider() {
		return [
			[", ", "--", 6, "50", "49", "54", "49--50, 57--60"],
			[", ", "--", 6, "50", "50", "52", "50, 57--8"],
			[", ", "--", 6, "50", "50", "51", "50, 57"]
		];
	}

	public function romanNumeralsProvider() {
		return [
			[", ", "--", 1, "i", true],
			[", ", "--", 1, "v", true],
			[", ", "--", 1, "x", true],
			[", ", "--", 1, "xiv", true],
			[", ", "--", 1, "ibx", false],
			[", ", "--", 1, "ixv", true],
			[", ", "--", 1, "XIV", false]
		];
	}

	public function badIntProvider() {
		return [
			[", ", "--", 8, 1, "foo"],
			[", ", "--", 8, 1, "3foo"],
			[", ", "--", 8, 1, "foo5"],
			[", ", "--", 8, 1, ""],
			[", ", "--", 8, 1, true]
		];
	}

	public function badSeriesProvider() {
		return [
			[", ", "--", 1, 1, "92, , 106"],
			[", ", "--", 1, 1, "92,, 106"],
			[", ", "--", 1, 1, "92,106"]
		];
	}

	public function badReferenceProvider() {
		return [
			[", ", "--", 8, 1, "(r)", "523[r]"]
		];
	}
	
	/**
	 * @covers	ReRanger::step
	 * @dataProvider incrementedNumberProvider
	 */
	public function testStepReturnsIncrementedNumber($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->step($input) );
	}
	

	/**
	 * @covers	ReRanger::expandRangeEnd
	 * @dataProvider expandRangeEndProvider
	 */
	public function testExpandRangeEnd($sd, $rd, $inc, $a, $b, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->expandRangeEnd($a, $b) );
	}
	

	/**
	 * @covers	ReRanger::collapseRangeEnd
	 * @dataProvider collapseRangeEndProvider
	 */
	public function testCollapseRangeEnd($sd, $rd, $inc, $a, $b, $expected) {
		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->collapseRangeEnd($a, $b) );
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider multipleIncrementedNumbersProvider
	 */
	public function testProcessSeriesReturnsMultipleIncrementedNumbers($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider incrementedRangeProvider
	 */
	public function testProcessSeriesReturnsIncrementedRange($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider multipleIncrementedRangesProvider
	 */
	public function testProcessSeriesReturnsMultipleIncrementedRanges($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider referencesProvider
	 */
	public function testProcessSeriesHandlesReferences($sd, $rd, $inc, $min, $ref, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min, $ref);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider minPagesProvider
	 */
	public function testProcessSeriesIgnoresMinPages($sd, $rd, $inc, $min, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min);

		$this->assertEquals( $expected, $reRanger->processSeries($input) );
	}
	

	/**
	 * @covers	ReRanger::splitRange
	 * @dataProvider splitRangeProvider
	 */
	public function testSplitRangeSplitsRanges($sd, $rd, $inc, $min, $a, $b, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min);

		$this->assertEquals( $expected, $reRanger->splitRange($a, $b) );
	}
	

	/**
	 * @covers	ReRanger::isPrelim
	 * @dataProvider romanNumeralsProvider
	 */
	public function testIsPrelimMatchesRomanNumerals($sd, $rd, $inc, $input, $expected) {

		$reRanger = new ReRanger($sd, $rd, $inc);

		$this->assertSame( $expected, $reRanger->isPrelim($input) );
	}
	

	/**
	 * @covers	ReRanger::step
	 * @dataProvider badIntProvider
	 * @expectedException	InvalidArgumentException
	 */
	public function testStepExceptsOnBadInput($sd, $rd, $inc, $min, $input) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min);
		$reRanger->step($input);
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider badSeriesProvider
	 * @expectedException	InvalidArgumentException
	 */
	public function testProcessSeriesExceptsOnBadSeries($sd, $rd, $inc, $min, $input) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min);
		$reRanger->processSeries($input);
	}
	

	/**
	 * @covers	ReRanger::processSeries
	 * @dataProvider badReferenceProvider
	 * @expectedException	InvalidArgumentException
	 */
	public function testProcessSeriesExceptsOnBadReference($sd, $rd, $inc, $min, $ref, $input) {

		$reRanger = new ReRanger($sd, $rd, $inc, $min);
		$reRanger->processSeries($input);
	}
}