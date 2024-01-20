<?php

/*
---------------------------------------------------------------------------------------------------------------
-- Default point year cookie, if not set, is created with an entry in the options.db JSON file.
---------------------------------------------------------------------------------------------------------------
*/

class cls_calendar_validation
{
  public $dbfileinfo;
  public $cookie_name = 'pointsystem_defaultyear';
  public $calendar_setting = '';
  public $calendar_start_month = 0;
  public $point_year = 0;

  function __construct()
  {
    // Open the options file, decode the JSON, and set the working point year variable.
    $this->dbfileinfo = '/home/ESIS/dataonly/htvfd_init/options.json';
    if (!file_exists($this->dbfileinfo)) {
      die('File with connection info ' . $this->dbfileinfo . ' not found');
    }
    $myfile = file_get_contents($this->dbfileinfo);
    $calendar_options = json_decode($myfile, true);  // true creates an associative array.
    $this->calendar_setting = $calendar_options['calendar'];
    $this->calendar_start_month = $calendar_options['start_month'];

    // Set the point year from the cookie (if it didn't expire) rather than the options file.
    // If the point_year cookie is not set, set it to the current date's point year value.
    if (!isset($_COOKIE[$this->cookie_name])) {
      $this->fct_reset_pointyear();
    } else {
      $this->point_year = $_COOKIE[$this->cookie_name];
    }
  }


  function __destruct()
  {
  }


  // This function contains a single function, but it is called by other programs.
  function fct_update_pointyear($arg_year)
  {
    setcookie($this->cookie_name, $arg_year, time() + (86400 * 1), "/"); // 86400 = 1 day
  }


  // This function will set the point year to the current date's year,
  // set the cookie, and write new data to the options file.
  function fct_reset_pointyear()
  {
    // Determine current Point Year and update the cookie and options file.
    $this->point_year = $this->fct_determine_pointyear();
    $this->fct_update_pointyear($this->point_year);   // Updates the cookie.

    // The following will rewrite the options file.
    $calendar_options['calendar'] = $this->calendar_setting;
    $calendar_options['start_month'] =  $this->calendar_start_month;
    $calendar_options['pointsystem_defaultyear'] = $this->point_year;
    $myfile = json_encode($calendar_options, JSON_PRETTY_PRINT);  // true creates an associative array.
    file_put_contents($this->dbfileinfo, $myfile);
  }


  // Determine the point year based on today's date and the default calendar value. 
  // Return the year that is calculated.
  function fct_determine_pointyear()
  {
    $temp_year = 0;
    $getdate = date(DATE_W3C);
    //$last_days = date("t");   // Returns number of days in a month.
    $date_parse_array = date_parse($getdate);
    if ($this->calendar_start_month == '12' and $date_parse_array['month'] == 12) {
      $temp_year = (string) ($date_parse_array['year'] + 1);
    } else {
      $temp_year = (string) ($date_parse_array['year']);
    }
    return $temp_year;
  }
}  // End of class definition


/*-------------------------------------------------------*/
/*   mainline of program
/*   this is used for testing only. Programs/modules
/*   should instantiate the class and use the functions
/*   within the class.
/*-------------------------------------------------------*/

?>