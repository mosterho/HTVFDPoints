// Javascript functions for the "point sheet detail by checkboxes" screen

// IF the "Select All" checkbox is selected, check all active members' checkboxes,
// but ignore the "all others" checkboxes (can be selected manually if needed).
function fct_js_select_active_members(){
  if(document.getElementById("formInputSelectall").checked == true){
    let var_active_members_entries = document.getElementsByClassName("form-check-input").length;
    for (let i = 1; i < var_active_members_entries; i++){
      let wrk_elements = document.getElementsByClassName("form-check-input")[i];
      //console.log(wrk_elements);
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

// Close form.
function fct_js_closeform(){
  window.close();
  //console.log('Close button hit...')
}
