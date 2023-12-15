// Javascript functions for the "point sheet detail by checkboxes" screen

// In the HTML, checkboxes have specific names, classess, and IDs.
// name="activemembercheckbox-11"  or  "inactivemembercheckbox-999"
// class="form-check-input"
// id="formInput11

// Labels for the checkboxes also have specific names.
// class="form-check-label"
// id="formcheckboxLabel11'
// for="formInput11'  to match the checkbox ID.


// IF the "Select All" checkbox is selected, check all active members' checkboxes,
// but ignore the "all others" checkboxes (can be selected manually if needed).
function fct_js_select_active_members(){
  // Check if "select all" checkbox is selected.
  if(document.getElementById("formInputSelectall").checked == true){
    // Get total number of individual member's checkboxes.
    let var_active_members_entries = document.getElementsByClassName("form-check-input").length;
    // Loop through all members' checkboxes,then determine the "active" members to select.
    // Remember that the index is the position within the checkboxes, not a member's line number.
    for (let i = 1; i < var_active_members_entries; i++){
      let wrk_elements = document.getElementsByClassName("form-check-input")[i];
      // if the class name begins with "active" set the checkbox (skip the inactive members).
      if(wrk_elements.name.startsWith("active") == true){
        document.getElementsByClassName("form-check-input")[i].checked = true;
      }
    }
  }
}


// If a member checkbox is de-selected, de-select the "Select All" checkbox; It does not matter if the member is active or inactive.
// If a member checkbox is selected, this JS function is not applicable... but it is used by the HTML update database function.
function fct_js_checkbox_members(){
  // Get total number of ALL individual member's checkboxes.
  let var_active_members_entries = document.getElementsByClassName("form-check-input").length;
  // Loop through all members' checkboxes,then determine the "active" members to select.
  for (let i = 0; i < var_active_members_entries; i++){
    // If at least one member's checkbox is not selected, deselect the "select all" checkbox and exit loop.
    if(document.getElementsByClassName("form-check-input")[i].checked == false){
      document.getElementById("formInputSelectall").checked = false;
      break;
    }
  }
}


// This is used for the "onkeydown" HTML event for the line number entry text box.
// Check for a keypress, but only complete if tab key is pressed after entering an
// individual line number.
// The line number text (number) box should receive focus after the confirmation text box is updated. (see fct_js_refocus)
function fct_js_keyevent(event){
  let keypressed = event.key;
  console.log(keypressed);
  // If the "tab" key was pressed while in the individual line number entry box,
  // read the value in the number box and check the corresponding checkbox.
  if(keypressed == 'Tab'){
    // Retrieve the value of the line number from the text box.
    let inp_linenumber = document.getElementById("forminput_individual").value;
    // Set the work field to grab the correct ID and set the checkbox to True.
    let wrk_linenbr = 'formInput' + inp_linenumber;
    document.getElementById(wrk_linenbr).checked = true;
    // Retrieve the checkbox label and set the "confirmation" text box that value.
    let wrk_checkbox_label = "formcheckboxLabel" + inp_linenumber;
    let wrk_last_updated = document.getElementById(wrk_checkbox_label).innerHTML;
    document.getElementById("form_line_number_confirmation").value = "Last entered: " + wrk_last_updated;

    //fct_js_refocus();
  }
}


// Reposition focus and cursor to Line number number box.
function fct_js_refocus(){
  document.getElementById("forminput_individual").focus();
  document.getElementById("forminput_individual").value = '';
  document.getElementById("forminput_individual").select();
}


// Close form.
function fct_js_closeform(){
  window.close();
}
