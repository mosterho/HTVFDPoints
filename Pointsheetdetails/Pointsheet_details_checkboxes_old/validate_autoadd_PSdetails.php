<?php

//

// COPIED from Scriptcase pointsheet detail Entry

// Rewrite this for regular PHP to be used with Point Sheet detail checkbox screen.

/*
This method will create an array of line numbers
of members who should be automatically added to any
chargeable company point sheet (e.g., fires, drills).

As of 12/15/2021 this applies mainly to
members who are on extended military leave -- these members
will be automatically added to the point sheet details
(please see exec board and bylaws for this allowance).
*/

// 12/3/2022 Added date(Starting_date) to correct issue with inclusion of member on military leasve
$runthis1 = 'SELECT ID_Pointsheet, TBL_master_sheetcode_ID, `TBL_master_clothingallowance(Y,N)`, date(Starting_date) as pointhseetdate  FROM Pointsheet PS join TBL_master_pointcodes_V2 PC on PS.Sheet_ID = PC.TBL_master_sheetcode_ID where ID_Pointsheet = '.[gbl_ID_Pointsheet].';';
sc_select(rs1,$runthis1);

// Regular "echo" of $rs2 doesn't work
//echo '<br>Try printing individual values, number of rows: '.$rs1->_numOfRows.' value of third column: '.$rs1->fields[2].'<br><br>';

if($rs1->_numOfRows > 0 and $rs1->fields[2] == 1){
	// CHANGED Dec 3, 2022: issue of not finding member on military leave. if member's current status is Active, but the starting date of
	// the point sheet falls within the member being on military leave, s/he was not automatically added to the point sheet.
	//$runthis2 = 'SELECT member_nbr, Line_number FROM view_Active_Roster where TBL_Validation_allow_companypoint_autoadd = TRUE; ';
	$runthis2 = "SELECT
    member_nbr, line_number
FROM
    view_Roster_RosterinService RIS
        JOIN
    TBL_Validation TBLV ON RIS.In_Service_Status = TBLV.idTBL_Validation
WHERE '".$rs1->fields[3]."' BETWEEN Date_In AND Date_Out
        AND (TBLV.TBL_Validation_allow_companypoint_autoadd = 1);";
	//echo '<p>$runthis2'.$runthis2;
	sc_select(rs2, $runthis2);
	//echo '<p>$rs2'.'  '.$rs2->_numOfRows;
	if($rs2->_numOfRows > 0){
		while(!$rs2->EOF){
			insert_pointsheetdetail([gbl_ID_Pointsheet], $rs2->fields[0]);
			$rs2->MoveNext();
		}
	$rs2->Close();
	}
}




?>
