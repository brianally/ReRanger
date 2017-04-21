<?php
namespace ReRanger;

/**
 * ReRanger
 * 
 * A utility to deal with the case that a list of book index entries must
 * have their page numbers incremented or decremented due to the addition
 * or subtraction of entire pages from a manuscript.
 * 
 * Index entries look something like:
 * 
 * AAFM (see Association of Air Force Missileers)
 * ABM (see Anti-Ballistic Missile)
 * AEC (see Atomic Energy Commission)
 * AFOSI (see Air Force Office of Special Investigations)
 * AFR 200-2|158, 225
 * AFR 35-99|156
 * AFSWC (see Armed Forces Special Weapons Command)
 * Aiken Air Force Station (South Carolina)|47, 127
 * Air Force Office of Special Investigations (AFOSI, OSI)|11, 51, 53--4, 144,
 * 148, 230--1, 236, 244, 327, 260, 271, 295, 314, 325--7, 329, 337--41, 383,
 * 393, 399, 412, 418, 422, 437--8, 528(r)
 * Air Force Weapons Laboratory|208--9, 302
 * Air Institute of Technology|33
 * Air Intelligence Requirements Division (AIRD)|33, 487
 * Air Materiel Command (AMC)|33, 330, 487, 528(r)
 * Alamogordo, New Mexico|52, 60, 91, 470, 530(r)
 * ...
 * 
 * As should be apparent, not all entries have page numbers. Also, some lines
 * will be empty. These will be passed through unchanged.
 * 
 * We need to cut at the line delimiter, in this case a pipe ("|"), and pass the
 * second part--the list of page numbers--to the processSeries() method of this
 * class. This will run through each number and decrement, finally patching the
 * list back together.
 * 
 * (The pipe will become an en-space when the typesetting is done. The double
 * dashes will be changed to en-dashes.)
 * 
 * But some numbers are ranges (eg. 32--5, 230--41) which make this a challenge.
 * The second part of the range must be expanded before the number can be
 * incremented/decremented.
 *
 * (This type of range is specific to books, and perhaps some other contexts.
 * If dealing with a normal range, eg. "45-48" then it's trivial to handle the
 * increment.)
 * 
 * Also, some numbers may have a string appended to denote that the page is in the
 * notes or references, eg. "n", "[n]", "(r)"
 * 
 * Finally, some numbers may be in roman numerals. While these are considered,
 * they are not yet handled. However, in the unlikely case that an index range
 * begins with roman and ends with arabic, the ending part will be handled. This
 * is not ideal but:
 * 
 * a) It's unlikely that prelim pages (roman) would require incrementing;
 * b) Manually adjusting prelim page numbers should be a snap unless you've got
 *    a ridiculously large prelim;
 * c) I have no pressing need for that right now.
 * 
 * @author    brian ally
 * @link      https://github.com/brianally/ReRanger
 * @license   MIT http://opensource.org/licenses/MIT
 */
class ReRanger {

	/**
	 * character(s) separating page numbers, eg. ", "
	 * 
	 * @var string
	 */
  private $_series_delimiter;

  /**
   * character(s) separating page numbers in range,
   * eg. "-", "--", ".."
   * 
   * @var string
   */
  private $_range_delimiter;

  /**
   * number of pages to increment (negative for decrement)
   * 
   * @var int
   */
  private $_increment;

  /**
   * page number ABOVE which all other pages
   * should be incremented/decremented
   * 
   * @var int
   */
  private $_min_page;

  /**
   * reference/notes append, eg. 505(n), 432(r), etc.
   * 
   * @var string
   */
  private $_reference_string;


  /**
   * constructor
   * 
   * @param string  $sd
   * @param string  $rd
   * @param string  $rs
   * @param int     $min
   * @param int     $inc
   *
   * @return  void
   * 
   * @access  public
   */
  public function __construct($sd, $rd, $inc, $min = 0, $rs = "") {
    $this->_series_delimiter = $sd;
    $this->_range_delimiter  = $rd;
    $this->_increment        = intval($inc);
    $this->_min_page         = intval($min);
    $this->_reference_string = $rs;
  }


  /**
   * splits a number series and handles each member
   *
   * Given a string, "45, 64, 178" the series delimiter would be ", "
   * Ranges should be separated by the given range delimiter,
   * ie. "45..9" represents "45 to 49", "53..78" is "53 to 78", etc.
   *
   * A mixed series contains both single numbers and ranges, eg.
   * "23, 27, 35..7, 39, 52..5"
   *
   * @param   string  $strNums delimited series of numbers
   * 
   * @return  string 
   * 
   * @throws  Exception if the series string contains a typo,
   *          such as a missing number
   *
   * @access  public
   */
  public function processSeries($strNums) {
		$out     = [];
		$ref_len = strlen($this->_reference_string) * -1;
		$chunks  = explode($this->_series_delimiter, $strNums);

    foreach($chunks as $chunk) {
      // assume no reference string
      $ref   = "";
      $chunk = trim($chunk);

      if ( !strlen($chunk) ) {
      	throw new Exception("bad number series: ${strNums}");
      }

      // save reference append if exists
      if ( substr_compare($chunk, $this->_reference_string, $ref_len) === 0 ) {

        $ref   = $this->_reference_string;
        $chunk = substr($chunk, 0, $ref_len);

      }

      // is range? eg. 231-44
      if ( stristr($chunk, $this->_range_delimiter) !== FALSE ) {

        $chunk = $this->processRange($chunk);

      } else {

        $chunk = $this->step($chunk);

      }

      $out[] = "${chunk}${ref}";

    }

    return implode($this->_series_delimiter, $out);
  }


  /**
   * splits a range, individually increments each part,
   * then collapses both into a range again
   *
   * If the range delimiter is, eg. ".."
   * then a range looks like "82..8", or "239..43", etc.
   *
   * @param   string    $range
   * 
   * @return  string 
   * 
   * @throws  Exception if the string does not contain the range delimiter 
   *                    or there are multiple instances
   *
   * @access  public
   */
  public function processRange($range) {
    $parts = explode($this->_range_delimiter, $range);

    if ( sizeof($parts) !== 2 ) {
      throw new Exception("bad range: ${range}");
    }

    // sanity
    $start = $parts[0];
    $end   = $parts[1];


    // could be roman numerals from prelim
    if ( !is_numeric($start) ) {

      if ( !is_numeric($end) ) {
        
        return $start . $this->_range_delimiter . $end;

      } else if ( intval($end) > $this->_min_page ) {

        // edge case: second number could be arabic
        $end = $this->step($end);

        return $start . $this->_range_delimiter . $end;
      }
    }

    // fill out $end with leading digits of $start
    $end = $this->expandRangeEnd($start, $end);

    // sanity
    if ( intval($end) <= intval($start) ) {
    	throw new Exception("bad range: ${start} to ${end}");
    }

    $start = $this->step($start);
    $end   = $this->step($end);

    $end = $this->collapseRangeEnd($start, $end);

    return $start . $this->_range_delimiter . $end;
  }


  /**
   * increments a single number
   *
   * If the input is not a valid number it will
   * be returned unchanged.
   *
   * @param  string   $input  
   *                          
   * @return string
   *
   * @access  private
   */
  private function step($input) {

    if ( !is_numeric($input) ) {
      return $input;
    }

    $num = intval($input);

    if ( $num > $this->_min_page ) {
      $num = $num + $this->_increment;
    }

    return strval($num);
  }



  /**
   * adds leading digits from first part of range
   * to the beginning of ending part
   * 
   * eg. 73..9 => 79; 225..36 => 236
   * 
   * If both parts have equal length, return $end as is
   * eg. 64..85 => 85
   * 
   * If second part is longer, again return $end as is
   * eg. 94..121 => 121
   *
   * @param  string $start
   * @param  string $end
   * 
   * @return string modified end
   *
   * @access  private
   */
  private function expandRangeEnd($start, $end) {
    $aStart = array_reverse( str_split($start) );
    $aEnd   = array_reverse( str_split($end) );

    if ( sizeof($aStart) > sizeof($aEnd) ) {

      while ( sizeof($aStart) > sizeof($aEnd) ) {

        // add last digit from $aStart to $aEnd
        $aEnd = array_pad( $aEnd, sizeof($aEnd) + 1, $aStart[ sizeof($aEnd) ] );

      }
    }
    return implode( "", array_reverse($aEnd) );
  }



  /**
   * removes leading digit(s) from range end
   * 
   * Will remove leading digits from $end, teths or higher,
   * if they correspond to those from $start, unless that digit
   * is 1, which is a special case. eg.
   *
   * incorrect: 512..4
   * correct: 512..14
   * 
   * If $end is negative, normalise it according to nearest.
   * 
   * @param   string  $start
   * @param   string  $end
   * 
   * @return  string  modified end
   * 
   * @throws  Exception if the numbers in the range are not sane
   *
   * @access  private
   */
  private function collapseRangeEnd($start, $end) {
    $aStart = str_split($start);
    $aEnd   = str_split($end);
    $debugS = $debugE = []; // capture shifted values in case things go south


    if ( sizeof($aStart) > 1 && sizeof($aStart) >= sizeof($aEnd) ) {

      // shift leading digits off start until the two
      // have the same number of them
      while ( sizeof($aStart) != sizeof($aEnd) ) {
        $debugS[] = array_shift($aStart);
      }

      // shift leading digits if they match in value
      while ( $aStart[0] === $aEnd[0] ) {

      	// skip if tenths special case
        if ( $aStart[0] === "1" && sizeof($aStart) == 2 ) {
          break;
        }

        $debugS[] = array_shift($aStart);
        $debugE[] = array_shift($aEnd);

        if ( $aStart[0] === "0" && $aStart[0] !== $aEnd[0] ) {
          break;
        }

        // wot?!
        if ( empty($aStart) || empty($aEnd) ) {
          $message = ["Something's not right with this!"];
          $message[] = "start: " . $start;
          $message[] = "end: " . $end;
          $message[] = print_r($debugS, true);
          $message[] = print_r($debugE, true);
          $message[] = print_r($aStart, true);
          $message[] = print_r($aEnd, true);
          
          throw new Exception( implode(PHP_EOL, $message) );
        }
      }
    }
    return implode("", $aEnd);
  }

}

/*
 * Local variables:
 * tab-width: 2
 * c-basic-offset: 2
 * c-hanging-comment-ender-p: nil
 * End:
 */

