<?php

// SAC = State Association Calculations
// Including functions other than State Association calculations are now here (e.g., LOSAP)
// rather than using Scriptcase internal or even external scripts.
class cls_SAC_calculations{

  public $wrk_pointyear = 0;
  public $db_info;
  public $options;
  public $roster_dataset;
  public $pointsheet_dataset;
  public $calendar_option = '';
  public $exempt_percent = 0;
  public $nonexempt_percent = 0;
  public $db_connection;   // Keep the DB connection open for the entire class rather than open and close multiple times for each function.
  public $SAC_point_summary_array = array(array(0,0,0,0,0,0,0,0,0,0,0,0), array(0,0,0,0,0,0,0,0,0,0,0,0), array(0,0,0,0,0,0,0,0,0,0,0,0));


  function __construct($arg_dbfileinfo, $arg_options){
    //
    // the __construct will setup the following datasets:
    // set the current point year
    // retrieve database connection variables (server, user, etc.)
    // retrive options (mainly to determine point year calendar)
    // create a databse connection for the entire class.
    // Retrieve the roster dataset.
    // Retrieve the pointsheet (header) dataset for a year.
    // Calculate and set the state association pivot table/dataset summary for one year (3 rows)
    // Determine exempt and nonexempt percentages for state association, clothing allowance, etc.
    //

    // begin try/catch block...
    try{
      if(isset($_COOKIE['pointsystem_defaultyear'])){
        $this->wrk_pointyear = $_COOKIE['pointsystem_defaultyear'];
        #echo 'Cookie set!!!!!'.PHP_EOL;
      }
      else{
        $wrk_datetime = (int)(date('Y'));
        $this->wrk_pointyear = $wrk_datetime;
        echo PHP_EOL.'Cookie not set.......'.$this->wrk_pointyear.PHP_EOL;
      }

      $myfile = file_get_contents($arg_dbfileinfo);
      $this->db_info = json_decode($myfile, true);
      $myoptions = file_get_contents($arg_options);
      $this->options = json_decode($myoptions, true);
      $this->open_db_connection();

      $runthis = "SELECT member_nbr, Line_number, last_name, Status, Status_Overall,  State_assoc_id, State_assoc_status FROM Roster ";
      $this->roster_dataset = $this->db_connection->query($runthis);

      //-----------------------------------------------------------------------------
      // Summarize all pointsheets that are used to calculate State Association percentages
      // State Assocation percentages are calculated on a month-to-month basis
      // 84 months are required to achieve Exempt status (technically it's not 7 years,
      // although the state association membership report is submitted with one year's worth of data)
      // Use the standard point year as the from and to dates
      $runthis = 'SELECT
      YEAR(Starting_date) AS `calc_point_year`,
      MONTH(Starting_date) AS `calc_point_month`,
      Point_Year,
      PC.TBL_master_sheetcode AS `sheetcode`,
      COUNT(*) as `rowcount`
      FROM
      Pointsheet PS
      JOIN
      TBL_master_pointcodes_V2 PC ON PS.Sheet_ID = PC.TBL_master_sheetcode_ID
      WHERE
      `TBL_master_stateassoc(Y,N)` = TRUE
      and Point_Year = '.$this->wrk_pointyear.'
      GROUP BY PC.TBL_master_sheetcode,  `calc_point_year` , `calc_point_month`
      ORDER BY PC.TBL_master_sheetcode,  `calc_point_year` , `calc_point_month`;
      ';
      $this->pointsheet_dataset = $this->db_connection->query($runthis);


      //-----------------------------------------------------------------------------
      // Determine the type of calendar year to use
      // P = HTVFD Point year (December 1 to November 30
      // C normal January to December
      if($this->options['calendar']){
        $this->calendar_option = $this->options['calendar'];
      }
      else{
        $this->calendar_option = 'C';
      }

      //******************************************************************************************************
      // Now arrange the summarized total company point sheets as a pivot table by sheet code as the x axis
      // and months as the Y axis.
      // NOTE: we can only have a total of 24 drills in a year (credited)
      // Keep total fires/alarms separate, but combine drills. If total > 2, use 2 (maximum per month)
      // $wrk_array will hold arrays of 1. total fires/alarms, 2. total drills, and 3. the sum of those two arrays.

      foreach($this->pointsheet_dataset as $rs2){
        // Determine index for months data
        // $idx is used against the multi-dimension array
        // if point year is 'P', point year starts in December and ends in November.
        // (e.g., December (normally month 12) is the first month of the year)
        // so the month-to-index starts as December/12 is index 0, then january is index 1, ... November  is index 11
        // and so on (i.e., except for December, the month happens to be the same as the index)
        // DECIDED TO DO THE FOLLOWING RATHER THAN MODULUS/12... SEEMS EASIER TO UNDERSTAND?
        if($this->calendar_option == 'P'){
          if($rs2['calc_point_month'] == 12){
            $idx = 0;
          }
          else{
            $idx = $rs2['calc_point_month'];
          }
        }
        // If this is a "regular" calendar year starting in January, the month-to-index
        // (e.g., January is the first month of the year)
        // will be one less than the month (January is index 0, ... December is index 11)
        else{
          $idx = $rs2['calc_point_month'] - 1;
        }
        // Assign sheet code to a row (fires or weekly drills)
        // $idx2 = 0 for fires, $idx2 = 1 for drills.
        if($rs2['sheetcode'] == 10){
          $idx2 = 0;
        }
        else{
          $idx2 = 1;
        }
        // Accumulate count of pointsheets (which is really only applicable for drills).
        $this->SAC_point_summary_array[$idx2][$idx] += $rs2['rowcount'];
      }
      // Go through the work array; if any month's drill count > 2, set it equal to 2
      // then get sum of fires/alarms and drills
      for($i = 0; $i <= 11; $i++){
        // check sum of drills for a month
        if($this->SAC_point_summary_array[1][$i] > 2){
          $this->SAC_point_summary_array[1][$i] = 2;
        }
        // Place the sum of fires/alarms[0] plus adjusted drills[1] into last row of $wrk_array[2]
        $this->SAC_point_summary_array[2][$i] = $this->SAC_point_summary_array[0][$i] + $this->SAC_point_summary_array[1][$i];
      }



      // Retrieve clothing allowance percentage (basically exempt (21) and non-exempt (22) percentages)
      $runthis = 'SELECT idTBL_Values, TBL_Values_desc, TBL_Values_numeric  FROM TBL_Values where idTBL_Values in (21, 22) order by idTBL_Values;';
      $result = $this->db_connection->query($runthis);
      if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
          if($row['idTBL_Values'] == 21){
            $this->exempt_percent = $row['TBL_Values_numeric'];
          }
          else{
            $this->nonexempt_percent = $row['TBL_Values_numeric'];
          }
        }
      }


      $this->close_db_connection();
    }
    ///// End of TRY block...

    catch(Exception $ex){
      $code = $ex->getCode();
      $message = $ex->getMessage();
      $file = $ex->getFile();
      $line = $ex->getLine();
      echo "Exception thrown in $file on line $line: [Code $code]  $message";
      echo "BIGLY ERROR in SAC_calculations.php in __construct !!!!";
      exit();
    }
  }
  // End of __construct



  function load_details() {

    $this->open_db_connection();

    $runthis = 'TRUNCATE tmp_roster_by_stateassocmonth;';
    $this->db_connection->query($runthis);

    // arraymonthcomp will contain the results of the percentage calculation near the end of this method
    // arraycompany holds the multi-dimensional array from load_pointsummary of total company point sheets
    $arraymonthcomp = array(0,0,0,0,0,0,0,0,0,0,0,0);
    $arraycompany = $this->SAC_point_summary_array;
    $wrk_total_summary = FALSE;

    // Read the Roster dataset of members trying to achieve Exempt status that
    // originated from the load_Roster method
    foreach($this->roster_dataset as $rosterdata){
      // From the Roster dataset, find the pointsheets for that member applicable for the State Assoc.
      $runthis2 = 'SELECT
      member_nbr,
      Line_nbr,
      State_assoc_id,
      YEAR(Starting_date) AS `calc_point_year`,
      MONTH(Starting_date) AS `calc_point_month`,
      Point_Year,
      TBL_master_sheetcode as `sheetcode`,
      COUNT(*) as `rowcount`
      FROM view_generic_pointsheet_pointsheetdtl_roster_masterpointcode
      WHERE
      `TBL_master_stateassoc(Y,N)` = TRUE
      and Point_year = '.$this->wrk_pointyear.'
      AND member_nbr = '.$rosterdata['member_nbr'].'
      GROUP BY  member_nbr, Line_nbr, State_assoc_id, `calc_point_year` , `calc_point_month`, TBL_master_sheetcode
      ORDER BY  member_nbr, Line_nbr, State_assoc_id, `calc_point_year` , `calc_point_month`, TBL_master_sheetcode ;';

      $rs_pointsheets = $this->db_connection->query($runthis2);

      // A BETTER WAY of finding no data is check _numOfRows
      // but should also do
      // if($rs_pointsheets){}
      if ($rs_pointsheets->num_rows == 0) {
        //echo '<br>No rows retrieved accessing pointsheets for a member number (load_details): '.$rosterdata['member_nbr'];
        $arraymonth = array(array(0,0,0,0,0,0,0,0,0,0,0,0), array(0,0,0,0,0,0,0,0,0,0,0,0), array(0,0,0,0,0,0,0,0,0,0,0,0));
        $arraycalc = array(0,0,0,0,0,0,0,0,0,0,0,0);
        $arraycredited = array(0,0,0,0,0,0,0,0,0,0,0,0);
      }
      else {
        // Setup an individual's data similar to the company level pointsheet summary array
        // (multidimensional array by fires/alarms, drills/meetings and total of the two elements)
        //echo '<br>*** Rows were retrieved for member number (load_details): '.$rosterdata['member_nbr'];
        $new_sheetcode = True;
        $wrk_count = 0;
        foreach($rs_pointsheets as $pointsheet){
          if($new_sheetcode == True){
            $new_sheetcode = False;
            $wrk_member_nbr = $pointsheet['member_nbr'];
            $wrk_line_nbr = $pointsheet['Line_nbr'];
            $wrk_state_assoc_id = $pointsheet['State_assoc_id'];
            $arraymonth = array(array(0,0,0,0,0,0,0,0,0,0,0,0), array(0,0,0,0,0,0,0,0,0,0,0,0), array(0,0,0,0,0,0,0,0,0,0,0,0));
          }

          // Same comparison of point year as pointsummary:
          // if Pointyear, start with December
          if($this->calendar_option == 'P'){
            if($pointsheet['calc_point_month'] == 12){
              $wrk_pointmonth = 0;
            }
            else{
              $wrk_pointmonth = $pointsheet['calc_point_month'];
            }
          }
          else{
            $wrk_pointmonth = $pointsheet['calc_point_month'] - 1;
          }
          // If the sheet code is 10, add as usual
          // if the sheet code is something else, allow a maximum of two per month
          // $idx = 0 for fires, $idx = 1 for drills
          if($pointsheet['sheetcode'] == 10){
            $idx = 0;
          }
          else{
            $idx = 1;
          }
          $wrk_count = $pointsheet['rowcount'];
          // Determine the array index to insert the data based on month
          $arraymonth[$idx][$wrk_pointmonth] = $wrk_count;
          // if sheet code is 10, reset the count to 0 so multiple sheet codes
          // for 20 can be used to accumulate the two counts.
          if($pointsheet['sheetcode'] == 10){
            $wrk_count = 0;
          }
        }

        // Determine the credited value for combined meetings and drills (max two per month) and
        // summarize the multidimensional array indexes to the last row, then
        // calculate the member's percentage $arraycalc
        $arraycredited = array(0,0,0,0,0,0,0,0,0,0,0,0);
        $arraycalc = array(0,0,0,0,0,0,0,0,0,0,0,0);
        for($i = 0; $i <= 11; $i++){
          // Calculate total credited drills/meetings
          $arraymonth[2][$i] = min(2, $arraymonth[1][$i]);
          if($arraycompany[2][$i] > 0){
            $arraycalc[$i] = (($arraymonth[0][$i] + $arraymonth[2][$i]) / $arraycompany[2][$i]) * 100.0;
          }
          $arraycredited[$i] = $arraymonth[0][$i] + $arraymonth[2][$i];
        }
        // All calculations are complete. Write the data to the tmp_roster_by_stateassocmonth table
        $this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 900, $wrk_state_assoc_id, '"Total Fires/Alarms Attended"', $arraymonth[0]);
        //write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 901, $wrk_state_assoc_id, '"Total Drills Attended"', $arraymonth[1]);
        $this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 902, $wrk_state_assoc_id, '"Total Drills Credited (adjusted max 2/month)"', $arraymonth[2]);
        $this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 903, $wrk_state_assoc_id, '"Total Credited "', $arraycredited);

        // If this is the first time thru for writing total lines, set a flag to only write once
        if($wrk_total_summary == FALSE){
          $wrk_total_summary = TRUE;
          #$this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 910, $wrk_state_assoc_id, '"* Total Fires/Alarms Company"', $arraycompany[0]);
          #$this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 911, $wrk_state_assoc_id, '"* Total Drills Company (adjusted max 2/month)"', $arraycompany[1]);
          #$this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 912, $wrk_state_assoc_id, '"* Total Company"', $arraycompany[2]);
          $this->write_data($this->wrk_pointyear, 0, 0, 910, $wrk_state_assoc_id, '"* Total Fires/Alarms Company"', $arraycompany[0]);
          $this->write_data($this->wrk_pointyear, 0, 0, 911, $wrk_state_assoc_id, '"* Total Drills Company (adjusted max 2/month)"', $arraycompany[1]);
          $this->write_data($this->wrk_pointyear, 0, 0, 912, $wrk_state_assoc_id, '"* Total Company"', $arraycompany[2]);
        }
        $this->write_data($this->wrk_pointyear, $wrk_member_nbr, $wrk_line_nbr, 950, $wrk_state_assoc_id, '"* Percentage Credited "', $arraycalc);
      }
    }
    $this->close_db_connection();
  }



  function write_data($arg2_pointyear, $arg2_membernbr, $arg2_linenbr, $arg2_sheetcode, $arg2_state_assoc_id, $arg2_description, $arg2_array){

    $runthis = 'INSERT INTO tmp_roster_by_stateassocmonth (`tmp_pointyear`, `tmp_Member_number`, `tmp_Line_number`, `tmp_sheet_code`, `tmp_state_assoc_id`, `tmp_description`, `tmp_month1`, `tmp_month2`, `tmp_month3`, `tmp_month4`, `tmp_month5`, `tmp_month6`, `tmp_month7`, `tmp_month8`, `tmp_month9`, `tmp_month10`, `tmp_month11`, `tmp_month12`) VALUES ('.$arg2_pointyear.','.$arg2_membernbr.','.$arg2_linenbr.','.$arg2_sheetcode.','.$arg2_state_assoc_id.','.$arg2_description.','.$arg2_array[0].','.$arg2_array[1].','.$arg2_array[2].','.$arg2_array[3].','.$arg2_array[4].','.$arg2_array[5].','.$arg2_array[6].','.$arg2_array[7].','.$arg2_array[8].','.$arg2_array[9].','.$arg2_array[10].','.$arg2_array[11].');';

    $this->db_connection->query($runthis);

  }



  function retrieve_detail_rows($arg_member_number){
    // NOTE: the where clause uses a NOT IN as opposed to IN. In this way, it excludes total rows by negation.
    // See "retrieve_summary_rows" function below
    $runthis = "SELECT tmp_pointyear, tmp_Member_number, tmp_Line_number, tmp_sheet_code, tmp_state_assoc_id, tmp_description, tmp_month1, tmp_month2, tmp_month3, tmp_month4, tmp_month5, tmp_month6, tmp_month7, tmp_month8, tmp_month9, tmp_month10, tmp_month11, tmp_month12 FROM tmp_roster_by_stateassocmonth where tmp_Member_number = ".$arg_member_number." and tmp_sheet_code not in (910, 911, 912);";

    $result = $this->db_connection->query($runthis);
    return $result;
  }



  function ClothingAllowance_calculation($arg_status_overall, $arg_percent){
    // Retrieve standard percentages for Exempt and non-Exempt
    // 21=exempt, 22=non-exempt
    if($arg_percent >= $this->exempt_percent and ($arg_status_overall == 'E' or $arg_status_overall == 'L')) {
      $clothing_allowance = 'YES';
    }
    elseif($arg_percent >= $this->nonexempt_percent and ($arg_status_overall <> 'E' and $arg_status_overall <> 'L')) {
      $clothing_allowance = 'YES';
    }
    else {
      $clothing_allowance = '';
    }
    return $clothing_allowance;
  }



  function open_db_connection(){
    $this->db_connection = new mysqli($this->db_info['server'], $this->db_info['username'], $this->db_info['password'], $this->db_info['database']);
    if ($this->db_connection->connect_error) {
      die("Connection failed: " . $this->db_connection->connect_error);
    }
  }



  function close_db_connection(){
    $this->db_connection->close();
  }



}
// End of class definition


/*-------------------------------------------------------*/
/*   mainline of program
/*   this is used for testing only. Programs/modules
/*   should instantiate the class and use the functions
/*   within the class.
/*-------------------------------------------------------*/

/*
$wrk_cls_SAC_calculations = new cls_SAC_calculations('/home/ESIS/htvfd_init/db.json', '/home/ESIS/htvfd_init/options.json');
$wrk_cls_SAC_calculations->load_details();
var_dump($wrk_cls_SAC_calculations);
$wrk_cls_SAC_calculations->close_db_connection();
*/

?>
