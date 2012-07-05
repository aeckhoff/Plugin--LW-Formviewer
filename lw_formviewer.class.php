<?php

/**************************************************************************
*  Copyright notice
*
*  Copyright 2012 Logic Works GmbH
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*  http://www.apache.org/licenses/LICENSE-2.0
*  
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License.
*  
***************************************************************************/

class lw_formviewer extends lw_plugin
{
	function __construct()
	{
		parent::__construct();
	}
	
	function buildPageOutput()
	{
        $inAuth = lw_in_auth::getInstance();
        if (!$inAuth->isLoggedIn() && !lw_registry::getInstance()->getEntry("auth")->isLoggedIn()) {
            return "<!-- formview needs a logged in user -->";
        }
		$this->id = $this->params['id'];
		$this->templateId = $this->params['template_id'];
		return $this->showCSV();
	}
	
	private function showCSV()
	{
		$data = $this->getData();
		$output = "";
		
		if (empty($data)) {
       		$output .= "<div>Keine CSV-Daten gefunden.</div>";
       	} 
       	else {
       		if ($this->templateId>0) {
                $showPage = new agent_showpage();
                $template = $showPage->getPageView($this->templateId, true);
                $template = html_entity_decode($template, ENT_QUOTES);
                $template = str_replace('&apos;', "'", $template);        
       		}
       		else {
		        $template = "<ul>\n";
		        $template.= "<!-- lw:blockstart list -->";
		        $template.= "    <li><!-- lw:var id --></li>\n";
		        $template.= "<!-- lw:blockend list -->";
		        $template.= "</ul>\n";
		    }
       		$output .= $this->fillTemplate($template, $data);
       	}
		return $output;
	}

    private function getData()
	{
		$this->db->setStatement("SELECT id FROM t:lw_forms_entry WHERE form_id = :pid ORDER BY lw_first_date");
       	$this->db->bindParameter('pid','i', $this->id);
       	$results = $this->db->pselect();
       	
       	$this->db->setStatement("SELECT DISTINCT(lw_key) FROM t:lw_forms_data WHERE form_id = :pid");
       	$this->db->bindParameter('pid','i', $this->id);
       	$key_results = $this->db->pselect();
       	
       	$mapping = array();
       	foreach($key_results as $kr) {
		    $key = str_replace('___lw_form_element_','',$kr['lw_key']);
		    $key = str_replace('___','',$key);
		    $temp = intval($key);   			
   			$mapping[$kr['lw_key']]	= $temp;
       	}

       	$data = array();
       	foreach($results as $result) {
       		$entry_id = $result['id'];
       		$this->db->setStatement("SELECT * FROM t:lw_forms_data WHERE entry_id = :eid AND form_id = :pid");
       		$this->db->bindParameter('pid','i', $this->id);
       		$this->db->bindParameter('eid','i', $entry_id);
       		$entries = $this->db->pselect();
       	
       		if (!empty($entries)) {
       			$entity = array();
       			foreach($entries as $entry) {
       				$entity[$mapping[$entry['lw_key']]] = $entry['lw_value'];
       			}
       			$entity['id'] = $entry_id;
       			$data[] = $entity;
       		}
       		
       	}
       	return $data;
	}

	private function fillTemplate($template, $data)
	{
		$tpl = new lw_te($template);
        $btemplate = $tpl->getBlock("list");
		foreach($data as $entry) {
            $btpl = new lw_te($btemplate);
			foreach($entry as $key => $value) {
                $btpl->reg('field_'.$key, $value);
			}
			$btpl->reg('id', $entry['id']);
            $bout.=$btpl->parse();
		}
		$tpl->putBlock("list", $bout);
		return $tpl->parse();
	}
}
