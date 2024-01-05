<?PHP

// This module will find any point sheets that overlap/intersect with another point sheet.
// Example: a member is at fire school, but there is also an activated alarm. The member
// at fire school should receive credit.

class cls_pointsheet_overlap {
  public $point_year = 0;
  public $db_info;
  public $connection;
  public $db_pointsheet;
  public $array_alldata = array();
  public $array_pointsheetnumbers = array();
  public $array_one = array();
  public $array_two = array();
  public $array_diff_one = array();
  public $array_diff_two = array();
  public $array_merged = array();

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
    try{
      return $_COOKIE['pointsystem_defaultyear'];
    }
    catch (Exception $e){
      die('In Point Sheet Overlap, error with the point year cookie: '.$e);
    }
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


  // This function will read the Point Sheet file and extract any point sheets where there is
  // a potential overlap of starting and ending times.
  // At least one point sheet will be a department-level point sheet (fire, drill, etc.).
  function fct_compare_PS(){
    $first_time_in = false;
    $second_time_in = false;
    // Read Point Sheet header file. at first read, initialize the "first in" variables.
    $sql = 'SELECT * FROM Pointsheet PS join TBL_master_pointcodes_V2 PC on PS.Sheet_ID = PC.TBL_master_sheetcode_ID where Point_Year = '.$this->point_year.' order by Starting_date, Ending_date, ID_Pointsheet;';
    $this->db_pointsheet = $this->fct_sql_query($sql);

    // Using foreach, read each row in the dataset.
    foreach($this->db_pointsheet as $PSH){
      if($first_time_in == false){
        $first_time_in = true;
        $wrk_pointsheet_nbr = $PSH['ID_Pointsheet'];
        $wrk_starting_date = $PSH['Starting_date'];
        $wrk_ending_date = $PSH['Ending_date'];
        $wrk_comments = $PSH['Comments'];
        $wrk_dept_points = $PSH['TBL_master_clothingallowance(Y,N)'];
      }
      else{
        // If the hold ending date of the prevous row is greater than the current row's starting date, print basic info.
        //if($wrk_ending_date > $PSH['Starting_date'] or $wrk_starting_date > $PSH['Ending_date']){
        if($wrk_ending_date > $PSH['Starting_date']){
          //$second_time_in = false;
          // Found potential overlap. Now determine if Line numbers on one pointsheet are on the other.
          $is_missing = false;
          $is_missing = $this->fct_compare_PS_detail($wrk_pointsheet_nbr, $PSH['ID_Pointsheet']);
          // if there are missing/mismatched line numbers and either one of the department points flags are true, load details.
          if($is_missing and ($wrk_dept_points == true or $PSH['TBL_master_clothingallowance(Y,N)'] == true)){
            if(!$second_time_in){
              $second_time_in = true;
              // create arrays to hold the data for two point sheets, their line numbers, and the merged line numbers.
              $wrk_array2obj1 = array("Pointsheet_number"=>$wrk_pointsheet_nbr, "starting_date"=>$wrk_starting_date, "ending_date"=>$wrk_ending_date, "comments"=>$wrk_comments, "dept_flag"=>$wrk_dept_points, "line_numbers"=>array($this->array_one));
            }
            $wrk_array2obj2 = array("Pointsheet_number"=>$PSH['ID_Pointsheet'], "starting_date"=>$PSH['Starting_date'], "ending_date"=>$PSH['Ending_date'], "comments"=>$PSH['Comments'], "dept_flag"=>$PSH['TBL_master_clothingallowance(Y,N)'], "line_numbers"=>array($this->array_two));
            $wrk_array2obj3 = array("merged_linenumbers"=>$this->array_merged);
            $wrk_array2 = array($wrk_array2obj1, $wrk_array2obj2, $wrk_array2obj3);
            array_push($this->array_alldata, $wrk_array2);
          }
        }
        // If the starting date of the current point sheet is greater than the previous point sheet's ending date,
        // reset the work (comparison) fields so the comparisons can start anew.
        // (basically start a new batch of point sheets to check.)
        else{
          // Reset the work fields.
          $second_time_in = false;
          $wrk_pointsheet_nbr = $PSH['ID_Pointsheet'];
          $wrk_starting_date = $PSH['Starting_date'];
          $wrk_ending_date = $PSH['Ending_date'];
          $wrk_comments = $PSH['Comments'];
          $wrk_dept_points = $PSH['TBL_master_clothingallowance(Y,N)'];
        }
      }
    }
    /*
    foreach($this->array_alldata as $line){
      foreach($line as $row){
        var_dump($row);
        echo '<br>';
      }
      echo '<p>';
    }
    */
  }


  // After determining there are potential overlapping point sheets, read the
  // Point sheet details and compare line numbers. If some are missing, set return flag to "true".
  function fct_compare_PS_detail($arg_hold_PSnumber, $arg_current_PSnumber){
    $this->array_one = array();
    $this->array_two = array();
    // Read "hold" and "current" point sheet details, place line numbers in an indexed array.
    $sql = 'SELECT * FROM Pointsheetdtl where ID_Pointsheetdtl ='.$arg_hold_PSnumber.' order by Line_nbr;';
    $return_hold = $this->fct_sql_query($sql);
    $sql = 'SELECT * FROM Pointsheetdtl where ID_Pointsheetdtl ='.$arg_current_PSnumber.' order by Line_nbr;';
    $return_current = $this->fct_sql_query($sql);

    // Place each dataset's line numbers into an array.
    foreach($return_hold as $row){
      array_push($this->array_one, $row['Line_nbr']);
    }
    foreach($return_current as $row){
      array_push($this->array_two, $row['Line_nbr']);
    }

    $this->array_merged = array_unique(array_merge($this->array_one, $this->array_two));
    // Note: "sort" function does the sort in-place to the same variable.
    sort($this->array_merged);
    // array_diff finds entries in the first argument that are NOT in the second argument.
    $this->array_diff_one = array_diff($this->array_merged, $this->array_one);
    $this->array_diff_two = array_diff($this->array_merged, $this->array_two);

    //if(count($this->array_diff_one) < count($this->array_merged) or count($this->array_diff_two) < count($this->array_merged)){
    if(count($this->array_diff_one) != 0 or count($this->array_diff_two) != 0 ) {
      return true;
    }
    else {
      return false;
    }
  }

}
// End of class definition

//-----------------------------------------------------------------------
//   mainline
//-----------------------------------------------------------------------

// There is no mainline for this program.
// Use another program to instantiate the class.

// The following is for test only...  comment-out the following lines when complete.
//$wrk_class_PSO = new cls_pointsheet_overlap();
//$wrk_class_PSO->fct_compare_PS();

?>
