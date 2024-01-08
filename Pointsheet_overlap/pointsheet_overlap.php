<?PHP

// This module will display point sheets that overlap/intersect with another point sheet.
// The overlapping point sheets were determined using the pointhsheet_overlap_search.php program.

class cls_display_ps_overlap {
  public $point_year = 0;
  public $wrk_class_PSO;

  function __construct() {
    $this->point_year = $_COOKIE['pointsystem_defaultyear'];
    $include = "pointsheet_overlap_search_V2.php";
    include $include;
    $this->wrk_class_PSO = new cls_pointsheet_overlap();
    $this->wrk_class_PSO->fct_compare_PS();
  }


  function __destruct(){
    //
  }


  function fct_display_header(){
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="This page will display any point sheets where the starting and ending dates/times overlap. This is to help determine if a member on department business should be added to any department-level point sheets.">
    <meta name="author" content="Marty Osterhoudt for Hardyston (NJ) Fire Department">
    <title>Point Sheet Overlap</title>
    <!-- Bootstrap core CSS -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="style.css" rel="stylesheet">
    </head>
    <body>
    <div class="container">
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <form>
    <h2>Point Sheet Overlap</h2>
    <h3>Determine if any point sheets overlap and member(s) should receive credit</h3>
    <h4>Point Year: '.$this->point_year.'</h4>
    ';
  }


  // This function will read the Point Sheet file and extract any point sheets where there is
  // a potential overlap of starting and ending times.
  // At least one point sheet will be a department-level point sheet (fire, drill, etc.).
  function fct_read_PS(){
    $wrk_counter = 0;
    // Read Point Sheet array.
    // Using foreach, read each row in the dataset.
    foreach($this->wrk_class_PSO->array_alldata as $key=>$value){
      echo '
      <div class="border-start border-top border-end border-bottom">
      <table class="table">
      <thead>
      <tr class="table-info table-row">
      <th>Point Sheet#</th>
      <th>Starting Date</th>
      <th>Ending Date</th>
      <th>Comments</th>
      </tr>
      </thead>
      <tbody>';

      // for debugging only
      /*
      echo '<br>';
      var_dump($value);
      echo '<br>';
      $temp_json = json_encode($value);
      var_dump($temp_json);
      echo '<br>';
      */

      // Loop through pointsheet header info, display each point sheet as a table row.
      foreach($value as $rowkey=>$rowvalue){
        if(is_int($rowkey)){
          echo '<tr class="table-row">
          <td>'.$rowvalue['Pointsheet_number'].'</td>
          <td>'.$rowvalue['starting_date'].'</td>
          <td>'.$rowvalue['ending_date'].'</td>
          <td>'.$rowvalue['comments'].'</td>
          </tr>
          ';
        }
      }
      echo '</tbody>
      </table>
      ';

      // start of new table, but for point sheet line numbers.
      echo '<table class="table">';
      echo '<tbody>';
      // Re-read the pointsheet arrays, but now also loop through the merged line numbers and within that loop,
      //  read the line numbers associated with the point sheet. This will align the line numbers for the HTML page.
      foreach($value as $rowkey=>$rowvalue){
        if(is_int($rowkey)){
          echo '<tr class="table-light">';
          echo '<td>Line Numbers on pointsheet '.$rowvalue['Pointsheet_number'].'</td>';
          // Read the merged_linenumbers array, but test if the merged line number is in the point sheet.
          foreach($value['merged_linenumbers'] as $idx){
            if(in_array($idx, $value[$rowkey]['line_numbers'])) {
              echo '<td>'.$idx.'</td>';
            }
            else{
              echo '<td> </td>';
            }
          }
          echo '</tr>';
        }
      }
      echo '</tbody>';
      echo '</table>';
      echo '</div>';
      echo '<br>';

      $wrk_counter += 1;
    }
    echo '<br>Total number of potential mismatched line numbers: '.$wrk_counter;
  }


  // Display the HTML footer info.
  function fct_display_footer(){
    echo '
    </form>
    <script src="assets/js/popper.min.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
    </div>
    </body>
    </html>
    ';
  }
}
// End of class definition

//-----------------------------------------------------------------------
//   mainline
//-----------------------------------------------------------------------

// Instantiaite the report's class. Within the __construct, this will instantiate the
// "pointsheet_overlap_search_V2.php" class, which in turn will read the point sheet tables and
// load arrays to be read by this program to produce the overlap report.
$wrk_thisclass = new cls_display_ps_overlap();
$wrk_thisclass->fct_display_header();
$wrk_thisclass->fct_read_PS();
$wrk_thisclass->fct_display_footer();

?>
