<?php
namespace ReRanger;

use \InvalidArgumentException as InvalidArgumentException;

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
 * While looping through a list of index entries to process be sure to catch
 * InvalidArgumentException so you can decide for yourself whether to continue 
 * processing further lines. The exception message will provide the offending
 * string so you can search for it in your source file.
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
   * Ranges should be separated by the given range delimiter ("..", "--", etc.)
   * ie. "45..9" represents "45 to 49", "53..78" is "53 to 78", etc.
   *
   * A mixed series contains both single numbers and ranges, eg.
   * "23, 27, 35..7, 39, 52..5"
   *
   * @param   string  $strSeries delimited series of numbers
   * 
   * @return  string 
   * 
   * @throws  InvalidArgumentException if the series string contains a typo,
   *          such as a missing number
   *
   * @access  public
   */
  public function processSeries($strSeries) {
		$processed = [];
		$ref_len   = strlen($this->_reference_string) * -1;
		$chunks    = explode($this->_series_delimiter, $strSeries);

    foreach($chunks as $chunk) {
      // assume no reference string
      $ref   = "";
      $chunk = trim($chunk);

      if ( !strlen($chunk) ) {
      	throw new InvalidArgumentException("bad number series: ${strSeries}");
      }

      // save reference append if exists
      if ( substr_compare($chunk, $this->_reference_string, $ref_len) === 0 ) {

        $ref   = $this->_reference_string;
        $chunk = substr($chunk, 0, $ref_len);

      }

      try {
	      // is range? eg. 231-44
	      if ( stristr($chunk, $this->_range_delimiter) !== FALSE ) {

	        $chunk = $this->processRange($chunk);

	      } else {

	        $chunk = $this->step($chunk);

	      }
	    }
	    catch(InvalidArgumentException $e) {
	    	// catch, wrap, and re-throw so we can send the entire series back up
	    	throw new InvalidArgumentException("bad number series: ${strSeries}", 0, $e);
	    }

      $processed[] = "${chunk}${ref}";

    }

    return implode( $this->_series_delimiter, $processed );
  }


  /**
   * expands a range, individually increments each part,
   * then collapses both into a range again
   *
   * If the range delimiter is, eg. ".."
   * then a range looks like "82..8", or "239..43", etc.
   *
   * @param   string    $range
   * 
   * @return  string 
   * 
   * @throws  InvalidArgumentException if the string does not
   *          contain the range delimiter exactly once, or some other typo
   *
   * @access  public
   */
  public function processRange($range) {
    $parts = explode($this->_range_delimiter, $range);

    if ( sizeof($parts) !== 2 ) {
      throw new InvalidArgumentException("bad range: ${range}");
    }

    // sanity
    $start = $parts[0];
    $end   = $parts[1];

    // could be roman numerals from prelim
    if ( !is_numeric($start) ) {

      if ( !is_numeric($end) ) {
        
        return $start . $this->_range_delimiter . $end;

      } else if ( intval($end) > $this->_min_page ) {
      	// way out on the edge case: second number could be arabic
        
        $end = $this->step($end);

        return $start . $this->_range_delimiter . $end;
      }
    }

    // fill out $end with leading digits of $start
    $end = $this->expandRangeEnd($start, $end);

    // sanity
    if ( intval($end) <= intval($start) ) {
    	echo "bad range: ${start} to ${end}";
    	throw new InvalidArgumentException("bad range: ${start} to ${end}");
    }

    // does the addition/subtraction fall within this range?
    if ( intval($start) <= $this->_min_page &&  $this->_min_page < intval($end) ) {

    	// removing pages from within a range is problematic
    	if ( $this->_increment < 0 ) {
    		throw new InvalidArgumentException("Cannot remove pages from with range: ${range}");
    	}

    	// gap this range
    	return $this->splitRange($start, $end);
    }

    $start = $this->step($start);
    $end   = $this->step($end);

    $end = $this->collapseRangeEnd($start, $end);

    return $start . $this->_range_delimiter . $end;
  }


  /**
   * increments a single number
   *
   * @param		string   $input  
   *                          
   * @return	string
   *
   * @throws  InvalidArgumentException if the string is not numeric
   * 
   * @access  public
   */
  public function step($input) {

    if ( !is_numeric($input) ) {
      throw new InvalidArgumentException("bad number: ${input}");
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
   * @access  public
   */
  public function expandRangeEnd($start, $end) {
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
   * Will remove leading digits from $end, tenths or higher,
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
   * @throws  InvalidArgumentException if the numbers in the range are not sane
   *
   * @access  public
   */
  public function collapseRangeEnd($start, $end) {
    $aStart = str_split($start);
    $aEnd   = str_split($end);

    if ( sizeof($aStart) > 1 && sizeof($aStart) >= sizeof($aEnd) ) {

      // shift leading digits off start until the two
      // arrays are the same size
      while ( sizeof($aStart) != sizeof($aEnd) ) {
        array_shift($aStart);
      }

      // shift leading digits if they match in value
      while ( $aStart[0] === $aEnd[0] ) {

      	// skip if tenths special case
        if ( sizeof($aStart) == 2 && $aStart[0] === "1" ) {
          break;
        }

        array_shift($aStart);
        array_shift($aEnd);

        // wot?!
        if ( empty($aStart) || empty($aEnd) ) {          
          throw new InvalidArgumentException("Range doesn't collapse nicely: ${start}, ${end}");
        }

        if ( $aStart[0] === "0" && $aStart[0] !== $aEnd[0] ) {
          break;
        }
      }
    }
    return implode("", $aEnd);
  }


  /**
   * splits a range into two, with x pages between
   *
   * If the point at which incrementing should occur (min_page)
   * falls within an existing range, the range must be split. The
   * first will run from the start of the original range up to
   * min_page, inclusive. If start === min_page then it is returned
   * as an ordinary number, after stepping.
   * 
   * The second part of the range will run from (min_page + 1) to end
   * and then processed, unless end is only one more thean min_page,
   * in which case it is also stepped as an ordinary number.
   *
   * The two halves are then concatenated with the series delimiter
   * and returned.
   * 
   * @param  string $start beginning of range
   * @param  string $end   end of range
   * 
   * @return string        the two ranges or numbers, combined by
   *                       the series delimiter
   */
  public function splitRange($start, $end) {
  	$series = [];
  	$ranges = [];

  	// start is the same as min page; make standalone number
  	if ( intval($start) === $this->_min_page ) {
  		$series[] = $start;
  	}
  	else {
			$range    = $start . $this->_range_delimiter . strval($this->_min_page);
			$series[] = $this->processRange($range);
  	}

  	// end only one more than min_page; make standalone number after stepping
  	if ( intval($end) - $this->_min_page === 1 ) {
  		$series[] = $this->step($end);
  	}
  	else {
			$range    = strval($this->_min_page + 1). $this->_range_delimiter . $end;
			$series[] = $this->processRange($range);
  	}

  	return implode($this->_series_delimiter, $series);
  }

}

/*
 * Local variables:
 * tab-width: 2
 * c-basic-offset: 2
 * c-hanging-comment-ender-p: nil
 * End:
 */

