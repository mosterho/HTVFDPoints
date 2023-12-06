<?php

class cls_pointsheet_detail_checkboxes{
  public $pointsheetnbr;
  public $call_descr;
  public $pointyear;
  public $starting_date;
  public $ending_date;
  public $db_info;
  public $connection;
  public $roster_active;
  public $roster_inactive;
  public $db_pointsheet;
  public $db_pointsheetdetail;
  public $cookie_pointsheetnbr = 'pointsheet_number';

  function __construct(){
    if(!isset($_COOKIE[$this->cookie_pointsheetnbr])) {
      echo "Cookie named '" . $this->cookie_pointsheetnbr . "' is not set!";
    } else {
      //echo "Cookie '" . $this->cookie_pointsheetnbr . "' is set!<br>";
      //echo "Value is: " . $_COOKIE[$this->cookie_pointsheetnbr];
      $this->pointsheetnbr =  $_COOKIE[$this->cookie_pointsheetnbr];
    }

    //$this->call_descr = $arg_call_descr;
    //$this->pointyear = $arg_pointyear;
    $dbfileinfo = '/home/ESIS/dataonly/htvfd_init/db.json';
    $myfile = file_get_contents($dbfileinfo);
    $this->db_info = json_decode($myfile, true);  // true creates an associative array.
    $this->connection = new mysqli($this->db_info['server'], $this->db_info['username'], $this->db_info['password'], $this->db_info['database']);
    if ($this->connection->connect_error) {
      die("Connection failed: " . $this->connection->connect_error);
    }

    $sql = 'SELECT * FROM Pointsheet where ID_Pointsheet = '.$this->pointsheetnbr.';';
    $this->db_pointsheet = $this->fct_sql_query($sql);
    foreach($this->db_pointsheet as $PSH){
      $this->pointyear = $PSH['Point_Year'];
      $this->call_descr = $PSH['Comments'];
      $this->starting_date = $PSH['Starting_date'];
      $this->ending_date = $PSH['Ending_date'];
    }

    $sql = 'SELECT * FROM Pointsheetdtl where ID_Pointsheetdtl = '.$this->pointsheetnbr.';';
    $this->db_pointsheetdetail = $this->fct_sql_query($sql);

    //$sql = 'SELECT * FROM Roster R inner join TBL_Validation TVAL on R.`Status` = TVAL.idTBL_Validation where TBL_Validation_chargecopoints = true and idTBL_Validation = "A" order by Line_number;';
    $sql = 'SELECT * FROM Roster R join Roster_In_Service RIS on R.member_nbr = RIS.Member_nbr where In_Service_Status in ("A") and "'.$this->starting_date.'" between Date_In and Date_Out order by R.Line_number;';
    $this->roster_active = $this->fct_sql_query($sql);

    //$sql = 'SELECT * FROM Roster R inner join TBL_Validation TVAL on R.`Status` = TVAL.idTBL_Validation where TBL_Validation_chargecopoints = true and idTBL_Validation <> "A" order by Line_number;';
    $sql = 'SELECT * FROM Roster R join Roster_In_Service RIS on R.member_nbr = RIS.Member_nbr where In_Service_Status not in ("A", "I", "R") and "'.$this->starting_date.'" between Date_In and Date_Out order by R.Line_number;';
    $this->roster_inactive = $this->fct_sql_query($sql);

  }


  function __destruct(){
    $this->connection->close();
  }


  function fct_sql_query($arg_sql){
    $result = $this->connection->query($arg_sql);
    if(!isset($result)){
      echo '<p>';
      die($arg_sql.' sql statement did not reult in an equivalent variable');
    }
    else {
      return $result;
    }
  }


  function fct_read_members($arg_dataset, $arg_activeinactive){
    try{
      if ($arg_dataset->num_rows > 0) {
        while($row = $arg_dataset->fetch_assoc()) {
          $psd_found = $this->fct_read_psdetail($row['Line_number']);
          $name = $row['last_name'].', '.$row['first_name'];
          if($arg_activeinactive == 'inactive'){
            $name .= ' ('.$row['In_Service_Status'].')';
          }
          $this->fct_html_member($row['Line_number'], $name, $arg_activeinactive, $psd_found);
        }
      }
      mysqli_data_seek($arg_dataset,0);
    }
    catch(exception  $error){
      echo 'Error within try/catch: '.$error;
    }

  }


  function fct_read_psdetail($arg_linenbr){
    $sql = 'SELECT * FROM Pointsheetdtl where ID_Pointsheetdtl = '.$this->pointsheetnbr.' and Line_nbr = '.$arg_linenbr.';';
    $result = $this->connection->query($sql);
    // Pass the literal value so the HTML "checked" is correct.
    if(isset($result)){
      if($result->num_rows > 0){
        return 'checked';
      }
    }
    return '';
  }


  // Write HTML header info, including checkbox for all members.
  function fct_html_header(){
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="HTVFD Point System Update Point Sheet Details via Checkbox">
    <meta name="author" content="Martin Osterhoudt (ESIS)">
    <meta name="generator" content="Hugo 0.79.0">
    <title>HTVFD Point System Update Point Sheet Details via Checkbox</title>
    <!-- Bootstrap core CSS -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="grid.css" rel="stylesheet">
    </head>
    <body class="py-4">
    <div class="container">
    <form method="post">
    <div id="html_heading">
    <h1>HTVFD Point System - Point Sheet Details</h1>
    <h2>Enter line numbers via checkbox</h2>
    <h4>Pointsheet: '.$this->pointsheetnbr.' - '.$this->call_descr.'</h4>
    <hr class="my-4">
    </div>
    <div id="html_body_active">
    <h5>Active Members</h5>
    <div class="form-check">
    <input type="checkbox" name="formInputSelectall" class="form-check-input" id="formInputSelectall" onclick="fct_js_select_active_members()">
    <label class="form-check-label" for="formInputSelectall">Select all Active members</label>
    </div>
    <p></p>
    <div id="active_members">
    <!-- <div class="row row-cols-md-5"> -->
    <div class="newspaper">
    ';
  }


  // Write HTML header for non-active member section.
  function fct_html_nonactive_header(){
    echo '
    </div>
    </div>
    <hr class="my-4">
    </div>
    <div id="html_body_nonactive_members">
    <h5>All Other Members (Medical Leave, etc.)</h5>
    <div class="row row-cols-md-5">
    ';
  }


  // This function will write the member details, for both active and non-active members.
  function fct_html_member($arg_linenbr, $arg_member_name, $arg_activeinactive, $arg_psd_found){
    // $arg_psd_found is a literal "checked" or an empty string.
    echo '
    <div class="col">
    <div class="form-check">
    <input type="checkbox" name="'.$arg_activeinactive.'membercheckbox-'.$arg_linenbr.'" class="form-check-input" id="formInput'.$arg_linenbr.'" '.$arg_psd_found.' onclick="fct_js_deselect_active_members()">
    <label class="form-check-label" for="formInput'.$arg_linenbr.'">'.$arg_linenbr.' - '.$arg_member_name.'</label>
    </div>
    </div>
    ';
  }


  // Write HTML footer information, including buttons.
  function fct_html_footer(){
    echo '
    </div>
    <hr class="my-4">
    </div>
    <div id="html_button_group">
    <div class="btn-group" role="group" aria-label="Basic Button Group">
    <button type="submit" name="button_submit" class="btn btn-secondary" value="submit">Submit</button>
    <button type="reset"  name="button_reset" class="btn btn-secondary">Reset</button>
    <button type="button" name="button_close" class="btn btn-secondary" value="close" onclick="fct_js_closeform()">Close</button>
    </div>
    </div>
    </form>
    </div>
    <!-- <script src="assets/js/popper.min.js"></script> -->
    <!-- <script src="bootstrap/js/bootstrap.min.js"></script>  -->
    <script src="pointsheet_detail_checkboxes.js"></script>
    </body>
    </html>
    ';
  }


  // Update Point Sheet detail file based on checkboxes.
  function fct_update_dbdata($arg_dataset){
    if($arg_dataset == 'active'){
      $wrk_dataset = $this->roster_active;
    }
    elseif($arg_dataset == 'inactive'){
      $wrk_dataset = $this->roster_inactive;
    }

    // Loop thru the active or inactive roster datasets, depending on the argument.
    foreach($wrk_dataset as $roster_row){
      if(isset($_POST['activemembercheckbox-'.$roster_row['Line_number']]) or isset($_POST['inactivemembercheckbox-'.$roster_row['Line_number']])){
        $checkbox_on = true;
      }
      else {
        $checkbox_on = false;
      }

      // Within each roster row just read, now determine if a pointsheetdetail row exists.
      // NOTE: reset the SQL dataset pointer in case it was moved from a prevoius read..., just in case...
      $wrk_found_PSDdetail = false;
      mysqli_data_seek($this->db_pointsheetdetail,0);
      foreach($this->db_pointsheetdetail as $PSD_row){
        // If a PS Detail row is found that matches a roster line number, set the flag and break out of foreach loop.
        if($PSD_row['Line_nbr'] == $roster_row['Line_number']){
          $wrk_found_PSDdetail = true;
          break;
        }
      }

      // Create a truth table (of sorts) to determine database operations.
      //  checkbox selected = false   row exists = false   action = do nothing.
      //  checkbox selected = false   row exists = true    action = delete row.
      //  checkbox selected = true    row exists = false   action = insert row.
      //  checkbox selected = true    row exists = true    action = do nothing.
      if($checkbox_on == false and $wrk_found_PSDdetail == true){
        $sql = 'DELETE FROM `Pointsheetdtl` WHERE (ID_Pointsheetdtl = '.$this->pointsheetnbr.' and Line_nbr = '.$roster_row['Line_number'].');';
        $this->fct_sql_query($sql);
      }
      elseif($checkbox_on == true and $wrk_found_PSDdetail == false){
        $sql = 'INSERT INTO `Pointsheetdtl` (`ID_Pointsheetdtl`, `Line_nbr`, `Member_nbr`, `dtl_Point_year`, `Pointsheetdtl_reconciled`) VALUES ('.$this->pointsheetnbr.', '.$roster_row["Line_number"].', '.$roster_row["member_nbr"].', '.$this->pointyear.', 1);';
        $this->fct_sql_query($sql);
      }

    }
    // *** reposition the row pointers so the fct_read_members function will work.
    mysqli_data_seek($this->roster_active,0);
    mysqli_data_seek($this->roster_inactive,0);
    mysqli_data_seek($this->db_pointsheetdetail,0);
  }

}
// End of class definition


//-----------------------------------------------------------------------
//   mainline
//-----------------------------------------------------------------------

$wrk_class = new cls_pointsheet_detail_checkboxes();

if ($_SERVER["REQUEST_METHOD"] == 'POST' and isset($_POST["button_close"])){
  fct_js_closeform();
}
elseif($_SERVER["REQUEST_METHOD"] == 'POST' and isset($_POST["button_submit"])){
  $wrk_class->fct_update_dbdata('active');
  $wrk_class->fct_update_dbdata('inactive');
}


$wrk_class->fct_html_header();
$wrk_class->fct_read_members($wrk_class->roster_active, 'active');
$wrk_class->fct_html_nonactive_header();
$wrk_class->fct_read_members($wrk_class->roster_inactive, 'inactive');
$wrk_class->fct_html_footer();


?>
