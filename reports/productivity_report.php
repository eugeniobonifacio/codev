<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}
?>

<?php
   $_POST[page_name] = T_("Productivity Report");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<script language="JavaScript">
  function submitForm() {
    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].action.value = "timeTrackingReport";
    document.forms["form1"].submit();
 }

  function setProjectid() {
     document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
     document.forms["form1"].projectid.value = document.getElementById('projectidSelector').value;
     document.forms["form1"].action.value="setProjectid";
     document.forms["form1"].submit();
  }
</script>

<div id="content">

<?php

include_once "period_stats.class.php";
include_once "project.class.php";

include_once "time_tracking.class.php";
require_once('tc_calendar.php');

// -----------------------------------------------
function setInfoForm($teamid, $defaultDate1, $defaultDate2, $defaultProjectid) {
  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate1);

  $myCalendar1 = new tc_calendar("date1", true, false);
  $myCalendar1->setIcon("../calendar/images/iconCalendar.gif");
  $myCalendar1->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar1->setPath("../calendar/");
  $myCalendar1->setYearInterval(2010, 2015);
  $myCalendar1->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar1->setDateFormat('Y-m-d');
  $myCalendar1->startMonday(true);

  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate2);

  $myCalendar2 = new tc_calendar("date2", true, false);
  $myCalendar2->setIcon("../calendar/images/iconCalendar.gif");
  $myCalendar2->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar2->setPath("../calendar/");
  $myCalendar2->setYearInterval(2010, 2015);
  $myCalendar2->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar2->setDateFormat('Y-m-d');
  $myCalendar2->startMonday(true);

  echo "<div class=center>";
  // Create form
  if (isset($_GET['debug'])) {
      echo "<form id='form1' name='form1' method='post' action='productivity_report.php?debug'>\n";
  } else {
  	   echo "<form id='form1' name='form1' method='post' action='productivity_report.php'>\n";
  }

  echo T_("Team").": <select id='teamidSelector' name='teamidSelector'>\n";

  $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
  $mTeamList = $session_user->getTeamList();
  $lTeamList = $session_user->getLeadedTeamList();
  $oTeamList = $session_user->getObservedTeamList();
  $managedTeamList = $session_user->getManagedTeamList();
  $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

  foreach($teamList as $tid => $tname) {
    if ($tid == $teamid) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "&nbsp;".T_("Start Date").": "; $myCalendar1->writeScript();

  echo "&nbsp; <span title='".T_("(included)")."'>".T_("End Date").": </span>"; $myCalendar2->writeScript();

  echo "&nbsp;<input type=button value='".T_("Compute")."' onClick='javascript: submitForm()'>\n";

  echo "<input type=hidden name=teamid  value=$teamid>\n";
  echo "<input type=hidden name=projectid value=$defaultProjectid>\n";

  echo "<input type=hidden name=currentAction value=setInfoForm>\n";
  echo "<input type=hidden name=nextAction    value=timeTrackingReport>\n";

  echo "</form>\n";
  echo "</div>";
}




// ---------------------------------------------------------------
function setProjectSelectionForm($teamid, $defaultProjectid) {

   // Display form
   echo "<div style='text-align: left;'>";
  if (isset($_GET['debug'])) {
      echo "<form id='projectSelectionForm' name='projectSelectionForm' method='post' action='productivity_report.php?debug'>\n";
  } else {
      echo "<form id='projectSelectionForm' name='projectSelectionForm' method='post' action='productivity_report.php'>\n";
  }

  $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);

   // --- Project List
   $query  = "SELECT mantis_project_table.id, mantis_project_table.name ".
                 "FROM `codev_team_project_table`, `mantis_project_table` ".
                 "WHERE codev_team_project_table.team_id = $teamid ".
                 "AND codev_team_project_table.project_id = mantis_project_table.id ".
                 "ORDER BY mantis_project_table.name";
       $result = mysql_query($query) or die("Query failed: $query");
         if (0 != mysql_num_rows($result)) {
            while($row = mysql_fetch_object($result))
            {
               $projList[$row->id] = $row->name;
            }
       }
   echo "<span class='caption_font'>".T_("Project Detail")." </span>\n";
   echo "<select id='projectidSelector' name='projectidSelector' onchange='javascript: setProjectid()' title='".T_("Project")."'>\n";
   echo "<option value='0'> </option>\n";
   foreach ($projList as $pid => $pname)
   {
      if ($pid == $defaultProjectid) {
         echo "<option selected value='".$pid."'>$pname</option>\n";
      } else {
         echo "<option value='".$pid."'>$pname</option>\n";
      }
   }
   echo "</select>\n";

   echo "<input type=hidden name=teamid     value=$teamid>\n";
   echo "<input type=hidden name=projectid value=$defaultProjectid>\n";
   echo "<input type=hidden name=action    value=noAction>\n";
   echo "</form>\n";

   echo "</div>\n";
}




// -----------------------------------------------
function displayRates ($timeTracking) {

  $prodDays                = $timeTracking->getProdDays();
  $sideProdDaysDevel       = $timeTracking->getProdDaysSideTasks(true);
  $sideProdDaysManagers    = $timeTracking->getProdDaysSideTasks(false) - $sideProdDaysDevel;
  $productivityRateETA     = $timeTracking->getProductivityRate("ETA");
  $productivityRateBI      = $timeTracking->getProductivityRate("EffortEstim");
  $efficiencyRate          = $timeTracking->getEfficiencyRate();
  $systemDisponibilityRate = $timeTracking->getSystemDisponibilityRate();
  $productionDaysForecast  = $timeTracking->getProductionDaysForecast();
//  $prodRateNoSupportETA    = $timeTracking->getProductivityRateNoSupport("ETA");
//  $prodRateNoSupportBI     = $timeTracking->getProductivityRateNoSupport("EffortEstim");

  echo "<table>\n";
  echo "<caption>".T_("Productivity indicators")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Indicator")."</th>\n";
  echo "<th>".T_("Value")."</th>\n";
  echo "<th>".T_("Description")."</th>\n";
  echo "<th>".T_("Formula")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Production Days : Projects")."</td>\n";
  echo "<td>$prodDays</td>\n";
  echo "<td>".T_("days spent on projects")."</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Production Days : SuiviOp Dev")."</td>\n";
  echo "<td>$sideProdDaysDevel</td>\n";
  echo "<td>".T_("days spent on sideTasks (holidays not included, developpers only)")."</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Production Days : SuiviOp Managers")."</td>\n";
  echo "<td>$sideProdDaysManagers</td>\n";
  echo "<td>".T_("days spent on sideTasks (holidays not included, managers only)")."</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Production Days : total")."</td>\n";
  echo "<td>".($sideProdDaysDevel + $sideProdDaysManagers + $prodDays)."</td>\n";
  echo "<td>".T_("number of days billed")."</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Available Workload")."</td>\n";
  echo "<td>".$productionDaysForecast."</td>\n";
  echo "<td>".T_("Workload Forecasting (holidays not included, developpers only)")."</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Efficiency Rate")."</td>\n";
  echo "<td>".number_format($efficiencyRate, 2)."%</td>\n";
  echo "<td>".T_("Development workload (developpers only)")."</td>\n";
  echo "<td>ProjProdDays / TotalProdDays * 100</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("System Availability")."</td>\n";
  echo "<td>".number_format($systemDisponibilityRate, 3)."%</td>\n";
  echo "<td>".T_("Platform Availability")."</td>\n";
  echo "<td>100 - (breakdownDays / prodDays)</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='".T_("BEFORE analysis")."'>".T_("Productivity Rate ETA")."</td>\n";
  echo "<td>".number_format($productivityRateETA, 2)."</td>\n";
  echo "<td>".T_("- If estimation is correct the aimed number should be 1.")."<br/>".
            T_("- A number below 1 means a lesser efficiency.")."<br/>".
            T_("- Time spent on a task is balanced by a toughness indicator")."<br/>".
            T_("- Computed on task Resolved/Closed in the given period")."<br/>".
            T_("- Reopened tasks are not taken into account")."</td>\n";
  echo "<td>sum(ETA_balance) / sum(elapsed)</td>\n";
  echo "</tr>\n";
/*
  echo "<tr>\n";
  echo "<td title='".T_("BEFORE analysis")."'>".T_("Prod Rate NoSupport ETA")."</td>\n";
  echo "<td>".number_format($prodRateNoSupportETA, 2)."</td>\n";
  echo "<td></td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";
*/
  echo "<tr>\n";
  echo "<td title='".T_("AFTER analysis")."'>".T_("Productivity Rate")."</td>\n";
  echo "<td>".number_format($productivityRateBI, 2)."</td>\n";
  echo "<td>".T_("- If estimation is correct the aimed number should be 1.")."<br/>".
            T_("- A number below 1 means a lesser efficiency.")."<br/>".
            T_("- Computed on task Resolved/Closed in the given period")."<br/>".
            T_("- Reopened tasks are not taken into account")."</td>\n";
  echo "<td>sum(EffortEstim + BS) / sum(elapsed)</td>\n";
  echo "</tr>\n";
/*
  echo "<tr>\n";
  echo "<td title='".T_("BEFORE analysis")."'>".T_("Prod Rate NoSupport")."</td>\n";
  echo "<td>".number_format($prodRateNoSupportBI, 2)."</td>\n";
  echo "<td></td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";
*/

  echo "</table>\n";

  //echo "<br/>SideTasks<br/>";
  //echo "Nb Production Days  : $sideProdDays<br/>";
  //echo "ProductivityRate    : ".$sideProductivityRate."<br/>\n";
}


// -----------------------------------------------
// display Drifts for Issues that have been marked as 'Resolved' durung the timestamp
function displayResolvedDriftStats ($timeTracking, $withSupport = true) {
  global $status_resolved;
  global $status_closed;

  $driftStats = $timeTracking->getResolvedDriftStats($withSupport);

  echo "<table>\n";
  echo "<caption>".T_("Drift - Tasks resolved in the period")."</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  echo "<th width='100' title='".T_("BEFORE analysis")."'>ETA</th>\n";
  echo "<th width='100' title='".T_("AFTER analysis")."'>EffortEstim <br/>(BI + BS)</th>\n";
  echo "<th>".T_("Description")."</th>\n";
  echo "<th>".T_("Formula")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='".T_("If < 0 then ahead on planning.")."'>".T_("Drift")."</td>\n";
  echo "<td title='elapsed - ETA'>".number_format($driftStats["totalDriftETA"], 2)."</td>\n";
  echo "<td title='elapsed - EffortEstim'>".number_format($driftStats["totalDrift"], 2)."</td>\n";
  echo "<td>".T_("Overflow day quantity")."<br/>".
            "- ".T_("Computed on task Resolved/Closed in the given period")."<br/>".
            "- ".T_("Reopened tasks are not taken into account")."<br/>\n".
            #"- ".T_("Support time is not taken into account")."<br/>\n".
            "- ".T_("If < 0 then ahead on planning.")."</td>\n";
  echo "<td>elapsed - EffortEstim</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks in drift")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsPosETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftPosETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsPos"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftPos"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats["formatedBugidPosList"]."</td>\n";
  echo "<td>".T_("drift")." > 1</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks in time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsEqualETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftEqualETA"] + $driftStatsClosed["driftEqualETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsEqual"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftEqual"] + $driftStatsClosed["driftEqual"]).")</span></td>\n";
  if (isset($_GET['debug'])) {
   echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats["formatedBugidEqualList"]."</td>\n";
  } else {
   echo "<td title='".$driftStats["bugidEqualList"]."'>".T_("Tasks resolved in time")."</td>\n";
  }
  echo "<td> -1 <= ".T_("drift")." <= 1</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks ahead")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsNegETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftNegETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats["nbDriftsNeg"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats["driftNeg"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats["formatedBugidNegList"]."</td>\n";
  echo "<td>".T_("drift")." < -1</td>\n";
  echo "</tr>\n";
  echo "</table>\n";
}


// -----------------------------------------------
// display TimeDrifts for Issues that have been marked as 'Resolved' durung the timestamp
function displayTimeDriftStats ($timeTracking) {
  global $status_resolved;
  global $status_closed;

  $timeDriftStats = $timeTracking->getTimeDriftStats();  // all issues delivered within the period

  echo "<table>\n";
  echo "<caption title='".T_("Tasks having no deadLine are not reported here")."'>".T_("Adherence to deadlines")."</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  echo "<th width='100'>".T_("Total")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "<th>".T_("Formula")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks NOT delivered on time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($timeDriftStats["nbDriftsPos"])."<span title='".T_("nb days")."' class='floatr'>(".round($timeDriftStats["driftPos"]).")</span></td>\n";
  echo "<td title='".T_("Tasks NOT delivered on time")."'>".$timeDriftStats["formatedBugidPosList"]."</td>\n";
  echo "<td>DeliveryDate > DeadLine</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks delivered on time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($timeDriftStats["nbDriftsNeg"])."<span title='".T_("nb days")."' class='floatr'>(".round($timeDriftStats["driftNeg"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$timeDriftStats["formatedBugidNegList"]."</td>\n";
  echo "<td>DeliveryDate <= DeadLine</td>\n";
  echo "</tr>\n";
  echo "</table>\n";
}





// -----------------------------------------------
// display Drifts for Issues that are CURRENTLY OPENED
function displayCurrentDriftStats ($timeTracking) {

   global $status_resolved;
   global $status_delivered;
   global $status_closed;

    // ---- get Issues that are not Resolved/Closed
    $formatedProdProjectList = implode( ', ', $timeTracking->prodProjectList);
    $issueList = array();

    $query = "SELECT DISTINCT id ".
               "FROM `mantis_bug_table` ".
               "WHERE status NOT IN ($status_resolved,$status_delivered,$status_closed) ".
               "AND project_id IN ($formatedProdProjectList)".
               "ORDER BY id DESC";
    $result = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->id);
            $user = UserCache::getInstance()->getUser($issue->handlerId);

            $issueList[] = $issue;
    }

    if (0 != count($issueList)) {
      $driftStats_new = $timeTracking->getIssuesDriftStats($issueList);
    } else {
    	$driftStats_new = array();
    }

  echo "<table>\n";
  echo "<caption>".T_("Drift - Today opened Tasks")."</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  echo "<th width='100' title='".T_("BEFORE analysis")."'>".T_("ETA")."</th>\n";
  echo "<th width='100' title='".T_("AFTER analysis")."'>".T_("EffortEstim <br/>(BI + BS)")."</th>\n";
  echo "<th>".T_("Description")."</th>\n";
  echo "<th>".T_("Formula")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='".T_("If < 0 then ahead on planning.")."'>".T_("Drift")."</td>\n";
  echo "<td title='elapsed - ETA'>".number_format($driftStats_new["totalDriftETA"], 2)."</td>\n";
  echo "<td title='elapsed - EffortEstim'>".number_format($driftStats_new["totalDrift"], 2)."</td>\n";
  echo "<td>".T_("Overflow day quantity")."<br/>".
            "- ".T_("Computed on task NOT Resolved/Closed on ").date("Y-m-d").".<br/>";
  echo "<td>elapsed - EffortEstim</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks in drift")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsPosETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftPosETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsPos"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftPos"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats_new["formatedBugidPosList"]."</td>\n";
  echo "<td>".T_("drift")." > 1</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks in time")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsEqualETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftEqualETA"] + $driftStatsClosed["driftEqualETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsEqual"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftEqual"] + $driftStatsClosed["driftEqual"]).")</span></td>\n";
  if (isset($_GET['debug'])) {
   echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats_new["formatedBugidEqualList"]."</td>\n";
  } else {
   echo "<td title='".$driftStats_new["bugidEqualList"]."'>".T_("Tasks resolved in time")."</td>\n";
  }
  echo "<td> -1 <= ".T_("drift")." <= 1</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>".T_("Tasks ahead")."</td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsNegETA"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftNegETA"]).")</span></td>\n";
  echo "<td title='".T_("nb tasks")."'>".($driftStats_new["nbDriftsNeg"])."<span title='".T_("nb days")."' class='floatr'>(".($driftStats_new["driftNeg"]).")</span></td>\n";
  echo "<td title='".T_("Task list for EffortEstim")."'>".$driftStats_new["formatedBugidNegList"]."</td>\n";
  echo "<td>".T_("drift")." < -1</td>\n";
  echo "</tr>\n";
  echo "</table>\n";
}


// --------------------------------
function displayWorkingDaysPerJob($timeTracking) {
  echo "<table width='300'>\n";
  echo "<caption>".T_("Load per Job")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Job")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  $query     = "SELECT id, name FROM `codev_job_table`";
  $result    = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
    echo "<tr>\n";
    echo "<td>$row->name</td>\n";
    echo "<td>".$timeTracking->getWorkingDaysPerJob($row->id)."</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

// -----------------------------------------------
function displayWorkingDaysPerProject($timeTracking) {
  echo "<table width='300'>\n";
  echo "<caption>".T_("Load per Project")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Project")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  $query     = "SELECT mantis_project_table.id, mantis_project_table.name ".
               "FROM `mantis_project_table`, `codev_team_project_table` ".
               "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
               "AND codev_team_project_table.team_id = $timeTracking->team_id ".
               " ORDER BY name";
  $result    = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
    echo "<tr>\n";
    echo "<td>";
    echo "$row->name\n";
    if (isset($_GET['debug'])) { echo " (".$row->id.")"; }
    echo "</td>\n";
    echo "<td>".$timeTracking->getWorkingDaysPerProject($row->id)."</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

// -----------------------------------------------
function displaySideTasksProjectDetails($timeTracking) {

  $sideTaskProjectType = Project::type_sideTaskProject;

  $durationPerCategory = array();
  $formatedBugsPerCategory = array();
  $stProjList = "";

  // find all sideTasksProjects (type = 1)
  $query     = "SELECT project_id ".
               "FROM `codev_team_project_table` ".
               "WHERE team_id = $timeTracking->team_id ".
               "AND type = $sideTaskProjectType";
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
     $durPerCat = $timeTracking->getProjectDetails($row->project_id);
     foreach ($durPerCat as $catName => $bugList)
     {
     	   foreach ($bugList as $bugid => $duration) {
     	   	$durationPerCategory[$catName] += $duration;

     	   	if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
     	   	$issue = IssueCache::getInstance()->getIssue($bugid);
            $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
     	   }
     }

     $proj = ProjectCache::getInstance()->getProject($row->project_id);
     $stProjList[] = $proj->name;

  }
  $formatedProjList = implode( ', ', $stProjList);

  $formatedBugList = "";

  echo "<table width='300'>\n";
  echo "<caption title='".T_("Projects").": $formatedProjList'>".T_("Project Management Detail")."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Category")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "<td>".$formatedBugsPerCategory[$catName]."</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

// -----------------------------------------------
function displayProjectDetails($timeTracking, $projectId) {

  $durationPerCategory = array();
  $formatedBugsPerCategory = array();

  $durPerCat = $timeTracking->getProjectDetails($projectId);
  foreach ($durPerCat as $catName => $bugList)
  {
      foreach ($bugList as $bugid => $duration) {
         $durationPerCategory[$catName] += $duration;

         if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $formatedBugsPerCategory[$catName] .= issueInfoURL($bugid, $issue->summary);
      }
  }

  $proj = ProjectCache::getInstance()->getProject($projectId);
  echo "<table width='300'>\n";
  //echo "<caption>".T_("Project Detail")." ".$proj->name."</caption>\n";
  echo "<tr>\n";
  echo "<th>".T_("Category")."</th>\n";
  echo "<th>".T_("Nb Days")."</th>\n";
  echo "<th>".T_("Tasks")."</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "<td>".$formatedBugsPerCategory[$catName]."</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

// -----------------------------------------------
function displayCheckWarnings($timeTracking) {
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";

  $result = mysql_query($query) or die("Query failed: $query");

  echo "<p style='color:red'>\n";

  while($row = mysql_fetch_object($result))
  {
    $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
    foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      if ($value < 1) {
        echo "<br/>$row->username: $formatedDate ".T_("incomplete (missing ")." ".(1-$value)." jour).\n";
      } else {
        echo "<br/>$row->username: $formatedDate ".T_("inconsistent")." (".($value)." jour).\n";
      }
    }

    $missingDays = $timeTracking->checkMissingDays($row->user_id);
    foreach ($missingDays as $date) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/>$row->username: $formatedDate ".T_("not defined.")."\n";
    }
  }
  echo "</p>\n";
}

// =========== MAIN ==========
$year = date('Y');

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;


$weekDates      = week_dates(date('W'),$year);

$action           = $_POST[action];
$defaultProjectid = isset($_POST[projectid]) ? $_POST[projectid] : 0;

$date1  = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : date("Y-m-d", $weekDates[1]);
$date2  = isset($_REQUEST["date2"]) ? $_REQUEST["date2"] : date("Y-m-d", $weekDates[5]);

$startTimestamp = date2timestamp($date1);
$endTimestamp = date2timestamp($date2);

$endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

//echo "DEBUG startTimestamp $startTimestamp  ".date("Y-m-d H:i:s", $startTimestamp)."<br/>";
//echo "DEBUG endTimestamp $endTimestamp  ".date("Y-m-d H:i:s", $endTimestamp)."<br/>";

$timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

setInfoForm($teamid, $date1, $date2, $defaultProjectid);
echo "<br/><br/>\n";


if (0 != $teamid) {

	echo "<br/>\n";
	echo "du ".date("Y-m-d  (H:i)", $startTimestamp)."&nbsp;<br/>";
	echo "au ".date("Y-m-d  (H:i)", $endTimestamp)."<br/><br/>\n";

	// Display on 3 columns
	echo "<div class=\"float\">\n";
	displayWorkingDaysPerJob($timeTracking);
	echo "</div>\n";

	echo "<div class=\"float\">\n";
	displayWorkingDaysPerProject($timeTracking);
	echo "</div>\n";

	echo "<div class=\"float\">\n";
	displaySideTasksProjectDetails($timeTracking);
	echo "</div>\n";

   echo "<div class=\"float\">\n";
   setProjectSelectionForm($teamid, $defaultProjectid);
   $defaultProjectid  = $_POST[projectid];
   if (0 != $defaultProjectid) {
      displayProjectDetails($timeTracking, $defaultProjectid);
   }
   echo "</div>\n";

	echo "<div class=\"spacer\"> </div>\n";

	echo "<br/><br/>\n";
	displayRates($timeTracking);

	echo "<br/><br/>\n";
   displayTimeDriftStats ($timeTracking);

   echo "<br/><br/>\n";
   displayResolvedDriftStats($timeTracking);


   echo "<br/><br/>\n";
   displayCurrentDriftStats($timeTracking);

	echo "<br/><br/>\n";
	displayCheckWarnings($timeTracking);
}

?>

</div>

<?php include 'footer.inc.php'; ?>
