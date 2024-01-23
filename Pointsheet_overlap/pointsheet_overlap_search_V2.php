<?php

// Version II of Pointsheet Overlap search program

// This program will create an array containing point sheet data where the starting and ending dates/times overlap.
// The resulting array $array_alldata will contain the following:
// 1. two or more arrays that contains partial point sheet header information and an array of line numbers.
// 2. A summary array $array_merged ["merged_linenumbers"] that combines all line numbers from the overlapping point sheets.

// It will be helpful to use "soft wrap" while using any editor to display this code.
// The example/section of the array $array_alldata that contains two overlapping point sheets will look like the following.
// There are line breaks at the outermost array elements in the following example. The first point sheet contains line number 35 who was at fire school. The second point sheet for a fire overlaps and
// the merged_linenumbers contains line numbers from both. The second point sheet doesn't have line number 35, but the individual should receive credit while at fire school.

/*

array(3) {
[0]=> array(6) { ["Pointsheet_number"]=> string(5) "16359" ["starting_date"]=> string(19) "2023-04-10 19:00:00" ["ending_date"]=> string(19) "2023-04-10 23:00:00" ["comments"]=> string(21) "Fire Instructor Class" ["dept_flag"]=> string(1) "0" ["line_numbers"]=> array(1) { [0]=> string(2) "35" } }

[1]=> array(6) { ["Pointsheet_number"]=> string(5) "16360" ["starting_date"]=> string(19) "2023-04-10 19:25:00" ["ending_date"]=> string(19) "2023-04-10 20:15:00" ["comments"]=> string(80) "Brush Fire. 142 Beaver Run Rd. (transformer explosion set front lawn on fire)" ["dept_flag"]=> string(1) "1" ["line_numbers"]=> array(14) { [0]=> string(2) "11" [1]=> string(2) "19" [2]=> string(2) "22" [3]=> string(2) "31" [4]=> string(2) "42" [5]=> string(2) "43" [6]=> string(2) "47" [7]=> string(2) "52" [8]=> string(2) "57" [9]=> string(2) "58" [10]=> string(2) "59" [11]=> string(2) "61" [12]=> string(2) "63" [13]=> string(2) "64" } }

["merged_linenumbers"]=> array(15) { [0]=> string(2) "11" [1]=> string(2) "19" [2]=> string(2) "22" [3]=> string(2) "31" [4]=> string(2) "35" [5]=> string(2) "42" [6]=> string(2) "43" [7]=> string(2) "47" [8]=> string(2) "52" [9]=> string(2) "57" [10]=> string(2) "58" [11]=> string(2) "59" [12]=> string(2) "61" [13]=> string(2) "63" [14]=> string(2) "64" } }

*/

class cls_pointsheet_overlap {
  public $point_year = 0;
  public $db_info;
  public $connection;
  public $db_pointsheet;
  public $array_merged = array();
  public $array_alldata = array();

  public $wrk_pointsheet_nbr;
  public $wrk_starting_date;
  public $wrk_ending_date;
  public $wrk_comments;
  public $wrk_dept_points;

  function __construct() {
    // Retrieve the current Point Year.
    $this->point_year = $this->fct_grab_point_year();
    // Obtain database connection parameters, and make a DB connection.
    $dbfileinfo = '/home/ESIS/dataonly/htvfd_init/db.json';
    if(!file_exists($dbfileinfo)){
      die('File with connection info '.$dbfileinfo.' not found');
    }
    $myfile = file_get_contents($dbfileinfo);
    $this->db_info = json_decode($myfile, true);  // true creates an associative array.
    $this->connection = new mysqli($this->db_info['server'], $this->db_info['username'], $this->db_info['password'], $this->db_info['database']);
    if ($this->connection->connect_error) {
      die("Connection failed: " . $this->connection->connect_error);
    }
  }


  function __destruct(){
    // Close the DB connection.
    $this->connection->close();
  }


  // Determine point year from cookie value.
  function fct_grab_point_year(){
    return $_COOKIE['pointsystem_defaultyear'];
  }


  // This will run a basic SQL statement, namely to load pointsheet details and roster into their respective datasets.
  function fct_sql_query($arg_sql){
    $result = $this->connection->query($arg_sql);
    if(!isset($result)){
      echo '<p>';
      die($arg_sql.' sql statement is not in the proper format or did not produce a correct/workable dataset.');
    }
    else {
      return $result;
    }
  }


  function fct_psnumber_break($PSH){
    $this->wrk_pointsheet_nbr = $PSH['ID_Pointsheet'];
    $this->wrk_starting_date = $PSH['Starting_date'];
    $this->wrk_ending_date = $PSH['Ending_date'];
    $this->wrk_comments = $PSH['Comments'];
    $this->wrk_dept_points = $PSH['TBL_master_clothingallowance(Y,N)'];
  }


  // This function will read the Point Sheet table and determine if there is
  // a potential overlap of two or more point sheets' starting and ending dates/times.
  // At least one point sheet will be a department-level point sheet (fire, drill, etc.).
  function fct_compare_PS(){
    // Read Point Sheet header table for a point year.
    $sql = 'SELECT * FROM Pointsheet PS join TBL_master_pointcodes_V2 PC on PS.Sheet_ID = PC.TBL_master_sheetcode_ID where Point_Year = '.$this->point_year.' order by Starting_date, Ending_date, ID_Pointsheet;';
    $this->db_pointsheet = $this->fct_sql_query($sql);

    // Set the following as a first-row-read flag, so the comparison will work.
    $work_psnumber_break = false;
    $work_is_previousrow_loaded = false;
    $work_array_keep_temp = array();
    
    // Using foreach, read each row in the dataset.
    // The idea is to keep the previous row's data to compare with the current row's data.
    // i.e., to mimic a look-ahead of the two rows.

    // The following pseudo-code determines how PS header and detail are checked and kept if there are overlaps:
    // 1. read row from pointsheet dataset;
    //    if this is the first row read, initially load the "keep" variables (used to compare the next row that will be read);
    // 2. else if the "keep" previous row ending date is greater than the current row starting date, possible overlap! Add to arrays with PS header and detail data;
    // 3. else (to line #2, but now previous row's ending date <= previous starting date)
    //    if there is data in "keep" variables (i.e., two point sheets overlap), begin audit of PS headers and line numbers. Add array to all_data array if audit passes.
    // 4. also within part of else to line #2, but regardless of line #3 audit, run function to initially load the "keep" variables (basically resetting them).
    //    This "reset" is also used when no pointsheets overlap at all.

    // 1.
    foreach($this->db_pointsheet as $PSH){
      // The following "if" should be executed only once; do not reset the flag.
      // This will initialize the work variables used to compare point sheets.
      if($work_psnumber_break == false){
        $work_psnumber_break = true;
        $this->fct_psnumber_break($PSH);
      }
      // 2. If the hold ending date of the prevous row is greater than the current row's starting date, save basic info in arrays.
      else{
        if($this->wrk_ending_date > $PSH['Starting_date']){
          // Found potential overlap. load previous row's header and Line numbers into a hold array.
          // Perform this once for each "batch" of possible overlapping point sheets.
          // Note PSH = Point Sheet Header info... PSD = Point Sheet Details (e.g., member's line numbers)
          if(!$work_is_previousrow_loaded){
            $work_is_previousrow_loaded = true;
            $work_array_PSH = array("Pointsheet_number"=>$this->wrk_pointsheet_nbr, "starting_date"=>$this->wrk_starting_date, "ending_date"=>$this->wrk_ending_date, "comments"=>$this->wrk_comments, "dept_flag"=>$this->wrk_dept_points);
            $work_array_PSD = $this->fct_load_PS_detail($this->wrk_pointsheet_nbr);  // function returns an array of member's line numbers.
            $work_array_PSH["line_numbers"] = $work_array_PSD;  // Add PSD array onto PSH header (an array within an array)
            array_push($work_array_keep_temp, $work_array_PSH);  // Now push PSH/PSD array onto the temp "overlap" array.
          }
          // load the current row's header and Line numbers into a hold array.
          $work_array_PSH = array("Pointsheet_number"=>$PSH['ID_Pointsheet'], "starting_date"=>$PSH['Starting_date'], "ending_date"=>$PSH['Ending_date'], "comments"=>$PSH['Comments'], "dept_flag"=>$PSH['TBL_master_clothingallowance(Y,N)']);
          $work_array_PSD = $this->fct_load_PS_detail($PSH['ID_Pointsheet']);  // function returns an array of line numbers.
          $work_array_PSH["line_numbers"] = $work_array_PSD;  // Add PSD array onto PSH header (so an array within an array)
          array_push($work_array_keep_temp, $work_array_PSH);  // Now push PSH/PSD array onto the temp "overlap" array.
        }

        // 3. The prevous row's ending date is NOT greater than the current row's starting date.
        else{
          // if there is data in the hold array, perform audit of data (at least one dept. level point sheet exists, etc.)
          $is_valid_data = false;
          if(count($work_array_keep_temp) > 0){
            foreach($work_array_keep_temp as $row1){
              // If at least one point sheet is dept. level, set flag and break out of foreach loop.
              if($row1['dept_flag'] == true){
                $is_valid_data = true;
                break;
              }
            }
          }
          // If there is at least one dept. level point sheet, continue with line number checks and updates.
          if($is_valid_data == true){
            // Ensure  array_merged is unique and sorted.
            $this->array_merged = array_unique($this->array_merged);
            sort($this->array_merged);  // Note: "sort" function does the sort in-place to the same variable.
            $work_array_keep_temp["merged_linenumbers"] = $this->array_merged;  // add 'merged_linenumbers' key and data.
            array_push($this->array_alldata, $work_array_keep_temp);  // Add merged line numbers to the array containing multiple point sheet arrays. 
          }

          // 4. Reset work variables.
          $this->fct_psnumber_break($PSH);   // Reset the hold fields for checking on overlapping point sheets.
          $work_array_keep_temp = array();
          $this->array_merged = array();
          $work_array_PSH = array();
          $work_is_previousrow_loaded = false;
        }
      }
    } // End of foreach read of point sheet info.
  }


  // Load Point sheet details into an array. This will be pushed onto point sheet header info.
  function fct_load_PS_detail($arg_PSnumber){
    $array_psd = array();  // Point sheet detail's line numbers.
    // Read point sheet details, place line numbers in an indexed array.
    $sql = 'SELECT * FROM Pointsheetdtl where ID_Pointsheetdtl ='.$arg_PSnumber.' order by Line_nbr;';
    $return_hold = $this->fct_sql_query($sql);
    // Place each dataset's line numbers into an array and the merged line number array.
    foreach($return_hold as $row){
      array_push($array_psd,          $row['Line_nbr']);  // Add line number to current point sheet array.
      array_push($this->array_merged, $row['Line_nbr']);  // Add line number to merged array for multiple point sheet comparisons.
    }
    return $array_psd;
  }

}
// End of class definition

//-----------------------------------------------------------------------
//   mainline
//-----------------------------------------------------------------------

// There is no mainline for this program. Instantiaite the cls_pointsheet_overlap class 
// in the calling program.


?>
