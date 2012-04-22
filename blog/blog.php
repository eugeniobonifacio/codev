<?php
include_once('../include/session.inc.php');
/*
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
*/

require '../path.inc.php';

require('super_header.inc.php');


include_once('user.class.php');
include_once('blog_manager.class.php');

require('display.inc.php');



function getBlogPosts($postList) {

   $blogPosts = array();

   foreach ($postList as $id => $bpost) {

   	$srcUser = UserCache::getInstance()->getUser($bpost->src_user_id);

      $item = array();

      // TODO
      $item['category'] = Config::getVariableValueFromKey(Config::id_blogCategories, $bpost->category);
      $item['severity'] = BlogPost::getSeverityName($bpost->severity);
      $item['summary'] = $bpost->summary;
      $item['content'] = $bpost->content;
      $item['date_submitted'] = date('Y-m-d G:i',$bpost->date_submitted);
      $item['from']    = $srcUser->getName();

      // find receiver
      if (0 != $bpost->dest_user_id) {
      	$destUser = UserCache::getInstance()->getUser($bpost->dest_user_id);
         $item['to'] = $destUser->getName();
      } else if (0 != $bpost->dest_team_id) {
      	$team = new Team($bpost->dest_team_id);
         $item['to'] = $team->name;
      } else if (0 != $bpost->dest_project_id) {
      	$destProj = ProjectCache::getInstance()->getProject($bpost->dest_project_id);
      	$item['to'] = $destProj->name;
      } else {
      	$item['to'] = '?';
      }

      $item['activity'] = 'activities...';
      $item['ack'] = "<input type='button' value='".T_('Ack')."' onclick='javascript: ackPost(".$bpost->id.")' />";

      $blogPosts[$id] = $item;
   }
   return $blogPosts;
}



// ================ MAIN =================

$logger = Logger::getLogger("blog");

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_("Blog"));

if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);

   $blogManager = new BlogManager();

   $src_user_id  = $session_user->id;
   $severity     = BlogPost::severity_normal;
   $category     = 0;
   $summary      = 'Welcome to the real world';
   $content      = 'Hello world !<br>The quick brown fox jumps over the lazy dog<br>Casse toi pauv\' con !';
   $dest_team_id = 4;
   $dest_user_id    = 0;
   $dest_project_id = 0;
   $date_expire     = 0;
   $color=0;

   $blogPost_id = BlogPost::create($src_user_id, $severity, $category, $summary, $content,
         $dest_user_id, $dest_project_id, $dest_team_id, $date_expire, $color);


   $postList = $blogManager->getPosts($session_user->id);
   $blogPosts = getBlogPosts($postList);
   $smartyHelper->assign('blogPosts', $blogPosts);

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
