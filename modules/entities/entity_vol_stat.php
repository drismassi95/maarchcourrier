<?php
/**
* Copyright Maarch since 2008 under licence GPLv3.
* See LICENCE.txt file at the root folder for more details.
* This file is part of Maarch software.

* @brief   entity_vol_stat
* @author  dev <dev@maarch.org>
* @ingroup entities
*/

require_once 'modules'.DIRECTORY_SEPARATOR."reports".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_modules_tools.php";
require_once 'modules'.DIRECTORY_SEPARATOR."entities".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_manage_entities.php";
require_once "core".DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR."class_manage_status.php";

$core_tools = new core_tools();
$rep = new reports();
$core_tools->load_lang();
$id = '';
if (isset($_REQUEST['arguments']) && !empty($_REQUEST['arguments'])) {
    $id = $rep->get_arguments_for_report($_REQUEST['arguments'], 'id');
}

$content = '';
$content .='<div id="params">';
  $content .='<form id="report_by_period_form" name="report_by_period_form" method="get" action="">';
        if($core_tools->test_service('graphics_reports', 'reports', false) == 1){
        $content .='<div id="statLabel" style="font-weight:bold;text-align:center;text-transform:uppercase;">'._MAIL_VOL_BY_ENT_REPORT.' <span style="font-weight: initial;">(<input type="radio" name="form_report" id="report_array" value="array" checked="checked"/><label for="report_array"> '. _ARRAY . '</label>)</span></div>';       
         }else{
        $content .='<div id="statLabel" style="font-weight:bold;text-align:center;text-transform:uppercase;">'._MAIL_VOL_BY_ENT_REPORT.' <span style="font-weight: initial;">(<input type="radio" name="form_report" id="report_graph"  value="graph" checked="checked" /><label for="report_graph"> ' . _GRAPH . ' </label><input type="radio" name="form_report" id="report_array" value="array" /><label for="report_array"> '. _ARRAY . '</label>)</span></div>';
          }
  $content .='<br/>';
        $content .='<input type="hidden" name="id_report" id="id_report" value="'.$id.'" />';
  $content .='<table style="width:600px;border: solid 1px #009DC5;margin:auto;" >';
        $content .='<tr>';
          $content .='<td align="left">';
          $content .='<p class="double"  style="padding:10px;text-align:justify;border:solid 1px #ccc;">';
          $content .= _ENTITY_VOL_STAT_DESC;
          $content .='</p>';
          $content .='<br/>';
          $content .='<p class="double">';
          $content .='<input type="radio" name="type_period" id="period_by_year" value="year" checked="checked" />';
          $content .= _SHOW_YEAR_GRAPH;
          $content .=' <select name="the_year" id="the_year">';
          $year=date("Y");
        $i_current=date("Y'");
        while ($year <> ($i_current-5))
{
        $content .= '<option value = "'.$year.'">'.$year.'</option>';
        $year= $year-1;
            }
            $content .='</select>';
            $content .='</p>';

             $content .='<p class="double">';
               $content .='<input type="radio" name="type_period" id="period_by_month" value="month" />';
               $content .= _SHOW_GRAPH_MONTH;
   				$content .=' <select name="the_month" id="the_month">';
              		$content .='<option value ="01"> '. _JANUARY.' </option>';
                  	$content .='<option value ="02"> '._FEBRUARY.' </option>';
                 	$content .='<option value ="03"> '._MARCH.' </option>';
                 	$content .='<option value ="04"> '._APRIL.' </option>';
                 	$content .='<option value ="05"> '._MAY.' </option>';
                 	$content .='<option value ="06"> '._JUNE.' </option>';
                 	$content .='<option value ="07"> '._JULY.' </option>';
                 	$content .='<option value ="08"> '._AUGUST.' </option>';
                	$content .='<option value ="09"> '._SEPTEMBER.' </option>';
                	$content .='<option value ="10"> '._OCTOBER.'</option>';
                 	$content .='<option value ="11"> '._NOVEMBER.' </option>';
                 	$content .='<option value ="12"> '._DECEMBER.' </option>';
               	$content .='</select> ';
	          $content .= _OF_THIS_YEAR.'.</p>';
	   		if($id <> 'process_delay')
	    	{
	           	$content .='<p class="double">';
              	$content .='<input type="radio" id="custom_period" name="type_period" value="custom_period" /><label for="period">'._TITLE_STATS_CHOICE_PERIOD.'.&nbsp;</label>'._SINCE.'&nbsp;:&nbsp;<input name="datestart" type="text"  id="datestart" onclick="showCalender(this);" />&nbsp;'._FOR.'&nbsp;:&nbsp;<input name="dateend" type="text"  id="dateend" onclick="showCalender(this);" /></p>';
        	}
                $content.='<p class="double" style="margin-left:10px">';
          $content.= _FILTER_BY.' :<br /><br />';
          $entities = array();
          $ent = new entity();
            $except[] = $_SESSION['m_admin']['entity']['entityId'];
    
          $entities=$ent->getShortEntityTree($entities, 'all', '', $except );

          $content.='<select name="entities_chosen" data-placeholder="'._DEPARTMENT_DEST.'" id="entities_chosen" size="10" multiple="multiple">';
          for($i=0; $i<count($entities);$i++)
          {
              $content.="<option";
              if ($entities[$i]['ID'] == $_SESSION['user']['primaryentity']['id']) {
                  $content .= ' selected';
              }
              $content.=" value='".$entities[$i]['ID']."'>";
              $content.=$entities[$i]['LABEL']."</option>";
          }             
          $content.='</select>';
            $content .='<input type="checkbox" title="'._INCLUDE_SUB_ENTITIES.'" name="sub_entities" id="sub_entities" />';          
            $content .= '<script>$j(document).ready(function() {$j("#sub_entities").tooltipster();});</script>';
            $js .= 'new Chosen($(\'entities_chosen\'),{width: "95%", disable_search_threshold: 10, search_contains: true});';
            $content.= '<br/><br/>';
            $status_obj = new manage_status();
            $status = $status_obj->get_searchable_status();
            $content.='<select name="status_chosen" data-placeholder="'._STATUS.'" id="status_chosen" size="10" multiple="multiple">';
            for($i=0; $i < count($status); $i++)
            {
                $content.="<option";
                $content.=" value='".$status[$i]['ID']."'>";
                $content.=$status[$i]['LABEL']."</option>"; 
            }
            $content.='</select>';
            $js .= 'new Chosen($(\'status_chosen\'),{width: "95%", disable_search_threshold: 10, search_contains: true});';
            $content.= '<br/><br/>';
            $content.='<select name="priority_chosen" data-placeholder="'._PRIORITY.'" id="priority_chosen" size="10" multiple="multiple">';
            foreach(array_keys($_SESSION['mail_priorities']) as $priority)
            {
                $content.="<option";
                $content.=" value='".$priority."'>";
                $content.=$_SESSION['mail_priorities'][$priority]."</option>"; 
            }
            $content.='</select>';
            $js .= 'new Chosen($(\'priority_chosen\'),{width: "95%", disable_search_threshold: 10, search_contains: true});';
          $content.='</p>'; 
        $content .='</td>';
        $content .='</tr>';
        $content .='<tr>';
        $content .='<td style="text-align:center;"><input type="button" id="validate" name="validate" value="'._VALIDATE.'" class="button" onclick="valid_report_by_period(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=get_entity_late_mail\');" /></td>';
        $content .='</tr>';
       $content .='</table>';
  $content .='</form>';
$content .='</div>';
$content .='<div id="result_period_report"></div>';
$js .= 'valid_report_by_period(\''.$_SESSION['config']['businessappurl'].'index.php?display=true&module=entities&page=get_entity_vol\');';

echo "{content : '".addslashes($content)."', exec_js : '".addslashes($js)."'}";
exit();
