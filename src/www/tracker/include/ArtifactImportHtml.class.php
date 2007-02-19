<?php

//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Marie-Luise Schneider
//

require_once('www/project/export/project_export_utils.php');
require_once('common/tracker/ArtifactImport.class.php');

$Language->loadLanguageMsg('tracker/tracker');
$Language->loadLanguageMsg('include/include');

class ArtifactImportHtml extends ArtifactImport {

  /**
   *  Constructor.
   *
   *    
   *  
   *
   *      @return boolean success.
   */
  function ArtifactImportHtml($ath,$art_field_fact,$group) {
    return $this->ArtifactImport($ath,$art_field_fact,$group);
  }


  /**
   * Permit CSV file input,parse the CSV file and show the parse report
   *
   *
   */
  function displayParse($csv_filename) {
    global $Language;

    if (!file_exists($csv_filename) || !is_readable($csv_filename)) {
      exit_missing_param();
    }
    $is_tmp = false;
    //}
    
    if($_REQUEST['notify']) {
        user_set_preference('tracker_import_notify_'.$this->ath->getID(), 1);
    }
    else {
        user_set_preference('tracker_import_notify_'.$this->ath->getID(), 0);
    }
    
    $ok = $this->parse($csv_filename,$is_tmp,
		$artifacts_data,
		$number_inserts,$number_updates);
    
      $this->ath->header(array ('title'=>$Language->getText('tracker_import','art_import').$this->ath->getID(). ' - ' . $this->ath->getName(),'pagename'=>'tracker',
			  'atid'=>$this->ath->getID(),'sectionvals'=>array($this->group->getPublicName()),
			  'help' => 'ArtifactImport.html'));
      
      echo '<h2>'.$Language->getText('tracker_import','parse_report').'</h2>';
      if (!$ok) {
	$this->showErrors();
      } else {
	echo $Language->getText('tracker_import','ready',array(($number_inserts+$number_updates),$number_inserts, $number_updates))."<br><br>\n";
	$this->showParseResults($this->parsed_labels,$artifacts_data);
      }
      
      $this->ath->footer(array());
  }
  

function showErrors() {
  echo $this->getErrorMessage()." <br>\n";
}


  /**
 * create the html output to visualize what has been parsed
 * @param $parsed_labels: array of the form (column_number => field_label) containing
 *                        all the fields parsed from $data
 * @param $artifacts_data: array containing the records for each artifact to be imported
 */
  function showParseResults($parsed_labels,$artifacts_data) {
    global $PHP_SELF,$sys_datefmt,$Language;

    $this->getImportUser($sub_user_id,$sub_user_name);
    $sub_on = format_date("Y-m-d",time());
    
    
    //add submitted_by and submitted_on columns only when 
    //artifact_id is not given otherwise the artifacts should
    //only be updated and we don't need to touch sub_on and sub_by
    if ($this->aid_column == -1 && $this->submitted_by_column == -1) {
      $new_sub_by_col = count($parsed_labels);
      $submitted_by_field = $this->art_field_fact->getFieldFromName('submitted_by');
      $parsed_labels[] = $submitted_by_field->getLabel();
    }
    
    if ($this->aid_column == -1 && $submitted_on_column == -1) {
      $new_sub_on_col = count($parsed_labels);
      $open_date_field = $this->art_field_fact->getFieldFromName('open_date');
      $parsed_labels[] = $open_date_field->getLabel();
    }
    
    echo '
        <FORM NAME="acceptimportdata" action="'.$PHP_SELF.'" method="POST" enctype="multipart/form-data">
        <p align="left"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('tracker_import_admin','import').'"></p>';
    
    
    echo html_build_list_table_top ($parsed_labels);
    
    
    for ($i=0; $i < count($artifacts_data) ; $i++) {
      
      $data = $artifacts_data[$i];
      if ($this->aid_column != -1) $aid = $data[$this->aid_column];
      
      echo '<TR class="'.util_get_alt_row_color($i).'">'."\n";
      
      for ($c=0; $c < count($parsed_labels); $c++) {
	
	$value = $data[$c];
	$width = ' class="small"';


	$submitted_by_field = $this->art_field_fact->getFieldFromName('submitted_by');
	$open_date_field = $this->art_field_fact->getFieldFromName('open_date');
	$aid_field = $this->art_field_fact->getFieldFromName('artifact_id');
	  
	//SUBMITTED_ON
	if ($parsed_labels[$c] == $open_date_field->getLabel()) {
	  //if insert show default value
	  if ($this->aid_column == -1 || $aid == "") {
	    if ($value == "") {
	      echo "<TD $width valign=\"top\"><I>$sub_on</I></TD>\n";
	    } else {
	      echo "<TD $width valign=\"top\">$value</TD>\n";
	    }
	  } else {
	    echo "<TD $width valign=\"top\"><I>".$Language->getText('global','unchanged')."</I></TD>\n";
	  }
	  continue;
	  
	  //SUBMITTED_BY
	} else if ($parsed_labels[$c] == $submitted_by_field->getLabel()) {
	  if ($this->aid_column == -1 || $aid == "") {
	    if ($value == "") {
	      echo "<TD $width valign=\"top\"><I>$sub_user_name</I></TD>\n";
	    } else {
	      echo "<TD $width valign=\"top\">$value</TD>\n";
	    }
	  } else {
	    echo "<TD $width valign=\"top\"><I>".$Language->getText('global','unchanged')."</I></TD>\n";
	  }
	  continue;
	}



	if ($value != "") {
	  
	  //FOLLOW_UP COMMENTS
	  if ($parsed_labels[$c] == $this->lbl_list['follow_ups']) {
	    unset($parsed_comments);
	    $this->clearError();
	    $art_id = (($this->aid_column != -1 && $aid != "") ? $aid : "0");
	    if ($this->parseFollowUpComments($data[$c],$parsed_comments,$art_id,true)) {
	      if (count($parsed_comments) > 0) {
		echo '<TD $width valign="top"><TABLE>';
		echo '<TR class ="boxtable"><TD class="boxtitle">'.$Language->getText('tracker_import_utils','date').'</TD><TD class="boxtitle">'.$Language->getText('global','by').'</TD><TD class="boxtitle">'.$Language->getText('tracker_import_utils','type').'</TD><TD class="boxtitle">'.$Language->getText('tracker_import_utils','comment').'</TD></TR>';
		for ($d=0; $d < count($parsed_comments); $d++) {
		  $arr = $parsed_comments[$d];
		  echo '<TR class="'.util_get_alt_row_color($d).'">';
		  echo "<TD $width>".$arr['date']."</TD><TD $width>".$arr['by']."</TD><TD $width>".$arr['type']."</TD><TD $width>".$arr['comment']."</TD>";
		  echo "</TR>\n";
		}
		echo "</TABLE></TD>";
	      } else {
		echo "<TD $width align=\"center\">-</TD>\n";
	      }
	    } else {
	      echo "<TD $width><I>".$Language->getText('tracker_import_utils','comment_parse_error',$this->getErrorMessage())."</I></TD>\n";
	    }
	    
	    //DEFAULT
	  } else {
	    echo "<TD $width valign=\"top\">$value</TD>\n";
	  }
	  
	  
	} else {
	  
	  if ($parsed_labels[$c] == $aid_field->getLabel()) {
	    echo "<TD $width valign=\"top\"><I>".$Language->getText('tracker_import_utils','new')."</I></TD>\n";
	    
	    //DEFAULT
	  } else {
	    echo "<TD $width  valign=\"top\" align=\"center\">-</TD>\n";
	  }
	}
      }
      echo "</tr>\n";
    }
    
    echo "</TABLE>\n";
    
    echo '
        <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.$this->ath->getID().'">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$this->group->group_id.'">
        <INPUT TYPE="HIDDEN" NAME="func" VALUE="import">
        <INPUT TYPE="HIDDEN" NAME="mode" VALUE="import">
        <INPUT TYPE="HIDDEN" NAME="aid_column" VALUE="'.$this->aid_column.'">
        <INPUT TYPE="HIDDEN" NAME="count_artifacts" VALUE="'.count($artifacts_data).'">';
    
    while (list(,$label) = each($parsed_labels)) {
      echo '
        <INPUT TYPE="HIDDEN" NAME="parsed_labels[]" VALUE="'.$label.'">';
    }
    
    
    for ($i=0; $i < count($artifacts_data); $i++) {
      $data = $artifacts_data[$i];
      for ($c=0; $c < count($data); $c++) {
	echo '
        <INPUT TYPE="HIDDEN" NAME="artifacts_data_'.$i.'_'.$c.'" VALUE="'.htmlspecialchars($data[$c]).'">';
      }
    }
    
    echo '
        </FORM>';
    
  }
  
  /**
   * Import artifacts that the user has accepted from the parse report and update DB.
   *
   *
   */
  function displayImport($parsed_labels,$artifacts_data,$aid_column,$count_artifacts) {
    global $Language;

    $notify = false;
    if(user_get_preference('tracker_import_notify_'.$this->ath->getID()) == '1') {
        $notify = true;
    }

    $errors = "";
    $ok = $this->updateDB($parsed_labels,$artifacts_data,$aid_column,$errors, $notify);
    
    if ($ok) $GLOBALS['Response']->addFeedback('info', $Language->getText('tracker_import','success_import',$count_artifacts));
    else $GLOBALS['Response']->addFeedback('error', $errors);
      
    //update group history
    group_add_history('import',$this->ath->getName(),$this->group->group_id);
  }
  
    
  
  
  /**
   * Display screen showing the allowed input format of the CSV files
   *
   *
   */
  function displayShowFormat() {
    global $Language;

    // project_export_utils is using $at instead of $ath
    $at = $this->ath;
    $this->ath->header(array ('title'=>$Language->getText('tracker_import','art_import').' '.$this->ath->getID(). ' - ' . $this->ath->getName(),'pagename'=>'tracker',
			'atid'=>$this->ath->getID(),'sectionvals'=>array($this->group->getPublicName()),
			'help' => 'ArtifactImport.html'));
    $sql = $this->ath->buildExportQuery($fields,$col_list,$this->lbl_list,$this->dsc_list,$select,$from,$where,$multiple_queries,$all_queries);
    
    //we need only one single record
    $sql .= " LIMIT 1";
    
    //get all mandatory fields
    $mand_list = $this->mandatoryFields();
    
    // Add the 2 fields that we build ourselves for user convenience
    // - All follow-up comments
    // - Dependencies
    
    $col_list[] = 'follow_ups';
    $col_list[] = 'is_dependent_on';
    $col_list[] = 'add_cc';
    $col_list[] = 'cc_comment';
            
    
    $eol = "\n";
    
    $result=db_query($sql);
    $rows = db_numrows($result); 
    
    echo '<h3>'.$Language->getText('tracker_import','format_hdr'),'</h3>';
    echo '<p>'.$Language->getText('tracker_import','format_msg'),'<p>';
    
    if ($rows > 0) { 
      $record = pick_a_record_at_random($result, $rows, $col_list);
    } else {
      $record = $this->ath->buildDefaultRecord();
    }
    prepare_artifact_record($at,$fields,$atid,$record);
    display_exported_fields($col_list,$this->lbl_list,$this->dsc_list,$record,$mand_list);
    
    echo '<br><br><h4>'.$Language->getText('tracker_import','sample_cvs_file').'</h4>';
    echo build_csv_header($col_list,$this->lbl_list);
    echo '<br>';
    echo build_csv_record($col_list,$record);
    
    $this->ath->footer(array());
    
  }
  
  
  /**
   * Display screen accepting the CSV file to be parsed  
   *     
   *     
   */
  function displayCSVInput($atid,$user_id) {
    global $Language,$sys_max_size_upload;
    
    $this->ath->header(array ('title'=>$Language->getText('tracker_import','art_import').' '.$this->ath->getID(). ' - ' . $this->ath->getName(),'pagename'=>'tracker',
			'atid'=>$this->ath->getID(),'sectionvals'=>array($this->group->getPublicName()),
			'help' => 'ArtifactImport.html'));
    
    echo '<h3>'.$Language->getText('tracker_import','import_new_hdr', array(help_button('ArtifactImport.html'))).'</h3>';
    echo '<p>'.$Language->getText('tracker_import','import_new_msg',array('/tracker/index.php?group_id='.$this->group->group_id.'&atid='.$atid.'&user_id='.$user_id.'&mode=showformat&func=import')).'</p>';
    
    $_pref_notify  = user_get_preference('tracker_import_notify_'.$atid);
    $notifychecked = '';
    if($_pref_notify === '1') {
        $notifychecked = 'checked="checked"';
    }

    echo '
	    <FORM NAME="importdata" action="'.$PHP_SELF.'" method="POST" enctype="multipart/form-data">
            <INPUT TYPE="hidden" name="group_id" value="'.$this->group->group_id.'">            
            <INPUT TYPE="hidden" name="atid" value="'.$atid.'">            
            <INPUT TYPE="hidden" name="func" value="import">
            <INPUT TYPE="hidden" name="mode" value="parse">

			<table border="0" width="75%">
			<tr>
			<th> ';//<input type="checkbox" name="file_upload" value="1"> 
    echo '<B>'.$Language->getText('tracker_import','upload_file').'</B></th>
			<td> <input type="file" name="csv_filename" size="50">
                 <br><span class="smaller"><i>'.$Language->getText('tracker_import','max_upload_size',formatByteToMb($sys_max_size_upload)).'</i></span>
			</td>
			</tr>
            <tr>
              <th>
                '.$Language->getText('tracker_import','send_notifications').'
              </th>
              <td>
                <input type="checkbox" name="notify" value="ok" "'.$notifychecked.'"/>
              </td>
            </tr>';
    
    //<tr>
    //<th>OR Paste Artifact Data (in CSV format):</th>
    //<td><textarea cols="60" rows="10" name="data"></textarea></td>
    //</tr>
    echo '
                        </table>

			<input type="submit" value="'.$Language->getText('tracker_import','submit_info').'">

	    </FORM> '; 
    $this->ath->footer(array());
    
  }
}

?>
