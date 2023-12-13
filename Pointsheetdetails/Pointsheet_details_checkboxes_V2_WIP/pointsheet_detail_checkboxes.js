// Javascript functions for the "point sheet detail by checkboxes" screen

// IF the "Select All" checkbox is selected, check all active members' checkboxes,
// but ignore the "all others" checkboxes (can be selected manually if needed).
function fct_js_select_active_members(){
  if(document.getElementById("formInputSelectall").checked == true){
    let var_active_members_entries = document.getElementsByClassName("form-check-input").length;
    for (let i = 1; i < var_active_members_entries; i++){
      let wrk_elements = document.getElementsByClassName("form-check-input")[i];
      //console.log(wrk_elements);
      // if the class name begins with "active" set the checkbox (skip the inactive members).
      if(wrk_elements.name.startsWith("active") == true){
        document.getElementsByClassName("form-check-input")[i].checked = true;
      }
    }
  }
}


// If an individual checkbox is de-selected, de-select the "Select All" checkbox.
function fct_js_deselect_active_members(){
  let var_active_members_entries = document.getElementsByClassName("form-check-input").length;
  for (let i = 0; i < var_active_members_entries; i++){
    if(document.getElementsByClassName("form-check-input")[i].checked == false){
      //console.log(document.getElementById("formInputSelectall"));
      document.getElementById("formInputSelectall").checked = false;
      break;
    }
  }
}


// check for keypress, but only complete if tab key is pressed for
// individual line number entered.
function fct_js_keyevent(event){
  console.log(event);
  let keypressed = event.key;
  console.log(keypressed);
  // If the "tab" key was pressed while in the individual line number entry box,
  // Read the value in the number box and check the corresoinding checkbox.
  if(keypressed == 'Tab'){
    let a = document.getElementById("forminput_individual").value;
    console.log(a);
    let wrk_linenbr = 'formInput' + a;
    console.log(wrk_linenbr);
    document.getElementById(wrk_linenbr).checked = true;
    let wrk_checkbox_label = "formcheckboxLabel" + a;
    let wrk_last_updated = document.getElementById(wrk_checkbox_label).innerHTML;
    console.log(wrk_last_updated);
    document.getElementById("form_line_number_confirmation").value = "Last entered: " + wrk_last_updated;
    //fct_js_refocus();
  }
}


// Try to reposition focus and cursor to Line number number box.
function fct_js_refocus(){
  document.getElementById("forminput_individual").focus();
  document.getElementById("forminput_individual").value = '';
  document.getElementById("forminput_individual").select();
  console.log("Trying to reset focus...");
}


// Close form.
function fct_js_closeform(){
  window.close();
  console.log('Close button hit...')
}
