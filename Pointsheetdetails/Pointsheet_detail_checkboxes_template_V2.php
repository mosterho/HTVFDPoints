<?php

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
            <form>
                <div id="html_heading">
                    <h1>HTVFD Point System - Point Sheet Details</h1>
                    <h2>Enter line numbers via checkbox</h2>
                    <h4>Pointsheet: 12345 - Some fire at some address</h4>
                    <hr class="my-4">
                    <div id="html_body_active">
                        <h5>Active Members</h5>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="formInputSelectall">
                            <label class="form-check-label" for="formInputSelectall">Select all Active members</label>
                        </div>
                        <p></p>
                        <div id="active_members">
                            <div class="row row-cols-md-5">
  ';

echo '

                                <div class="col">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="formInput11">
                                        <label class="form-check-label" for="formInput11">11-Giannone, J.</label>
                                    </div>
                                </div>
      ';


echo '
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <h5>All Other Members (Medical Leave, etc.)</h5>
                    <div id="nonactive_members">
                        <div class="row">
      ';

      echo '
                            <div class="col">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="formInput26">
                                    <label class="form-check-label" for="formInput26">26-Osterhoudt, M. (med)</label>
                                </div>
                            </div>
            ';


echo '

                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="btn-group" role="group" aria-label="Basic Button Group">
                        <button type="submit" class="btn btn-secondary">Submit</button>
                        <button type="reset" class="btn btn-secondary">Reset</button>
                        <button type="button" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </form>
        </div>
        <script src="assets/js/popper.min.js"></script>
        <script src="bootstrap/js/bootstrap.min.js"></script>
    </body>
</html>

';

?>
