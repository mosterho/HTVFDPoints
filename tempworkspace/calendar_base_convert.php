<?php

// Testing "base_convert" to do calendar math
// See what happens when converting numbers 1 through 12 from base10 to base12.

echo PHP_EOL;
for($i=0; $i<=12; $i++){
  echo PHP_EOL.' '.$i.'  '.base_convert($i,10,12);
}
echo PHP_EOL;
echo PHP_EOL;

// Now make December look like January
echo PHP_EOL;
for($i=12; $i<=24; $i++){
  echo PHP_EOL.' '.$i.'  '.base_convert($i,10,12);
}
echo PHP_EOL;
echo PHP_EOL;

var_dump(date(DATE_W3C));
echo PHP_EOL;
var_dump(date_create());   // This throws an error
echo PHP_EOL;
var_dump(date_create(date(DATE_W3C)));   // This also throws an error.
echo PHP_EOL.'NOTE: the difference in three date/time string formats, timezone formats and microseconds in the above var_dump results';
echo PHP_EOL;


?>
