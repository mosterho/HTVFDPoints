<?php

class cls_pointyear_display
{
    public $point_year = 0;
    public $wrk_cls_pointyear_validation;

    function __construct()
    {
        // include pointyear validation code, instantiate class, and retrieve
        // and update the cookie and, if needed, the options JSON file.
        $file = 'pointyear_validation.php';
        include $file;
        $this->wrk_cls_pointyear_validation = new cls_calendar_validation();
        $this->point_year = $this->wrk_cls_pointyear_validation->point_year;
    }
}

// End of class definition

/*-------------------------------------------------------*/
/*   mainline of program
/*   Programs/modules should instantiate the class 
/*   and use the functions within the class.
/*-------------------------------------------------------*/

$wrk_cls_pointyear_display = new cls_pointyear_display;
$arg_year = $wrk_cls_pointyear_display->point_year;

// If submit button was hit, save point year via pointyear validation module.
//if ($_SERVER["REQUEST_METHOD"] == "POST" and (isset($_POST['btn-submit']) == "submit")) {
if ($_SERVER["REQUEST_METHOD"] == "POST" and (isset($_POST['btn-submit']))) {
    $arg_year = $_POST['forminputyear'];
    $wrk_cls_pointyear_display->wrk_cls_pointyear_validation->fct_update_pointyear($arg_year);
}

/*----------------------------------------------------------------------------------------------------------*/
// As much as I would rather put the following in a function, it won't work properly unless 
// the HTML follows the cookie updates and is the last code in this script.

// Determine the maximum value the point year can be, based on today's date in the pointyear validation module.
$max_year = $wrk_cls_pointyear_display->wrk_cls_pointyear_validation->fct_determine_pointyear();

echo
'
        <!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="Update " content="Update Point Year for Reports">
        <meta name="author" content="Marty Osterhoudt for HTVFD">
        <title>Update Point Year for Reports</title>
        <!-- Bootstrap core CSS -->
        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <!-- Custom styles for this template -->
        <link href="style.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <form method="post">
                <h2>Update Point Year for Reports</h2>
                <hr class="hr">
                <div id="div_id_inputpointyear">
                    <label class="form-check-label" for="forminputyear">Point Year:&nbsp;</label>
                    <input type="number" min="2000" max="' . $max_year . '" id="forminputyear" name="forminputyear" autofocus value="' . $arg_year . '">
                </div>
                <hr class="hr">
                <div class="btn-group" role="group" aria-label="Basic example"> 
                    <button type="submit" class="btn btn-secondary" id="btn-submit" name="btn-submit" value="submit">Submit</button>                     
                    <button type="reset" class="btn btn-secondary">Reset</button>                     
                    <button type="button" class="btn btn-secondary" id="btn-close" name="btn-button" value="Close" onclick="fct_js_closeform()">Close</button>                     
                </div>                 
            </form>
        </div>
        <!-- Bootstrap core JavaScript
    ================================================== -->
        <!-- Placed at the end of the document so the pages load faster -->
        <!--
        <script src="assets/js/popper.min.js"></script>
        <script src="bootstrap/js/bootstrap.min.js"></script>
        -->
        <script src="Pointyear.js"></script>
    </body>
</html>
        ';

?>