# HTVFDPoints

This module contains all of the custom calculations and web pages for the Hardyston Township Volunteer Fire Department (HTVFD).

The sections are:
1. Geolocate Update;
2. LOSAP/NJSFA calculations;
3. Scriptcase custom code;
4. Point Sheet Details.
5. SQL

The Geolocate update section works with the Geolocate subsystem (link here). This module will display a web page that will update the JSON file containing whitelisted locations. These whitelisted locations are those that are permitted to access the Point System.

The LOSAP/NJSFA calculations contain custom code for calculating LOSAP and NJ State Firemens' Association points and percentages.

The Scriptcase custom code contains the PHP script that is embedded in the app_Login event (in the Security folder) of the Scriptcase Point System project. This code checks the Geolocate subsystem to determine if the login screen should appear or if the user gets an HTTP 403 (forbidden) screen.

The Point Sheet Details contains custom webpages that allows entry of members' line numbers on a point sheet. The webpage displays the currently active members via line number. The members can be selected via checkbox.

SQL contains a working copy of a fairly complex SQL statement using Common Table Expression (CTE). This is used in two reports that require the Roster, total company points, total possible company points, members actual points and LOSAP achieved in one report (see Monthly Percentages). Please note that the WHERE clauses contain custom Scriptcase global variables for member status and point year.  Please look for the [] in the WHERE clauses.

NOTE!!!
* The Geolocate Update and LOSAP/NJSFA calculations are called via "include" statements in the PHP code (i.e. this code is working in a special folder within the WWWroot on the application server).
* The Scriptcase custom code (for app_Login) is a working set; the production code is currently in the app_Login module in the Point System project. The code is here in case the app_Login script is overwritten somehow in Scriptcase.
* The Point Sheet Details code is for a working set in test; the production code is currently a module in the same folder as the Point Sheet pages (designed using Scriptcase) within the Point System project.  <ul>IT IS HOPED THAT THIS WILL BE CHANGED SO THAT THE WEBPAGE IS CALLED VIA "INCLUDE" DIRECTLY, SAME AS THE GEOLOCATE AND LOSAP/NJSFA CALCULATIONS.</ul>
* The SQL script is a working set only, but is the basis for a view that is created in MYSQL databases (test and production).
