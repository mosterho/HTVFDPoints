# HTVFD Points System
The Hardyston Township Volunteer Fire Department (HTVFD) has a computerized methodology of tracking its members' activities. As shown in the history, there are currently three sets of books for various calculations and reports.

## History
Prior to 1999, the department by-laws described three sets of calculations. The calculations were used to determine the individual members' status and eligibility for clothing allowance, etc. The tracking of the members activities and calculations were written in journal books. Depending on the members activities, he/she could be eligible for clothing allowance, eligible to run for office, or maintaining "member in good standing" status.

The computer-based Point System was placed in production starting in the 2000 point year (December 1999). It was written using MSAccess database and included custom script for various calculations. The denominator was referred to as "chargeable department points". The chargeable department points (based on an old copy of the MSAccess system) are fires, meetings, fund drive, work drills, regular weekly drills (house/engineer/captain), "other" firematic drills, and parades. Activities included in the categories below are based on my recollection alone; I can't find an old copy of the department by-laws:
1. Clothing allowance: This included fires, drills, meetings, and fund raising activities.
2. department Points: This included the Clothing Allowance points above, but also quasi-department level activities (e.g., parades, committees, etc.)
3. Miscellaneous: This includes all categories of work performed.  

In 2000, the department joined the NJ State Firemen's Association (NJSFA). This required a township ordinance that formally recognized that, among other things, the department is accountable to the township administration. The township also enacted the state approved "Length of Service Awards Program" (LOSAP) system for establishing 401k-style retirement accounts. Both of these had their own methods of calculations. So in essence, there were five sets of books for calculating a member's standing and benefits.

In 2014(?), the department approved that all points' categories would now be rolled into the same category as Clothing Allowance. The MSAccess system was changed accordingly to handle the three sets of books. This reduced the number of "books" and calculations from five to three:
1. One standard for calculating the department's clothing allowance and exemption/life membership;
2. the NJSFA calculations for NJ exemption, and;
3. LOSAP for the retirement accounts.

In July(?) 2021, the fire department's laptop computer suffered a hard drive failure. Fortunately there was a backup of the MSAccess system about two months prior. It was decided to create a web-based system that did not rely on software on a laptop. The new web-based system was created using Scriptcase software. The first version of the system had only the basic support tables' maintenance (roster, etc.) and point sheet entry screen. A separate utility was used to convert the MSAccess tables to MYSQL. This initial setup allowed the vice-president to reenter the point sheets, starting with July. Shortly afterward, additional tables to support basic rules and the monthly reports were created in the system. Since then various bug fixes and improvements have been made.

Still, even with cloud backups, the physical system itself was a single point of failure. The web site was running on the life member's large desktop computer. After a category one hurricane in September 2022 and a couple bad storms, it was decided to host the web site on a cloud platform. In September 2023 the system was successfully moved to the MS Azure cloud platform.  

### The transition to Scriptcase browser based system in 2021
The HTVFD Point System is written via [Scriptcase software](https://www.scriptcase.net/). This Rapid Application Development (RAD) software generates web pages via its GUI. The web pages and script generated are in PHP, JavaScript, and HTML. Scriptcase can access several different database systems; the HTVFD Point System utilizes MySQL.

## How custom web pages and code are used with Scriptcase
This HTVFDPoints folder/module contains all of the custom calculations and custom web pages for the department.

The custom code is kept outside of the wwwroot folder structure on the application system. Azure uses NGINX HTTP server to produce the web pages. The default path for the web pages is:
/home/site/wwwroot.
This folder contains the Scriptcase folders that have each application's HTML, Javascript, and PHP.

![Screen cap of folder structure](../Supporting_documents/homesitewwwroot.jpg)

The way to access the custom web pages and code in Scriptcase is through a symbolic link. The symbolic link is /home/site/wwwroot/home_esis_link (the link is visible in the screen cap above). The link points to /home/ESIS/.

While in the "/home/site/wwwroot" directory, run the following:
sudo ln -s /home/ESIS/ home_esis_link

For ease of seeing this link, below is the result of running "ls -lash" while in the /home/site/wwwroot folder on the application server.
4.0K lrwxrwxrwx 1 nobody nogroup   11 Nov 16 17:54 home_esis_link -> /home/ESIS/


### The sections utilizing custom web pages and code
The sections are:
1. Geolocate Update and tracking log;
2. LOSAP/NJSFA calculations;
3. Scriptcase custom code;
4. Point Sheet Details;
5. SQL

#### Custom web pages
The custom web pages are still initialized in Scriptcase. There is minimal code as the initialization points to a folder structure outside of the wwwroot folder structure.

The Geolocate update section herein the HTVFD Points System works with the [Geolocate subsystem](https://github.com/mosterho/GeoLocate). This module will display a web page that will update the JSON file containing whitelisted locations. These whitelisted locations are those that are permitted to access the Point System. The input to the Geolocate system is a combination of the [ip2location.io system](https://www.ip2location.io/) code and a [tracking log](https://github.com/mosterho/errorhandler).

#### Custom code/calculations
The LOSAP/NJSFA calculations contain custom code for calculating LOSAP and NJ State Firemen's Association points and percentages. Please see the [README.md](https://github.com/mosterho/HTVFDPoints/tree/main/LOSAP_calculations).

The Scriptcase custom code contains the PHP script that is embedded in the app_Login event (in the Security folder) of the Scriptcase Point System project. This code checks the Geolocate subsystem to determine if the login screen should appear or if the user gets an HTTP 403 (forbidden) screen.

The Point Sheet Details contains custom webpages that allows entry of members' line numbers on a point sheet. The webpage displays the currently active members via line number. The members can be selected via checkbox.

SQL contains a working copy of a fairly complex SQL statement using Common Table Expression (CTE). This is used in two reports that require the Roster, total department points, total possible department points, members actual points and LOSAP achieved in one report (see Monthly Percentages). Please note that the WHERE clauses contain custom Scriptcase global variables for member status and point year.  Please look for the [] in the WHERE clauses.

## Final Note
* The Geolocate Update and LOSAP/NJSFA calculations are called via "include" statements in the Scriptcase PHP code (i.e. this code is working in a special folder within the wwwroot on the application server).
* The Scriptcase custom code (for app_Login) is a working set; the production code is currently in the app_Login module in the Point System project. The code is here in case the app_Login script is overwritten somehow in Scriptcase.
* The Point Sheet Details (using checkboxes) allows for three different ways of entering members' activity:
  1. Enter a line number and hit the tab key;
  2. hit the "select all" button;
  3. Click on each individual member's line number via checkbox.   
* The SQL script is a working set only, but is the basis for a view that is created in MYSQL databases (test and production). The code contains square brackets [] that contain additional script, mainly for the WHERE clause of a SELECT statement.
