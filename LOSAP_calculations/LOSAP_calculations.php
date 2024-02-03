<?php

class cls_LOSAP_calculations {
  public $db_info = '';
  public $wrk_pointyear = 0;   // Holds cookie value for point year.

  public $LOSAP_max_fires = 0;
  public $LOSAP_max_drills = 0;
  //  public $LOSAP_max_officer = 0;   // placeholder only,
  public $LOSAP_max_meetings = 0;
  public $LOSAP_max_training = 0;
  public $LOSAP_max_misc = 0;

  public $LOSAP_officer_array;   // array containing officers for a point year.

  public $LOSAP_fire_floor = 0;
  public $LOSAP_creditedfires = 0;
  public $LOSAP_crediteddrills = 0;
  public $LOSAP_officercode = 0;
  public $LOSAP_creditedofficer =  0;  // Officer is flat rate
  public $LOSAP_creditedmeetings = 0;
  public $LOSAP_creditedtraining = 0;
  public $LOSAP_creditedmisc = 0;
  public $LOSAP_credited_total = 0;
  public $LOSAP_achieved_total = 0;


  // the __construct sets up a good portion of the basis of calculating LOSAP.
  // Obtain the current point year, or default to the current year.
  // Establish the DB connection based on argument and JSON file.
  // Determine the maximum/cap values for each LOSAP category.
  // Retrieve the LOSAP roster (officer roster) and the associated points for each officer/member.
  function __construct($arg_dbfileinfo){
    try{
      if(isset($_COOKIE['pointsystem_defaultyear'])){
        $this->wrk_pointyear = $_COOKIE['pointsystem_defaultyear'];
        #echo 'Cookie set!!!!!'.PHP_EOL;
      }
      else{
        $wrk_datetime = (int)(date('Y'));
        $this->wrk_pointyear = $wrk_datetime;
        echo PHP_EOL.'Within LOSAP_calculations.php, the cookie containing point year is not set. Defaulting to: '.$this->wrk_pointyear.PHP_EOL;
      }
      $myfile = file_get_contents($arg_dbfileinfo);
      $this->db_info = json_decode($myfile, true);

      $connection = new mysqli($this->db_info['server'], $this->db_info['username'], $this->db_info['password'], $this->db_info['database']);
      if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
      }

      $runthis = 'SELECT TBL_LOSAP_category_master_code, TBL_LOSAP_category_master_description, TBL_LOSAP_category_master_maxpoints FROM TBL_LOSAP_category_master;';
      $result = $connection->query($runthis);

      if($result->num_rows > 0){
        while($row = $result->fetch_assoc()) {
          switch ($row['TBL_LOSAP_category_master_code']){
            case 1:
            $this->LOSAP_max_fires    = $row['TBL_LOSAP_category_master_maxpoints'];
            break;
            case 2:
            $this->LOSAP_max_drills   = $row['TBL_LOSAP_category_master_maxpoints'];
            break;
            case 3:
            //  [LOSAP_max_officer]	= $dataset[3][3];   // placeholder only,
            break;
            case 4:
            $this->LOSAP_max_meetings = $row['TBL_LOSAP_category_master_maxpoints'];
            break;
            case 5:
            $this->LOSAP_max_training = $row['TBL_LOSAP_category_master_maxpoints'];
            break;
            case 6:
            $this->LOSAP_max_misc     = $row['TBL_LOSAP_category_master_maxpoints'];
            break;
          }
        }
      }

      $runthis = 'SELECT idx_LOSAP_Rostercol, Point_Year, Officer_Code, Member_nbr, Line_nbr, TBL_Officer_Code, TBL_Officer_Desc, TBL_Officer_LOSAP_points
      FROM LOSAP_Roster LR inner join TBL_LOSAP_officer_codes LOC on LR.Officer_Code = LOC.TBL_Officer_Code
      where Point_year = '. $this->wrk_pointyear.' order by LOC.TBL_Officer_LOSAP_points desc, TBL_Officer_Code ;';

      #$connection = new mysqli($this->db_info['server'], $this->db_info['username'], $this->db_info['password'], $this->db_info['database']);
      $result = $connection->query($runthis);
      $this->LOSAP_officer_array = $result->fetch_all(MYSQLI_ASSOC);  // The results will be an associative array.
      #var_dump($this->LOSAP_officer_array);

      $connection->close();
    }

    catch(Exception $ex){
      $code = $ex->getCode();
      $message = $ex->getMessage();
      $file = $ex->getFile();
      $line = $ex->getLine();
      echo "Exception thrown in $file on line $line: [Code $code]  $message";
      echo "BIGLY ERROR in LOSAP_calculations.php in __construct !!!!";
      exit();
      //$LOSAP_array = array(0,0,0,0,0,0,0);
    }
  }


  // Function will accept an argument containing a member's LOSAP earned points, and
  // determine the credited points (determining the lesser of the earned versus maximum/cap allowed).
  function LOSAP_calculation($arg_membernbr, $arg_LOSAP_fires, $arg_LOSAP_fires_total, $arg_LOSAP_drills, $arg_LOSAP_meetings, $arg_LOSAP_training, $arg_LOSAP_misc){
    // For fire percentage, call "fct_alarm_percent_floor" function.
    $this->LOSAP_fire_floor = $this->fct_alarm_percent_floor($arg_LOSAP_fires, $arg_LOSAP_fires_total);
    $this->LOSAP_creditedfires = min($this->LOSAP_max_fires, $this->LOSAP_fire_floor);
    $this->LOSAP_crediteddrills = min($this->LOSAP_max_drills, $arg_LOSAP_drills);
    $this->fct_LOSAP_officer($arg_membernbr);  // Officer is flat rate
    $this->LOSAP_creditedmeetings = min($this->LOSAP_max_meetings, $arg_LOSAP_meetings);
    $this->LOSAP_creditedtraining = min($this->LOSAP_max_training, $arg_LOSAP_training);
    $this->LOSAP_creditedmisc = min($this->LOSAP_max_misc, $arg_LOSAP_misc);
    $this->LOSAP_credited_total = $this->LOSAP_creditedfires + $this->LOSAP_crediteddrills + $this->LOSAP_creditedofficer + $this->LOSAP_creditedmeetings + $this->LOSAP_creditedtraining + $this->LOSAP_creditedmisc;
    $this->LOSAP_achieved_total = $this->LOSAP_fire_floor + $arg_LOSAP_drills + $this->LOSAP_creditedofficer + $arg_LOSAP_meetings + $arg_LOSAP_training + $arg_LOSAP_misc;
  }


  // This function can determine either achieved or credited LOSAP fire percentage.
  function fct_alarm_percent_floor($arg_LOSAP_fires, $arg_LOSAP_fires_total){
    if((is_numeric($arg_LOSAP_fires_total)) and $arg_LOSAP_fires_total != 0){
      $LOSAP_fire_percent = round($arg_LOSAP_fires / $arg_LOSAP_fires_total * 100.0, 1);
      $LOSAP_fire_floor = floor($LOSAP_fire_percent / 10.0) * 10;
      // Adjust calculation for range of 20-39 percent (bug fix 12/31/2022). Previously, 30% = 30 points
      // Based on Hardyston ordinance.
      if($LOSAP_fire_floor == 30){
        $LOSAP_fire_floor = 20;
      }
      // Based on LOSAP ordinance chart, must achieve a minimum of 20% for any credit
      elseif($LOSAP_fire_floor < 20){
        $LOSAP_fire_floor = 0;
      }
    }
    else{
      $LOSAP_fire_floor = 0;
    }
    return $LOSAP_fire_floor;
  }



  // This function will determine the highest LOSAP office a member can hold
  // Note: Hardyston does not allow LOSAP credit for more than one office.
  function fct_LOSAP_officer($arg_membernbr){
    $this->LOSAP_officercode = 0;
    $this->LOSAP_creditedofficer = 0;
    // The LOSAP_officer_array contains an array for each officer for a point Point_year.
    // therefor we must loop through the second array to look for officers and point values.
    foreach($this->LOSAP_officer_array as $key => $value){
      #echo PHP_EOL.'var_dump of $value';
      #var_dump($value);
      if($value['Member_nbr'] == $arg_membernbr){
        if($value['TBL_Officer_LOSAP_points'] > $this->LOSAP_creditedofficer){
          $this->LOSAP_officercode = $value['TBL_Officer_Code'];
          $this->LOSAP_creditedofficer = $value['TBL_Officer_LOSAP_points'];
          #echo PHP_EOL.'Found an office!!!!!!!   member: '.$value['Member_nbr'].'   Office: '.$value['TBL_Officer_Code'].'   Points: '.$wrk_highest_points;
          #echo PHP_EOL;
        }
      }
    }
  }

}

/*-------------------------------------------------------*/
/*   mainline of program
/*   this is used for testing only. Programs/modules
/*   should instantiate the class and use the functions
/*   within the class.
/*-------------------------------------------------------*/

/*
$wrk_class = new cls_LOSAP_calculations('/home/ESIS/htvfd_init/db.json');

$wrk_class->fct_LOSAP_officer(4);  //John G
$wrk_class->LOSAP_calculation(62, 100, 130, $wrk_class->LOSAP_creditedofficer, 100, 260, 987);
var_dump($wrk_class);
*/

?>
