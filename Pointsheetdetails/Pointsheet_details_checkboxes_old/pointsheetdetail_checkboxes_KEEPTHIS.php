/*
This method is the main calling method from the onExecute event
*/

// Did the user click on the close button?
if($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST['close'])) {
	sc_exit();
	}

// Return basic info from Pointsheet header
$runthissql = 'select Comments, Point_Year from Pointsheet where ID_Pointsheet = '.$arg_pointsheet.';';
sc_lookup(header_resultset, $runthissql);
$wrk_point_year = $header_resultset[0][1];

// Return an array of active members based on the Roster table
$returndataset = load_Roster();
$count = count($returndataset);


// initialize the HTML for the page

echo '
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Updates Pointsheet detail via checkboxes">
        <meta name="author" content="Marty Osterhoudt">
        <title>Update Pointsheet Detail via Checkboxes</title>
        <link href="css/style.css" rel="stylesheet">
        <style>.newspaper, .pg-node-id-1, .pg-main-cs-5, .pg-file-cs-5 { padding: 15px; column-fill: balance; columns: 3; width: 660px; border: 2px solid #000000; }</style>
    </head>
    <body>
        <h2>Assign Line Numbers for Pointsheet: '.$arg_pointsheet.'</h2>
        <h3>'.$header_resultset[0][0].'</h3>
        <div>
            <br>
			<form method="post"
';

/*
	The first time through, the following will not execute;
	This code will run after the "submit" button is pressed.
*/

if($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST['submit'])){
  //
  for($row=0; $row<$count; $row++) {
    $runthis = 'select count(0) from Pointsheetdtl where ID_Pointsheetdtl = '.$arg_pointsheet.' and Member_nbr = '.$returndataset[$row][0].';';
    sc_lookup(resultset, $runthis);

	  // Determine if a linenumber checkbox is checked
    $datasetcheck = 'linenumber'.$returndataset[$row][1];
    if(isset($_POST[$datasetcheck])){
      $checked = True;
	}
    else {
      $checked = False;
    }

	  // Now determine what to do with the line number checked/unchecked against
	  // whether data exists in the pointsheet detail file
	/*-------------------------------------------------------------------------------------------------

	Check the following conditions:
	If a line number is checked, but there is no row in the pointsheet detail, add it to the pointsheetdtl table.
	If a line number is checked and there is a row in the pointsheet detail, ignore it.
	if a line number is not checked and there is no row in the pointsheet detail, ignore it.
	if a line number is not checked, but there is a row in the pointsheet detail, delete it from the pointsheetdtl table.
	*/

    if($checked and $resultset[0][0] == 0){
		$sql = 'insert into `Pointsheetdtl` (`ID_Pointsheetdtl`, `Line_nbr`, `Point_Sheet_nbr`, `Member_nbr`, `dtl_Point_year`) VALUES ("'.$arg_pointsheet.'", "'.$returndataset[$row][1].'", "0", "'.$returndataset[$row][0].'", "'.$wrk_point_year.
			'"); ';
		sc_exec_sql($sql);
    }
    elseif($checked and $resultset[0][0] > 0) {
      // ignore this
    }
    elseif(!$checked and $resultset[0][0] == 0) {
      //ignore this
    }
    elseif(!$checked and $resultset[0][0] > 0) {
		$sql = 'DELETE FROM `Pointsheetdtl` where ID_Pointsheetdtl = '.$arg_pointsheet.' and Member_nbr = '.$returndataset[$row][0].';';
		sc_exec_sql($sql);
    }
  }
}

/*
The following method "initialize_roster_checkboxes" will determine if a row exists in the pointsheet detail and
writes the appropriate HTML (check/uncheck the checkbox as needed)
This section of code should run regardless of which button was clicked ("submit" or "cancel/reset")
*/

echo '<div class="newspaper">';
initialize_roster_checkboxes($arg_pointsheet, $returndataset);
echo '</div>';

// Enbable the submit and cancel buttons

echo '
 <br>
                <div class="buttons" data-pg-collapsed>
                    <input type="submit" name="submit" value="Submit">&nbsp;
                    <input type="submit" name="cancel" value="Cancel/Reset">&nbsp;
                    //<input type="submit" name="close" value="Close">
                </div>
            </form>
        </div>
';

echo('</body></html>');
