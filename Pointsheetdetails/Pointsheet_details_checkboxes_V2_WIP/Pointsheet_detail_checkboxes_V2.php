<?php

// Point Sheet Detail entry via text box and check boxes.
//
// Original coding by Marty Osterhoudt for Hardyston Twp. (NJ) Volunteer Fire Department
// as part of its Point Sheet Entry system.
//
// The HTML for this form was originally designed using Pinegrow and utilized Bootstrap 5 for formatting and CSS.
// The PHP code here utilizes "echo" functions and wraps around the HTML and CSS created from Pinegrow.
//
// The Point Sheet system is written using Scriptcase. This program is called from the main Point Sheet Entry program
// that was created using Scriptcase.
//

class cls_pointsheet_detail_checkboxes{
  public $pointsheetnbr;
  public $call_descr = '*** Pointsheet cookie not set, or invalid pointsheet number!';
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
    // The Point sheet number is obtained from cookie value. This was set in main Pointsheet Entry screens.
    // Scriptcase has a convulated method of passing arguments to external applications using the sc_redir function.
    if(!isset($_COOKIE[$this->cookie_pointsheetnbr])) {
      echo "Cookie named '" . $this->cookie_pointsheetnbr . "' is not set!";
      die("Cookie named '" . $this->cookie_pointsheetnbr . "' is not set!");
    } else {
      $this->pointsheetnbr =  $_COOKIE[$this->cookie_pointsheetnbr];
    }

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

    // Obtain Point Sheet header information based on cookie data.
    $sql = 'SELECT * FROM Pointsheet where ID_Pointsheet = '.$this->pointsheetnbr.';';
    $this->db_pointsheet = $this->fct_sql_query($sql);
    // Using foreach, but there should be one row.
    foreach($this->db_pointsheet as $PSH){
      $this->pointyear = $PSH['Point_Year'];
      $this->call_descr = $PSH['Comments'];
      $this->starting_date = $PSH['Starting_date'];
      $this->ending_date = $PSH['Ending_date'];
    }

    // fct_load_PSD is called here (to initially load the display) and after a database update (see mainline code section).
    $this->fct_load_PSD();

    // NOTE: Active and Inactive members are based on the starting date of the pointsheet header, NOT today's date.
    // Retrieve Active members from Roster table into dataset.
    $sql = 'SELECT * FROM Roster R join Roster_In_Service RIS on R.member_nbr = RIS.Member_nbr where In_Service_Status in ("A") and "'.$this->starting_date.'" between Date_In and Date_Out order by R.Line_number;';
    $this->roster_active = $this->fct_sql_query($sql);

    // Retrieve Inctive members from Roster table into dataset.
    $sql = 'SELECT * FROM Roster R join Roster_In_Service RIS on R.member_nbr = RIS.Member_nbr where In_Service_Status not in ("A", "I", "R") and "'.$this->starting_date.'" between Date_In and Date_Out order by R.Line_number;';
    $this->roster_inactive = $this->fct_sql_query($sql);

  }


  function __destruct(){
    // Close the DB connection.
    $this->connection->close();
  }


  // This function will load the most recent Point Sheet details into a dataset (called before loading screen and after a database update).
  function fct_load_PSD(){
    $sql = 'SELECT * FROM Pointsheetdtl where ID_Pointsheetdtl = '.$this->pointsheetnbr.';';
    $this->db_pointsheetdetail = $this->fct_sql_query($sql);
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


  // This will loop through either the active or inactive roster data -- depending on the dataset
  // passed in as the argument -- and call the function to write HTML for each member.
  function fct_read_members($arg_dataset, $arg_activeinactive){
    try{
      if ($arg_dataset->num_rows > 0) {
        while($row = $arg_dataset->fetch_assoc()) {
          // after retrieving the roster row, determine if a pointsheet detail exists.
          // The returned value will be either true or false and used for the line number checkbox.
          $psd_found = $this->fct_read_psdetail_fromDS($row['Line_number']);
          $name = $row['last_name'].', '.$row['first_name'];
          // If the inactive dataset was passed in, tack on the status code at the end of the members name.
          if($arg_activeinactive == 'inactive'){
            $name .= ' ('.$row['In_Service_Status'].')';
          }
          // The following function will write the HTML to the screen.
          $this->fct_html_member($row['Line_number'], $name, $arg_activeinactive, $psd_found);
        }
      }
    }
    catch(exception  $error){
      echo 'Error within try/catch: '.$error;
    }
  }


  // Function to read the Pointsheet detail dataset. Must loop through the dataset to
  // find the and compare the line numbers.
  function fct_read_psdetail_fromDS($arg_linenbr){
    // Reset the dataset pointer to the first row.
    mysqli_data_seek($this->db_pointsheetdetail, 0);
    foreach($this->db_pointsheetdetail as $PSD_row){
      // If a PS Detail row is found that matches a roster line number, set the flag and break out of foreach loop.
      if($PSD_row['Line_nbr'] == $arg_linenbr){
        return true;
        break;
      }
    }
    return false;
  }


  // Write HTML header info, including the checkbox that can select all members.
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
    <!-- This template was modified to include a 3 column newspaper style -->
    <link href="grid.css" rel="stylesheet">
    </head>
    <body class="py-4">
    <div class="container">
    <form method="post">
    <div id="html_heading">
    <h1>HTVFD Point System - Point Sheet Details</h1>
    <h2>Add/Update/Delete Line Numbers</h2>
    <h4>Pointsheet: '.$this->pointsheetnbr.' - '.$this->call_descr.'</h4>
    <h5>Add members by entering individual line numbers, `Select all` checkbox, or click on individual checkboxes.</h5>
    <h5>Once all members are selected, hit `Enter` or `Submit` button</h5>
    <hr class="my-4">
    </div>
    <div id="html_members_single_linenumber">
    Enter a member line number and hit `tab` key:&nbsp;
    <input type="number" name="forminput_individual" class="form_check-input" id="forminput_individual" autofocus onkeydown="fct_js_keyevent(event)" >
    <!--  The following text box will display the line number entered after the tab key is pressed. It also sets the focus back to the entry textbox. -->
    <input type="text" size="50" name="form_line_number_confirmation" id="form_line_number_confirmation" onfocus="fct_js_refocus()">
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
  // It will also set the checkbox based on the $arg_psd_found true/false value.
  function fct_html_member($arg_linenbr, $arg_member_name, $arg_activeinactive, $arg_psd_found){
    // $arg_psd_found determines if a pointsheet detail was found for a member.
    if($arg_psd_found == true){
      $wrk_checkbox = 'checked';  // HTML line number checkbox will be selected/checked.
    }
    else{
      $wrk_checkbox = '';
    }
    echo '
    <div class="col">
    <div class="form-check">
    <input type="checkbox" name="'.$arg_activeinactive.'membercheckbox-'.$arg_linenbr.'" class="form-check-input" id="formInput'.$arg_linenbr.'" '.$wrk_checkbox.' onclick="fct_js_checkbox_members()">
    <label class="form-check-label" id="formcheckboxLabel'.$arg_linenbr.'" for="formInput'.$arg_linenbr.'">'.$arg_linenbr.' - '.$arg_member_name.'</label>
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
    // Since there are two roster datasets, determine which one to use
    // based on the argument passed in.
    if($arg_dataset == 'active'){
      $wrk_dataset = $this->roster_active;
    }
    elseif($arg_dataset == 'inactive'){
      $wrk_dataset = $this->roster_inactive;
    }

    // Loop thru the active or inactive roster datasets, depending on the argument.
    foreach($wrk_dataset as $roster_row){
      // $_POST determines the checlkbox is checked, this is used by the truth table below.
      if(isset($_POST['activemembercheckbox-'.$roster_row['Line_number']]) or isset($_POST['inactivemembercheckbox-'.$roster_row['Line_number']])){
        $checkbox_on = true;
      }
      else {
        $checkbox_on = false;
      }

      // For each roster row just read, determine if a pointsheet detail row exists.
      // This is used by the truth table below.
      $wrk_found_PSDdetail = $this->fct_read_psdetail_fromDS($roster_row['Line_number']);

      // -----------------------------------------------------------------------------------------------------------------------------------
      // Create a truth table (of sorts) to determine database operations.
      // This compares whether the member checkbox is selected versus whether a pointsheet detail row exists for the member.
      //  checkbox selected = false   row exists = false   action = do nothing.
      //  checkbox selected = false   row exists = true    action = delete row.
      //  checkbox selected = true    row exists = false   action = insert row.
      //  checkbox selected = true    row exists = true    action = do nothing.
      // -----------------------------------------------------------------------------------------------------------------------------------
      if($checkbox_on == false and $wrk_found_PSDdetail == true){
        $sql = 'DELETE FROM `Pointsheetdtl` WHERE (ID_Pointsheetdtl = '.$this->pointsheetnbr.' and Line_nbr = '.$roster_row['Line_number'].');';
        $this->fct_sql_query($sql);
      }
      elseif($checkbox_on == true and $wrk_found_PSDdetail == false){
        $sql = 'INSERT INTO `Pointsheetdtl` (`ID_Pointsheetdtl`, `Line_nbr`, `Member_nbr`, `dtl_Point_year`, `Pointsheetdtl_reconciled`) VALUES ('.$this->pointsheetnbr.', '.$roster_row["Line_number"].', '.$roster_row["member_nbr"].', '.$this->pointyear.', 1);';
        $this->fct_sql_query($sql);
      }

    }
    // *** reposition the row pointers for each dataset.
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

// NOTE: The close button is handled in Javascript "pointsheet_detail_checkboxes.js".
if($_SERVER["REQUEST_METHOD"] == 'POST' and isset($_POST["button_submit"])){
  $wrk_class->fct_update_dbdata('active');
  $wrk_class->fct_update_dbdata('inactive');
  $wrk_class->fct_load_PSD();   // the initial data loaded by __construct must be re-read after the udpate.
}


$wrk_class->fct_html_header();
$wrk_class->fct_read_members($wrk_class->roster_active, 'active');
$wrk_class->fct_html_nonactive_header();
$wrk_class->fct_read_members($wrk_class->roster_inactive, 'inactive');
$wrk_class->fct_html_footer();


?>
