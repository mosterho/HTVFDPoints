<?php

// Program to update the latlong2.json file used by Geolocate subsystem.
// The html was originally created using Pinegrow and Bootstrap.
// This program will load the latlong2.json file to a table-like form.
// It will also load entries from the tracking.log in the error-handler subsystem
// in order to select additional locations to add to the latlong2.json file.
// When reading the entries from the tracking log, the following occurs:
// 1. include the "response" data from the file.
// 2. Determine a square that will include the latitude and longitude of the IP adress
//    based on the values in the "response" value. The box drawn will be 0.2 degress to
//    the northwest and southeast of the original location.
// 3. The entries will then be summarized (since there will be more than one attempt to
//    access the geolocate system. )
// 4. Display the summarized entries after the entries already in the latlong2.json file.
// Once the "Submit" button is hit, this will read the "POST" data submitted and
// determine if and entry was selected or deselected. If selected, the entry will
// be added to the latlong2.json file. if an existing entry is deselected, it will
// removed from the table.

//VERY IMPORTANT PROGRAMMING NOTE: If making changes in DataGit, make sure the code is copied to the correct folder in Scriptcase.
//VERY IMPORTANT PROGRAMMING NOTE: don't forget to use "name" for each variable, "id" is not enough!!!!!!

class cls_geolocate_update_latlong_edit{
  public $wrk_cls_geolocateapi;
  public $log_data;
  public $summarized_IPs;
  public $unique_IPs;
  public $JSON_encoded_file_put;
  public $latlong2_file_put_contents = '/home/ESIS/dataonly/GeoLocate_data/keys/latlong2.json';


  function __construct(){
    try{
      $include_code = '/home/ESIS/GeoLocate/geolocate_API.php';
      if(file_exists($include_code)){
        include $include_code;
        // Only need the new class, the __construct will create the whitelist array.
        $this->wrk_cls_geolocateapi = new cls_geolocateapi;
      }
      else{
        echo 'Within Geolocate_update_latlong_edit.php program, could not find geolocate_API';
      }
    }
    catch(Exception $e){
      echo $e;
    }
  }


  function fct_html_header(){
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Geolocate Update Latitude/Longitude Whitelist">
    <meta name="author" content="Marty Osterhoudt (Emergency Services Industry Software)">
    <title>Geolocate Update Latitude/Longitude Whitelist Maintenance</title>
    <!-- Bootstrap core CSS -->
    <link href="css/theme.css" rel="stylesheet" type="text/css">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template
    <link href="style.css" rel="stylesheet">
    -->
    </head>
    <body>
    <div class="container">
    <div>
    <h2>Geolocate Latitude/Longitude Whitelist Maintenance</h2>
    </div>
    <!-- Use this script as the default action -->
    <form method="post">
    <div class="form-check">
    <div class="row bg-info text-decoration-underline">
    <div class="col-4 text-begin">Description</div>
    <div class="col">Northwest Latitude</div>
    <div class="col">Northwest Longitude</div>
    <div class="col">Southeast Latitude</div>
    <div class="col">Southeast Longitude</div>
    </div>
    </div>
    ';
  }


  function fct_html_details(){
    // Load the existing JSON data first.
    $this->wrk_cls_geolocateapi->fct_load_latlong();
    $wrk_array_whitelist = $this->wrk_cls_geolocateapi->whitelist;
    $wrk_count = 0;
    foreach($wrk_array_whitelist as $index){
      $wrk_count += 1;
      $wrk_attr1 = 'checked';
      $wrk_attr2 = '';  // MAY use this for 'disabled', but will cause issues when reading changes.
      //if($index[5] == True){
      //$wrk_attr2 = 'disabled';
      //}
      $this->fct_html_write_details($wrk_count, $index, $wrk_attr1, $wrk_attr2);
    }

    // Include a little formatting between whitelisted and non-whitelisted entries
    echo '
    <div>
    <p><hr><p>
    </div>
    ';

    // Load any entries from the tracking.log file from the class.
    // Remember: the response variable is a string, but in JSON format.
    // Must use "explode" to break out each line into an array and use an individual element of that array
    // to get the response variable. Sample line from tracking.log as follows:
    /*
    <134> 1 2023-09-16T13:03:42-04:00 htvfdpointsystem.azurewebsites.net htvfdpoints - -  whitelisted:True Response:{"ip":"64.138.230.42","country_code":"US","country_name":"United States of America","region_name":"South Carolina","city_name":"Myrtle Beach","latitude":33.689317,"longitude":-78.887101,"zip_code":"29572","time_zone":"-04:00","asn":"21565","as":"Horry Telephone Cooperative Inc.","is_proxy":false}
    */

    // Load the tracking.log into a variable, setup work variables.
    $this->log_data = $this->wrk_cls_geolocateapi->wrk_cls_error_handler->readmessage(True);
    $pattern4 = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';  // REGEX pattern for 4 octet IP.
    $pattern3 = '/\d{1,3}\.\d{1,3}\.\d{1,3}\./';  // REGEX pattern for 3 octet IP.
    $tmp_array_regex = array();
    $wrk_array_regex_results = array();
    // Read each entry in the log...
    foreach($this->log_data as $temp_string){
      // explode the row into 10 elements based on blanks between fields, the 10th being the response variable.
      $tmp_array = explode(' ', $temp_string, 10);
      // Parse out the 3rd octet of the IP address.
      // $temp_matches holds the result/value.
      $temp_regex_count = preg_match($pattern3, $tmp_array[9], $temp_matches);
      //echo '<br>'.$temp_regex_count.'<br>';
      //var_dump($temp_matches);
      // if found, replace the 4 octet IP with the 3 octet IP in $temp_matches in the entire array element.
      // use array "PUSH" to add to a temporary array.
      if($temp_regex_count > 0){
        $temp_regex_result = preg_replace($pattern4, $temp_matches[0], $tmp_array[9]);
        if($temp_regex_result != ''){
          array_push($wrk_array_regex_results, $temp_regex_result);
        }
      }
    }

    // Create a unique array from the response variable values of each element of the array.
    $this->unique_IPs = array_unique($wrk_array_regex_results);
    //var_dump($this->unique_IPs);

    // Read and load the unique IPs onto the screen.
    // The following is just for documentation -- DO NOT reset the counter.
    // $wrk_count = 0;
    $wrk_attr1 = '';
    foreach($this->unique_IPs as $eachline){
      // remove the "response:" portion of the string, then use json_decode to create an associative array to
      // access various elements (e.g., latitude).
      $replaced_line = str_ireplace('response:', '', $eachline);
      $tmp_assoc_array = json_decode($replaced_line, True);  // "True" will create an associative array.
      // If the IP address is blank, this is probablyt/somehow a LAN address that snuck into the log.
      if($tmp_assoc_array["ip"] != ''){
        $wrk_count += 1;
        $index[0] = 'IP: '.$tmp_assoc_array["ip"].'  Country: '. $tmp_assoc_array["country_name"].'  Region/State: '. $tmp_assoc_array["region_name"].'  City: '.  $tmp_assoc_array["city_name"];
        // calculate a northwest/southeast latitude/longitude "box" for the IP.
        $index[1] = round($tmp_assoc_array['latitude'],1) + 0.2;
        $index[2] = round($tmp_assoc_array['longitude'],1) - 0.2;
        $index[3] = round($tmp_assoc_array['latitude'],1) - 0.2;
        $index[4] = round($tmp_assoc_array['longitude'],1) + 0.2;
        $this->fct_html_write_details($wrk_count, $index, $wrk_attr1, $wrk_attr2);
      }
    }
  }


  function fct_html_write_details($wrk_count, $index, $wrk_attr1, $wrk_attr2){
    // Each input field will have a uniqwue ID and NAME, but will appear as an array.
    // This will make reading the POST entires easier.
    echo '
    <div class="row">
    <div class="col-4">
    <input type="checkbox" id="checkbox['.$wrk_count.']" name="checkbox['.$wrk_count.']" class="form-check-input" '.$wrk_attr1.' '.$wrk_attr2.'></input>
    <input type="text" id="textbox['.$wrk_count.']" name="textbox['.$wrk_count.']" class="form-control-input" '.$wrk_attr2.' size=40 value="'.$index[0].'"></input>
    </div>
    <div class="col">
    <input type="number" id="nwlatitude['.$wrk_count.']" name="nwlatitude['.$wrk_count.']" class="form-control-input" readonly value="'.$index[1].'"></input>
    </div>
    <div class="col">
    <input type="number" id="nwlongitude['.$wrk_count.']" name="nwlongitude['.$wrk_count.']"  class="form-control-input" readonly value="'.$index[2].'"></input>
    </div>
    <div class="col">
    <input type="number" id="selatitude['.$wrk_count.']" name="selatitude['.$wrk_count.']"  class="form-control-input" readonly value="'.$index[3].'"></input>
    </div>
    <div class="col">
    <input type="number" id="selongitude['.$wrk_count.']" name="selongitude['.$wrk_count.']"  class="form-control-input" readonly value="'.$index[4].'"></input>
    </div>
    </div>
    ';
  }


  function fct_html_footer(){
    echo '
    <div class="form-check">
    <!-- input type="button" class="btn btn-primary mt-3" id="btn-submit1" name="btn-submit1" value="Submit"></input>  -->
    <button type="submit" class="btn btn-primary mt-3" id="btn-submit2" name="btn-submit2" value="Submit">Submit</button>
    <!-- input type="button" class="btn btn-primary mt-3" id="btn-reset1" name="btn-reset1" value="Reset"></input> -->
    <button type="reset" class="btn btn-primary mt-3" id="btn-reset2" name="btn-reset2" value="Reset">Reset</button>
    </div>
    </form>
    </div>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="assets/js/popper.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    </body>
    </html>
    ';
  }

}

//-----------------------------------------------------------------------
//   End of class definition.
//-----------------------------------------------------------------------


//-----------------------------------------------------------------------
//   functions for mainline
//-----------------------------------------------------------------------

function fct_mainline_debug(){
  echo '<p>';
  if(isset($_POST['checkbox'])){
    foreach($_POST['checkbox'] as $key=>$value){
      echo '<br>Key: '.$key.'  Value: '.$value;
      echo ' Textbox: '.$_POST['textbox'][$key];
      if(isset($_POST['nwlatitude'][$key])){
        echo ' NW Latitude: '.$_POST['nwlatitude'][$key];
      }
      if(isset($_POST['nwlongitude'][$key])){
        echo ' NW Longitude: '.$_POST['nwlongitude'][$key];
      }
      if(isset($_POST['selatitude'][$key])){
        echo ' SE Latitude: '.$_POST['selatitude'][$key];
      }
      if(isset($_POST['selongitude'][$key])){
        echo ' SE Longitude: '.$_POST['selongitude'][$key];
      }
    }
  }
}


function fct_mainline_update_JSON(){
  //fct_mainline_debug();
  fct_geolocate_edit();
}


function fct_geolocate_edit(){
  // Build the array structures.
  $wrk_array_innerdata = array();
  if(isset($_POST['checkbox'])){
    foreach($_POST['checkbox'] as $key=>$value){
      // Write the innermost portion of the array data (description, latitudes, longitudes) to a temporary array.
      $wrk_array_temp = array($_POST['textbox'][$key], $_POST['nwlatitude'][$key], $_POST['nwlongitude'][$key], $_POST['selatitude'][$key], $_POST['selongitude'][$key]);
      array_push($wrk_array_innerdata, $wrk_array_temp);
    }
  }
  // Test if there are any entries selected. If not, do not clear out the JSON file contents; always keep at least one entry.
  if(count($wrk_array_innerdata) > 0){
    $wrk_array_for_JSON =  array("whitelist_LATLONG"=>$wrk_array_innerdata, "whitelist_verbose"=>false);
    $wrk_json_encode = json_encode($wrk_array_for_JSON, JSON_PRETTY_PRINT);
    // First, clear out the file using a ''. Then write the entire file.
    file_put_contents($this->latlong2_file_put_contents, '');
    file_put_contents($this->latlong2_file_put_contents, $wrk_json_encode);
  }
}


//-----------------------------------------------------------------------
//   mainline
//-----------------------------------------------------------------------

$wrk_class2 = new cls_geolocate_update_latlong_edit;

// Did the user click on the submit button?
if($_SERVER["REQUEST_METHOD"] == 'POST'){
  fct_mainline_update_JSON();
}

$wrk_class2->fct_html_header();
$wrk_class2->fct_html_details();
$wrk_class2->fct_html_footer();


?>
