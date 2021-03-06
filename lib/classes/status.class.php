<?php
class status
{
	private static $_status = array();
	private $status = array();
	private $path = '';
	private $url = '';
	
	private function __construct($sbas_id)
	{
	
		$this->status = array();
		
		$path = $url = false;
		
		$sbas_params = phrasea::sbas_params();
		
		if(!isset($sbas_params[$sbas_id]))
			return;
		
		$path = $this->path = GV_RootPath."config/status/".urlencode($sbas_params[$sbas_id]["host"])."-".urlencode($sbas_params[$sbas_id]["port"])."-".urlencode($sbas_params[$sbas_id]["dbname"]);
		$url = $this->url = "/status/".urlencode($sbas_params[$sbas_id]["host"])."-".urlencode($sbas_params[$sbas_id]["port"])."-".urlencode($sbas_params[$sbas_id]["dbname"]);

		$xmlpref = databox::get_structure($sbas_id);
		$sxe = simplexml_load_string($xmlpref);
		
		if($sxe)
		{
			
			foreach($sxe->statbits->bit as $sb)
			{
				$bit = (int)($sb["n"]);
				if($bit<4 && $bit>63)
					continue;
					
				$this->status[$bit]["name"] = (string)($sb);
				$this->status[$bit]["labeloff"] = (string)$sb['labelOff'];
				$this->status[$bit]["labelon"] = (string)$sb['labelOn'];
				
				$this->status[$bit]["img_off"] = false;
				$this->status[$bit]["img_on"] = false;
				
				if(is_file( $path . "-stat_".$bit."_0.gif"))
				{
					$this->status[$bit]["img_off"] = $url . "-stat_".$bit."_0.gif";
					$this->status[$bit]["path_off"] = $path . "-stat_".$bit."_0.gif";
				}
				if(is_file( $path . "-stat_".$bit."_1.gif"))
				{
					$this->status[$bit]["img_on"] = $url . "-stat_".$bit."_1.gif";
					$this->status[$bit]["path_on"] = $path . "-stat_".$bit."_1.gif";
				}
				
				$this->status[$bit]["searchable"] = isset($sb['searchable']) ? (string)$sb['searchable'] : '0';
				$this->status[$bit]["printable"] = isset($sb['printable']) ? (string)$sb['printable'] : '0';
			}
		}
		ksort($this->status);
	}
	
	public static function getStatus($sbas_id)
	{
		
		if(!isset(self::$_status[$sbas_id]))
			self::$_status[$sbas_id] = new status($sbas_id);
			
		return self::$_status[$sbas_id]->status;
	}
	
	public static function getDisplayStatus()
	{
		
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		
		$statuses = array();
		
		foreach($user->_rights_sbas as $sbas_id=>$array_rights)
		{
				$status = self::getStatus($sbas_id);
					
				$statuses[$sbas_id] = $status;
		}
		
		return $statuses;
	}
	
	public static function getSearchStatus()
	{
		
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		$statuses = array();

		foreach($user->_rights_sbas as $sbas_id=>$array_rights)
		{
				$statuses[$sbas_id] = self::getStatus($sbas_id);
		}
		
		$stats = array();
		
		foreach($statuses as $sbas_id=>$status)
		{
			
			$see_all = false;
			
			if($user->_rights_sbas[$sbas_id]['bas_modify_struct'])
				$see_all = true;
				
			foreach($status as $bit=>$props)
			{
				
				if($props['searchable'] == '0' && !$see_all)
					continue;

				$set = false;
				if(isset($stats[$bit]))
				{
					foreach($stats[$bit] as $k=>$s_desc)
					{
						if(mb_strtolower($s_desc['labelon']) == mb_strtolower($props['labelon']) && mb_strtolower($s_desc['labeloff']) == mb_strtolower($props['labeloff']))
						{
							$stats[$bit][$k]['sbas'][] = $sbas_id;
							$set = true;
						}
					}
					if(!$set)
					{
						$stats[$bit][] = array(
							'sbas'		=>	array($sbas_id),
							'labeloff'	=>	$props['labeloff'],
							'labelon'	=>	$props['labelon'],
							'imgoff'	=>	$props['img_off'],
							'imgon'		=>	$props['img_on']
						);
						$set = true;
					}
				}
				
				if(!$set)
				{
					$stats[$bit] = array(
						array(
							'sbas'		=>	array($sbas_id),
							'labeloff'	=>	$props['labeloff'],
							'labelon'	=>	$props['labelon'],
							'imgoff'	=>	$props['img_off'],
							'imgon'		=>	$props['img_on']
						)
					);
				}
			}
		}
		
		return $stats;
	}
	
	public static function getPath($sbas_id)
	{
		
		if(!isset(self::$_status[$sbas_id]))
			self::$_status[$sbas_id] = new status($sbas_id);
		
		return self::$_status[$sbas_id]->path;
	}
	
	public static function getUrl($sbas_id)
	{
		
		if(!isset(self::$_status[$sbas_id]))
			self::$_status[$sbas_id] = new status($sbas_id);
		
		return self::$_status[$sbas_id]->url;
	}
	
	public static function deleteStatus($sbas_id, $bit)
	{
		
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		if(!$user->_rights_sbas[$sbas_id]['bas_modify_struct'])
			return false;
		
		$status = self::getStatus($sbas_id);
		
		if(isset($status[$bit]))
		{
			$connbas = connection::getInstance($sbas_id);
			
			$doc = databox::get_dom_structure($sbas_id);
			if($doc)
			{
				$xpath = databox::get_xpath_structure($sbas_id);
				$entries = $xpath->query($q="/record/statbits/bit[@n=".$bit."]");

				foreach($entries as $sbit)
				{
					if($p = $sbit->previousSibling)
					{
						if($p->nodeType==XML_TEXT_NODE && $p->nodeValue=="\n\t\t")
							$p->parentNode->removeChild($p);
					}
					if($sbit->parentNode->removeChild($sbit))
					{
						$sql = 'UPDATE record SET status = status&(~(1<<'.$bit.'))';
						$connbas->query($sql);
					}
				}
				$sql = "UPDATE pref SET value='".$connbas->escape_string($doc->saveXML())."', updated_on=NOW() WHERE prop='structure'";
				if($connbas->query($sql))
				{
					$cache_appbox = cache_appbox::getInstance();
					$cache_appbox->delete('list_bases');
					cache_databox::update($sbas_id,'structure');
					
					if(self::$_status[$sbas_id]->status[$bit]['img_off'])
					{
						unlink(self::$_status[$sbas_id]->status[$bit]['path_off']);
					}
					if(self::$_status[$sbas_id]->status[$bit]['img_on'])
					{
						unlink(self::$_status[$sbas_id]->status[$bit]['path_on']);
					}
					
					unset(self::$_status[$sbas_id]->status[$bit]);
					
					return true;
				}
			}
			
		}
		
		return false;
	}
	
	public static function updateStatus($sbas_id, $bit, $properties)
	{
	
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		if(!$user->_rights_sbas[$sbas_id]['bas_modify_struct'])
			return false;
			
		$status = self::getStatus($sbas_id);
					
		$connbas = connection::getInstance($sbas_id);
		
		$doc = databox::get_dom_structure($sbas_id);
		if($doc)
		{
			$xpath = databox::get_xpath_structure($sbas_id);
			$entries = $xpath->query("/record/statbits");
			if($entries->length == 0)
			{
				$statbits = $doc->documentElement->appendChild($doc->createElement("statbits"));
			}
			else
			{
				$statbits = $entries->item(0);
			}
			
			if($statbits)
			{
				$entries = $xpath->query("/record/statbits/bit[@n=".$bit."]");
				
				if($entries->length>=1)
				{
					foreach($entries as $k=>$sbit)
					{
						if($p = $sbit->previousSibling)
						{
							if($p->nodeType==XML_TEXT_NODE && $p->nodeValue=="\n\t\t")
								$p->parentNode->removeChild($p);
						}
						$sbit->parentNode->removeChild($sbit);
					}
				}

				$sbit = $statbits->appendChild($doc->createElement("bit"));
				
				if($n = $sbit->appendChild($doc->createAttribute("n")))
				{
					$n->value = $bit;
					$sbit->appendChild($doc->createTextNode($properties['name']));
				}
				
				if($labOn = $sbit->appendChild($doc->createAttribute("labelOn")))
				{
					$labOn->value = $properties['labelon']; 
				}
				
				if($searchable = $sbit->appendChild($doc->createAttribute("searchable")))
				{
					$searchable->value = $properties['searchable']; 
				}
				 
				if($printable = $sbit->appendChild($doc->createAttribute("printable")))
				{
					$printable->value = $properties['printable']; 
				}
				 
				if($labOff = $sbit->appendChild($doc->createAttribute("labelOff")))
				{
					$labOff->value = $properties['labeloff']; 
				}
			}
			
			$sql = "UPDATE pref SET value='".$connbas->escape_string($doc->saveXML())."', updated_on=NOW() WHERE prop='structure'";
			if($connbas->query($sql))
			{
				$cache_appbox = cache_appbox::getInstance();
				$cache_appbox->delete('list_bases');
				cache_databox::update($sbas_id,'structure');
					
				self::$_status[$sbas_id]->status[$bit]["name"] = $properties['name'];	
				self::$_status[$sbas_id]->status[$bit]["labelon"] = $properties['labelon'];	
				self::$_status[$sbas_id]->status[$bit]["labeloff"] = $properties['labeloff'];	
				self::$_status[$sbas_id]->status[$bit]["searchable"] = $properties['searchable'];	
				self::$_status[$sbas_id]->status[$bit]["printable"] = $properties['printable'];	
				
				if(!isset(self::$_status[$sbas_id]->status[$bit]['img_on']))
					self::$_status[$sbas_id]->status[$bit]['img_on'] = false;
				if(!isset(self::$_status[$sbas_id]->status[$bit]['img_off']))
					self::$_status[$sbas_id]->status[$bit]['img_off'] = false;
			}
		}
		
		return false;
	}
	
	public static function deleteIcon($sbas_id, $bit, $switch)
	{
		$session = session::getInstance();
		$user = user::getInstance($session->usr_id);
		
		if(!$user->_rights_sbas[$sbas_id]['bas_modify_struct'])
			return false;
			
		$status = self::getStatus($sbas_id);
		
		$switch = in_array($switch, array('on', 'off')) ? $switch : false;
		
		if(!$switch)
			return false;
			
		if($status[$bit]['img_'.$switch])
		{
			if(isset($status[$bit]['path_'.$switch]))
				unlink($status[$bit]['path_'.$switch]);
			
			$cache_data = cache_appbox::getInstance();
			$cache_data->delete('status'.basename($status[$bit]['path_'.$switch]));

			$status[$bit]['img_'.$switch] = false;
			unset($status[$bit]['path_'.$switch]);
		}	
		return true;
	}
	
	public static function updateIcon($sbas_id, $bit, $switch, $file)
	{
		$session = session::getInstance();
						
		$user = user::getInstance($session->usr_id);
		
		if(!$user->_rights_sbas[$sbas_id]['bas_modify_struct'])
			return false;
			
		$switch = in_array($switch, array('on', 'off')) ? $switch : false;

		if(!$switch)
			return false;

		$status = self::getStatus($sbas_id);
		$url = self::getUrl($sbas_id);
		$path = self::getPath($sbas_id);
		if($file['size']<65535 && $file['error'] == UPLOAD_ERR_OK)
		{
			self::deleteIcon($sbas_id, $bit, $switch);
			$name = "-stat_".$bit."_".($switch == 'on' ? '1' : '0').".gif";
			
			if(move_uploaded_file($file["tmp_name"], $path . $name))
			{
				self::$_status[$sbas_id]->status[$bit]['img_'.$switch] = $url . $name;
				self::$_status[$sbas_id]->status[$bit]['path_'.$switch] = $path . $name;
				
				
				$cache_data = cache_appbox::getInstance();
				$cache_data->delete('status'.basename(self::$_status[$sbas_id]->status[$bit]['path_'.$switch]));
				
				return true;
			}
		}
						
		return false;
	}
	
	public static function and_operation($stat1, $stat2)
	{
		$conn = connection::getInstance();
		
		$status = '0';
		
		$sql = 'select bin(0b'.trim($stat1).' & 0b'.trim($stat2).') as result';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$status = $row['result'];
			}
			$conn->free_result($rs);
		}
		return $status;
	}
	
	public static function or_operation($stat1, $stat2)
	{
		$conn = connection::getInstance();
		
		$status = '0';
		
		$sql = 'select bin(0b'.trim($stat1).' | 0b'.trim($stat2).') as result';
		if($rs = $conn->query($sql))
		{
			if($row = $conn->fetch_assoc($rs))
			{
				$status = $row['result'];
			}
			$conn->free_result($rs);
		}
		return $status;
	}
	
}