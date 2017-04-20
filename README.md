# ReRanger

A utility to deal with the case where a list of book index entries must have their page numbers incremented or decremented. This could be required due to the addition or subtraction of entire pages from a manuscript _after_ the index has already been created.

For example, some changes you made earlier caused the text to re-flow up and now you have a blank verso page before a chapter opener. That verso must be removed.

Or, the client has suddenly wants a photo spread, introducing new pages in the middle
of the book.

## Description

Index entries look something like:

AAFM (see Association of Air Force Missileers)
ABM (see Anti-Ballistic Missile)
AEC (see Atomic Energy Commission)
AFOSI (see Air Force Office of Special Investigations)
AFR 200-2|158, 225
AFR 35-99|156
AFSWC (see Armed Forces Special Weapons Command)
Aiken Air Force Station (South Carolina)|47, 127
Air Force Office of Special Investigations (AFOSI, OSI)|11, 51, 53--4, 144,
148, 230--1, 236, 244, 327, 260, 271, 295, 314, 325--7, 329, 337--41, 383,
393, 399, 412, 418, 422, 437--8, 528(r)
Air Force Weapons Laboratory|208--9, 302
Air Institute of Technology|33
Air Intelligence Requirements Division (AIRD)|33, 487
Air Materiel Command (AMC)|33, 330, 487, 528(r)
Alamogordo, New Mexico|52, 60, 91, 470, 530(r)
...

As should be apparent, not all entries have page numbers. Also, some lines
will be empty. These will be passed through unchanged.

We need to cut at the line delimiter, in this case a pipe ("|"), and pass the
second part--the list of page numbers--to the `processSeries()` method of this
class. This will run through each number and decrement, finally patching the
list back together.

(The pipe will become an en-space when the typesetting is done. The double
dashes will be changed to en-dashes.)

But some numbers are ranges (eg. 32--5, 230--21) which make this a challenge.
The second part of the range must be expanded before the number can be
incremented/decremented.

(This type of range is specific to books, and perhaps some other contexts.
If dealing with a normal range, eg. "45-48" then it's trivial to handle the
increment.)

Also, some numbers may have a string appended to denote that the page is in the
notes or references, eg. "n", "[n]", "(r)"

Finally, some numbers may be in roman numerals. While these are considered,
they are not yet handled. However, in the unlikely case that an index range
begins with roman and ends with arabic, the ending part will be handled. This
is not ideal but:

1. It's unlikely that prelim pages (roman) would require incrementing;
2. Manually adjusting prelim page numbers should be a snap unless you've got a ridiculously large prelim;
3. I have no pressing need for that right now.

I wrote this up in a couple of hours using PHP. My first inclination was
to do it with Python but, as I thought the process through, I kept thinking
about how I would do it with PHP. As time was short, I simply did it this way.
I may rewrite this in Python sometime as an exercise.

### This class requires PHP 5.5+

## Example Usage

```php

$splitter = "|";
$series   = ", ";
$range    = "--";
$inc      = -2;
$min_page = 150;   // defaults to 0
$ref      = "(n)"; // defaults to the empty string

$reRanger = new ReRanger($series, $range, $inc, $min_page, $ref);

// VERY basic!
$readPointer  = fopen($sourceFile, "r");
$writePointer = fopen($targetFile, "a");

while( ($line = fgets($readPointer)) !== false ) {

  // split each line between index entry and page numbers
  $line  = str_replace(PHP_EOL, "", $line);
  $split = explode($splitter, $line);

  $numbers = $reRanger->processSeries( trim($split[1]) );

  $line = trim($split[0]) . $splitter . $numbers;
  fwrite($writePointer, $line);
}
```
