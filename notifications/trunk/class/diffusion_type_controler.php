<?php

/*
*   Copyright 2008-2011 Maarch
*
*   This file is part of Maarch Framework.
*
*   Maarch Framework is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   Maarch Framework is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with Maarch Framework. If not, see <http://www.gnu.org/licenses/>.
*/

/**
* @brief Contains the diffusion_type Object
* (herits of the BaseObject class)
*
* @file
* @author Loïc Vinet - Maarch
* @date $date$
* @version $Revision$
* @ingroup core
*/

//Loads the required class
try {
	require_once 'modules/notifications/class/diffusion_type.php';
	require_once 'core/class/ObjectControlerAbstract.php';
} catch (Exception $e) {
    echo $e->getMessage() . ' // ';
}

/**
 * Class for controling docservers objects from database
 */
class diffusion_type_controler
    extends ObjectControler 
    //implements ObjectControlerIF
{
    /**
     * Get event with given event_id.
     * Can return null if no corresponding object.
     * @param $id Id of event to get
     * @return event
     */
    public function getAllDiffusion() {
		core_tools::load_lang();
		$return = array();
		$xmlfile = 'modules/notifications/xml/diffusion_type.xml';
        
        $xmldiffusion = simplexml_load_file($xmlfile);
        foreach($xmldiffusion->diffusion_type as $diffusion) {
			//<id> <label> <script>	
			
			$diffusion_type = new diffusion_type();
			
			if(@constant((string) $diffusion->label)) {
				$label = constant((string)$diffusion->label);
			} else {
				$label = (string) $diffusion->label;
			}
			
			$diffusion_type->id = (string) $diffusion->id;
			$diffusion_type->label = $label;
			$diffusion_type->script = (string) $diffusion->script;
		
			$return[$diffusion_type->id] = $diffusion_type;
		}
		
        if (isset($return)) {
            return $return;
        } else {
            return null;
        }
    }
  
	public function get($type_id) {
		if ($type_id <> '') {
			$fulllist = array();
			$fulllist = $this->getAllDiffusion();
			foreach ($fulllist as $dt_id => $dt)
			{
				if ($type_id == $dt_id){
					return $dt;
				}
			}
		}
		return null;
	}
   
	public function getRecipients($templateAssocObj, $eventObj) 
	{
		$diffusionType = $this->get($templateAssocObj->diffusion_type);
		$request = 'recipients';
		require($diffusionType->script);
		return $recipients;
	}
	
	public function getAttachFor($templateAssocObj, $user_id) {
		// No attachment defined
		if($templateAssocObj->attachfor_type == '') {
			return false;
		}
		$attachforType = $this->get($templateAssocObj->attachfor_type);
		$request = 'attach';
		require($attachforType->script);
		return $attach;
	}

}

