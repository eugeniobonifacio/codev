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

<?php

include_once "project_cache.class.php";
include_once "jobs.class.php";


class Project {

  const type_workingProject   = 0;     // normal projects are type 0
  const type_sideTaskProject  = 1;     // SuiviOp must be type 1
  const type_noCommonProject  = 2;     // projects which jave only assignedJobs (no common jobs) REM: these projects are not considered as sideTaskProjects


   // REM: the values are also the names of the fields in codev_sidetasks_category_table
   public static $keyProjManagement = "cat_management";
   public static $keyIncident       = "cat_incident";
   public static $keyInactivity     = "cat_absence";
   public static $keyTools          = "cat_tools";
   public static $keyWorkshop       = "cat_workshop";

	var $id;
	var $name;
	var $description;
	var $type;
	var $jobList;
	var $categoryList;

	// -----------------------------------------------
	public function Project($id) {

      $this->id = $id;

      $this->initialize();
   }

   // -----------------------------------------------
   public function initialize() {

   	$query  = "SELECT mantis_project_table.name, mantis_project_table.description, codev_team_project_table.type ".
   	          "FROM `mantis_project_table`, `codev_team_project_table` ".
   	          "WHERE mantis_project_table.id = $this->id ".
   	          "AND codev_team_project_table.project_id = $this->id ";

      $result = mysql_query($query) or die("Query failed: $query");
      $row = mysql_fetch_object($result);

      $this->name        = $row->name;
      $this->description = $row->description;
      $this->type        = $row->type;

      // ---- if SideTaskProject get categories
      if ( $this->type == Project::type_sideTaskProject) {

         $query  = "SELECT * FROM `codev_sidetasks_category_table` WHERE project_id = $this->id ";
         $result = mysql_query($query) or die("Query failed: $query");
         $row = mysql_fetch_object($result);

         $this->categoryList = array();
         $this->categoryList[Project::$keyProjManagement] = $row->cat_management;
         $this->categoryList[Project::$keyIncident]       = $row->cat_incident;
         $this->categoryList[Project::$keyInactivity]     = $row->cat_absence;
         $this->categoryList[Project::$keyTools]          = $row->cat_tools;
         $this->categoryList[Project::$keyWorkshop]          = $row->cat_workshop;
      }

      #echo "DEBUG $this->name type=$this->type categoryList ".print_r($this->categoryList)." ----<br>\n";

      #$this->jobList     = $this->getJobList();
   }

   // -----------------------------------------------
   /**
    *
    * @param unknown_type $projectName
    */
   public static function createSideTaskProject($projectName) {

      $estimEffortCustomField  = Config::getInstance()->getValue("customField_effortEstim");
      $addEffortCustomField    = Config::getInstance()->getValue("customField_addEffort");
      $remainingCustomField    = Config::getInstance()->getValue("customField_remaining");
      $deadLineCustomField     = Config::getInstance()->getValue("customField_deadLine");
      $deliveryDateCustomField = Config::getInstance()->getValue("customField_deliveryDate");


      // check if name exists
      $query  = "SELECT id FROM `mantis_project_table` WHERE name='$projectName'";
      $result = mysql_query($query) or die("Query failed: $query");
      $projectid    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : -1;
      if (-1 != $projectid) {
         echo "ERROR: Project name already exists ($projectName)<br/>\n";
         return -1;
      }

      // create new Project
      $query = "INSERT INTO `mantis_project_table` (`name`, `status`, `enabled`, `view_state`, `access_min`, `description`, `category_id`, `inherit_global`) ".
               "VALUES ('$projectName','50','1','10','10','$projectDesc','1','0');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $projectid = mysql_insert_id();


      // add custom fields BI,BS,RAE,DeadLine,DeliveryDate
      $query = "INSERT INTO `mantis_custom_field_project_table` (`field_id`, `project_id`, `sequence`) ".
               "VALUES ('$estimEffortCustomField',  '$projectid','0'), ".
                      "('$addEffortCustomField',    '$projectid','1'), ".
                      "('$remainingCustomField',    '$projectid','2'), ".
                      "('$deadLineCustomField',     '$projectid','3'), ".
                      "('$deliveryDateCustomField', '$projectid','5');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");


      // create entry in codev_sidetasks_category_table
      $query = "INSERT INTO `codev_sidetasks_category_table` (`project_id`) VALUES ('$projectid');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

      return $projectid;
   }


   // -----------------------------------------------
   public function addCategoryProjManagement($catName) {
      return $this->addCategory(Project::$keyProjManagement, $catName);
   }
   public function addCategoryInactivity($catName) {
      return $this->addCategory(Project::$keyInactivity, $catName);
   }
   public function addCategoryIncident($catName) {
      return $this->addCategory(Project::$keyIncident, $catName);
   }
   public function addCategoryTools($catName) {
      return $this->addCategory(Project::$keyTools, $catName);
   }
   public function addCategoryWorkshop($catName) {
      return $this->addCategory(Project::$keyWorkshop, $catName);
   }

   // -----------------------------------------------
   /**
    * WARN: the $catKey is the name of the field in codev_sidetasks_category_table
    * @param string $catKey in (Project::$keyProjManagement, Project::$keyIncident, Project::$keyInactivity, Project::$keyTools, Project::$keyWorkshop
    * @param string $catName
    */
   private function addCategory($catKey, $catName) {

   	// create category for SideTask Project
      $query = "INSERT INTO `mantis_category_table`  (`project_id`, `user_id`, `name`, `status`) VALUES ('$this->id','0','$catName', '0');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $catId = mysql_insert_id();

      $query = "UPDATE `codev_sidetasks_category_table` SET $catKey = $catId WHERE project_id = $this->id";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

      $this->categoryList[$catKey] = $catId;

      return $catId;
   }


   // -----------------------------------------------
   public function addIssueProjManagement($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyProjManagement, $issueSummary, $issueDesc);
   }
   public function addIssueInactivity($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyInactivity, $issueSummary, $issueDesc);
   }
   public function addIssueIncident($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyIncident, $issueSummary, $issueDesc);
   }
   public function addIssueTools($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyTools, $issueSummary, $issueDesc);
   }
   public function addIssueWorkshop($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyWorkshop, $issueSummary, $issueDesc);
   }

   // -----------------------------------------------
   private function addSideTaskIssue($catKey, $issueSummary, $issueDesc) {

   	global $status_closed;
   	$cat_id = $this->categoryList["$catKey"];

      $query = "INSERT INTO `mantis_bug_text_table`  (`description`) VALUES ('$issueDesc');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
   	$bug_text_id = mysql_insert_id();

   	$query = "INSERT INTO `mantis_bug_table`  (`project_id`, `category_id`, `summary`, `priority`, `reproducibility`, `status`, `bug_text_id`) ".
   	         "VALUES ('$this->id','$cat_id','$issueSummary','10','100','$status_closed','$bug_text_id');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $bugt_id = mysql_insert_id();

   	return $bugt_id;
   }


   // -----------------------------------------------
   // Job list depends on project type:
   // if type=1 (SideTask) than only jobs for SideTasks are displayed.
   // if type=0 (Project) then all jobs which codev_project_job_table.project_id = $this->id
   //                     OR codev_job_table.type = $commonJobType (common jobs)
   public function getJobList() {
   	$workingProjectType  = Project::type_workingProject;
   	$sideTaskProjectType = Project::type_sideTaskProject;
   	$commonJobType       = Job::type_commonJob;

   	$jobList = array();

   	if (0 != $this->id) {
	   	if ($sideTaskProjectType == $this->type) {
		      $query  = "SELECT codev_job_table.id, codev_job_table.name ".
		                "FROM `codev_job_table`, `codev_project_job_table` ".
		                "WHERE codev_job_table.id = codev_project_job_table.job_id ".
		                "AND codev_project_job_table.project_id = $this->id";
	   	} elseif ($workingProjectType == $this->type) {
	   		// all other projects
	         $query  = "SELECT codev_job_table.id, codev_job_table.name ".
	                   "FROM `codev_job_table` ".
	                   "LEFT OUTER JOIN  `codev_project_job_table` ".
	                   "ON codev_job_table.id = codev_project_job_table.job_id ".
	                   "WHERE (codev_job_table.type = $commonJobType OR codev_project_job_table.project_id = $this->id)";

	   	} else {
	   		echo "ERROR Project.getJobList(): unknown project type !";
	   		exit;
	   	}
	      $result = mysql_query($query) or die("Query failed: $query");
	   	if (0 != mysql_num_rows($result)) {
		   	while($row = mysql_fetch_object($result))
		      {
		         $jobList[$row->id] = $row->name;
		      }
	      }
   	}
      return $jobList;
   }

   // -----------------------------------------------
   public function getIssueList() {

   	$issueList = array();

	   $query = "SELECT DISTINCT id FROM `mantis_bug_table` ".
	            "WHERE project_id=$this->id ".
	            "ORDER BY id DESC";

	   $result = mysql_query($query) or die("Query failed: $query");
	   while($row = mysql_fetch_object($result)) {
	   	$issueList[] = $row->id;
	   }
	   return $issueList;
   }

   // -----------------------------------------------
   public function isSideTasksProject() {
   	$sideTaskProjectType = Project::type_sideTaskProject;

		return ($sideTaskProjectType == $this->type);
	}


   // -----------------------------------------------
	public function getManagementCategoryId() {
		if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyProjManagement];
   }
   public function getIncidentCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyIncident];
   }
   public function getInactivityCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyInactivity];
   }
   public function getToolsCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyTools];
   }
   public function getWorkshopCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyWorkshop];
   }

}

?>
