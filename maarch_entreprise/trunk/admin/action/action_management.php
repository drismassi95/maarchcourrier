<?php

/* Affichage */
if($mode == "list")
{
	list_show::admin_list($tab, $i, $title, 'id','action_management_controler&mode=list','action','id', true, $page_name_up, $page_name_val, $page_name_ban, $page_name_del, $page_name_add, $label_add, FALSE, FALSE, _ALL_STATUS, _STATUS_SING, $_SESSION['config']['businessappurl'].'static.php?filename=manage_action_b.gif', false, true, false, true, $what, true, $autoCompletionArray);
}
elseif($mode == "up" || $mode == "add")
{
	?><h1><img src="<?php echo $_SESSION['config']['businessappurl'];?>static.php?filename=manage_action_b.gif" alt="" />
				<?php
				if($mode == "up")
				{
					echo _MODIFY_STATUS;
				}
				elseif($mode == "add")
				{
					echo _ADD_STATUS;
				}
				?>
	</h1>
	<div id="inner_content" class="clearfix" align="center">
		<br /><br />
	<?php
	if($state == false)
		echo "<br /><br /><br /><br />"._THE_ACTION.' '._UNKNOWN."<br /><br /><br /><br />";
	else
	{?>
		<form name="frmaction" id="frmaction" method="post" action="<?php echo $_SESSION['config']['businessappurl']."index.php?display=true&admin=action&page=action_management_controler&mode=".$mode;?>" class="forms addforms">
			<input type="hidden" name="display" value="true" />
			<input type="hidden" name="admin" value="action" />
			<input type="hidden" name="page" value="action_management_controler" />
			<input type="hidden" name="mode" value="<?php echo $mode;?>" />
					
			<input type="hidden" name="order" id="order" value="<?php echo $_REQUEST['order'];?>" />
			<input type="hidden" name="order_field" id="order_field" value="<?php echo $_REQUEST['order_field'];?>" />
			<input type="hidden" name="what" id="what" value="<?php echo $_REQUEST['what'];?>" />
			<input type="hidden" name="start" id="start" value="<?php echo $_REQUEST['start'];?>" />
			
			<p>
				<label for="label"><?php echo _DESC; ?> : </label>
				<input name="label" type="text"  id="label" value="<?php echo functions::show($_SESSION['m_admin']['action']['LABEL']); ?>"/>
			</p>
			<?php if($_SESSION['m_admin']['action']['IS_SYSTEM']  == 'Y')
			{
				echo '<div class="error">'._DO_NOT_MODIFY_UNLESS_EXPERT.'</div><br/>';
			}?>
			<p>
				<label for="status"><?php echo _ASSOCIATED_STATUS; ?> : </label>
				<select name="status" id="status">
					<option value=""><?php echo _CHOOSE_STATUS;?></option>
					<?php
					for($i=0; $i<count($arr_status);$i++)
					{
						?><option value="<?php echo $arr_status[$i]['id'];?>" <?php if($_SESSION['m_admin']['action']['ID_STATUS'] == $arr_status[$i]['id']) { echo 'selected="selected"';}?>><?php echo $arr_status[$i]['label'];?></option><?php
					}
					?>
				</select>
			</p>
			<p>
				<label for="action_page"><?php echo _ACTION_PAGE;?> : </label>
				<select name="action_page" id="action_page">
					<option value=""><?php echo _NO_PAGE;?></option>
					<?php for($i=0; $i< count($_SESSION['actions_pages']); $i++)
					{
					?><option value="<?php echo $_SESSION['actions_pages'][$i]['ID'];?>" <?php if($_SESSION['actions_pages'][$i]['ID'] == $_SESSION['m_admin']['action']['ACTION_PAGE']){ echo 'selected="selected"';}?> ><?php echo $_SESSION['actions_pages'][$i]['LABEL'];?></option><?php
				}?>
				</select>
			</p>
            <p>
				<label for="history"><?php echo _ACTION_HISTORY; ?> : </label>
				<input type="radio"  class="check" name="history" value="Y" <?php if($_SESSION['m_admin']['action']['HISTORY'] == 'Y'){ echo 'checked="checked"';}?> /><?php echo _YES;?>
				<input type="radio"  class="check" name="history" value="N" <?php if($_SESSION['m_admin']['action']['HISTORY'] == 'N'){ echo 'checked="checked"';}?>/><?php echo _NO;?>
			</p>
			<p class="buttons">
				<?php
			if($mode == "up")
			{
				?>
					<input class="button" type="submit" name="Submit" value="<?php echo _MODIFY_ACTION; ?>" />
				<?php
			}
			elseif($mode == "add")
			{
				?>
					<input type="submit" class="button"  name="Submit" value="<?php echo _ADD_ACTION; ?>" />
				<?php
				}
				?>
			   <input type="button" class="button"  name="cancel" value="<?php echo _CANCEL; ?>" onclick="javascript:window.location.href='<?php echo $_SESSION['config']['businessappurl'];?>index.php?page=action_management_controler&amp;mode=list&amp;admin=action';"/>
			</p>
		</form >
		<div class="infos"><?php echo _INFOS_ACTIONS;?></div>
	<?php
	}
}

