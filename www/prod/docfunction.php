<?php

require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
phrasea::headers();
require(GV_RootPath."lib/index_utils2.php");

$request = httpRequest::getInstance();
$parm = $request->get_parms("act", "lst", "SSTTID");

	
$lng = isset($session->locale)?$session->locale:GV_default_lng;

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	if(!($ph_session = phrasea_open_session((int)$ses_id, $usr_id)))
	{
		header("Location: /login/?err=no-session");
		exit();
	}
}
else
{
	header("Location: /login/");
	exit();
}
					

?>
<html lang="<?php echo $session->usr_i18n;?>">
	<head>
		<base target="_self">
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/jquery-ui.css" />
		<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css')?>/prodcolor.css" />

		<script type="text/javascript">

		function getElementsByClass(cName,elType){var rtab=new Array();var tabTmp=new Array();tabTmp=document.getElementsByTagName(elType);var j=0;var X=tabTmp.length;for(i=0;i<X;i++){if(tabTmp[i].className==cName)
		{rtab[j]=tabTmp[i];j++;}}
		return rtab;}

		
		function selectGal(sbasid)
		{
			var els = getElementsByClass('select'+sbasid,'select');
			var X = els.length;
			var opt = document.getElementById('select'+sbasid).options[document.getElementById('select'+sbasid).selectedIndex].value;
			var dis = false;
			if(opt != '0')
				dis = true;
			
			for(var i=0;i!=X;i++)
			{
				els[i].disabled = dis; 
			}
		}
		
		function validChgType()
		{
			var els = getElementsByClass('sbasSelect','select');
			var X = els.length;
			
			var out = '';
			for(var i=0;i!=X;i++)
			{
				var gal = false;
				if(els[i].options[els[i].selectedIndex].value != '0')
				{
					gal = true;
				}

					var imgs = getElementsByClass('select'+els[i].id.substr(6),'select');
					var Y = imgs.length;
					for(var j=0;j!=Y;j++)
					{
						if(gal)
							out += imgs[j].id+'='+els[i].options[els[i].selectedIndex].value+";";
						else
							out += imgs[j].id+'='+imgs[j].options[imgs[j].selectedIndex].value+";";
					}

			}
			document.getElementById('typelst').value = out;
			document.forms.formtypedoc.submit();
		}
		

	
function doChgStat(do_it)
{
	if(do_it)
	{
		var sbases = document.getElementsByName('presa');
		var A = sbases.length;
		
		for(var h=0;h!=A;h++)
		{
			calcHex(sbases[h].id.substring(4));
		}
		
		if(document.getElementById("cc_chg_stat_son"))
			document.forms.formstatus.chg_status_son.value = document.getElementById("cc_chg_stat_son").getAttribute('status'); 

		var sbOr = document.getElementsByName('preso');
		var sbAnd = document.getElementsByName('presa');
		
		var X = sbOr.length;
		var Y = sbAnd.length;
		
		var outOr='';
		var outAnd='';
		
		for(var i=0;i!=X;i++)
		{
			outOr += outOr!=''?';':'';
			outOr += sbOr[i].id.substring(4)+'_'+sbOr[i].value;
		}
		for(var j=0;j!=Y;j++)
		{
			outAnd += outAnd!=''?';':'';
			outAnd += sbAnd[j].id.substring(4)+'_'+sbAnd[j].value;
		}
		document.getElementById('MSKA').value = outAnd;
		document.getElementById('MSKO').value = outOr;
		document.formstatus.submit();
	}
	else
		parent.hideDwnl();
}

var majImg=new Array();

function calcHex(sbas)
{
	var t_and = new Array();
	var t_or = new Array();
	for(bit=0; bit<64; bit++)
	{
		t_and[bit] = bit<4 ? 1 : 0;	// on peut clear tous les bits non nommes, sauf les 4 premiers reserves
		t_or[bit] = 0;
	}
	var c = document.getElementsByName("cca"+sbas);
	var bit;
	var status;
	
	for(i=0; i<c.length; i++)
	{
		bit    = c[i].getAttribute('bit');
		status = c[i].getAttribute('status');
		t_and[bit] = 1;
		if(status=="0")
			t_and[bit] = 0;
		else
			if(status=="1")
				t_or[bit] = 1;
	}
	
	t_hex = new Array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f");
	bit = 0;
	s_a = s_o = "";
	for(q=0; q<16; q++)
	{
		v_a = v_o = 0;
		for(b=0; b<4; b++)
		{
			v_a |= t_and[bit]<<b;
			v_o |= t_or[bit]<<b;
			bit++;
		}
		s_a = t_hex[v_a] + s_a;
		s_o = t_hex[v_o] + s_o;
	}

	document.getElementById('mska'+sbas).value = "0x" + s_a;
	document.getElementById('msko'+sbas).value = "0x" + s_o;
}
function evt_clk_stat(ccoch)
{
	var bit = ccoch.getAttribute('bit');
	var sbas = ccoch.getAttribute('sbas');
	switch(ccoch.getAttribute('status'))
	{
		case "0":		// decoche -> coche
			ccoch.setAttribute('status', "1");
			document.getElementById("linkoff_"+sbas+'_'+bit).setAttribute('status', "0");
			self.setTimeout("document.getElementById('cc_on_"+sbas+"_"+bit+"').src = '/skins/icons/ccoch1.gif'", 10);
			self.setTimeout("document.getElementById('cc_off_"+sbas+"_"+bit+"').src = '/skins/icons/ccoch0.gif'", 10);
			break;
		case "1":		// coche -> decoche
		case "2":		// grise -> decoche
			ccoch.setAttribute('status', "0");
			document.getElementById("linkoff_"+sbas+'_'+bit).setAttribute('status', "1");
			self.setTimeout("document.getElementById('cc_on_"+sbas+"_"+bit+"').src = '/skins/icons/ccoch0.gif'", 10);
			self.setTimeout("document.getElementById('cc_off_"+sbas+"_"+bit+"').src = '/skins/icons/ccoch1.gif'", 10);
			break;
	}
	calcHex(sbas);
}
function evt_clk_stat_inv(ccoch)
{
	evt_clk_stat(document.getElementById("linkon_"+ccoch.getAttribute('sbas')+'_'+ ccoch.getAttribute('bit') ) );
}


function chksonstatus(obj)
{
	if(obj.getAttribute('status')=="1")
	{
		obj.setAttribute('status', "0");
		obj.src = "/skins/icons/ccoch0.gif";
	}
	else
	{
		obj.setAttribute('status', "1");
		obj.src = "/skins/icons/ccoch1.gif";
	}
	return false;
}

		</script>
		<script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.4.4.js,include/jslibs/jquery-ui-1.7.2.js,include/jquery.p4.modal.js"></script>
	</head>
<?php
		
	$usrRight = null;
	
	$ndocs = null;
	$tbits = null;
	$nrecs = null;
	$nsb = null;
	$ndefined = null;
	$nbgrouping = null ;	
	$sbasSet = null;
	
	$tmp_lst = null;


	$basdst2baslocal = NULL;	// relation basDistante (en fct du sbas) vers bas locale 
	$sbasNames = NULL;
	foreach($ph_session['bases'] as $onebase)
	{					 
		$sbasNames[$onebase['sbas_id']] = $onebase['dbname'];
		foreach($onebase['collections'] as $oneColl)
		{
			$basdst2baslocal[$onebase['sbas_id']][$oneColl['coll_id']] = $oneColl['base_id'];			
		}
	}

	$conn = connection::getInstance();

	$sql = 'SELECT bu.base_id,bu.chgstatus FROM (usr u, basusr bu) WHERE u.usr_id="'.$conn->escape_string($usr_id).'" AND bu.usr_id = u.usr_id';
	if($rs = $conn->query($sql))
	{
		while($row = $conn->fetch_assoc($rs))
		{
			$usrRight[$row["base_id"]] = $row["chgstatus"];
		}			
		$conn->free_result($rs);
	}
	
	
	
	if($parm['SSTTID']!='' && ($parm['lst']==null || $parm['lst']==''))
	{
		// WARNIING : on demande le print d'un element de la zone temporaire
		$continu = true ;
		$parm['lst'] = ';';
		$sql = 'SELECT * FROM (ssel LEFT JOIN sselcont ON ( ssel.ssel_id=sselcont.ssel_id AND ssel.ssel_id="'.$conn->escape_string($parm['SSTTID']).'") )' .
				' WHERE ssel.ssel_id="'.$conn->escape_string($parm['SSTTID']).'" AND usr_id="' . $conn->escape_string($usr_id).'" ' ;
			$sql = 'SELECT s.*, c.*, sb.*, u.mask_and, u.mask_xor' .
		' FROM ssel s, sselcont c, sbas sb, bas b, basusr u' .
		' WHERE (s.usr_id="'.$conn->escape_string($usr_id).'"' .
		' OR (s.public="1" AND s.pub_restrict="0")' .
		' OR (s.public="1" AND s.pub_restrict="1" AND c.base_id IN' .
		' (SELECT base_id FROM basusr WHERE usr_id = "'.$conn->escape_string($usr_id).'" AND actif = "1"))' .
		' ) AND s.ssel_id="'.$conn->escape_string($parm["SSTTID"]) .'" AND s.ssel_id = c.ssel_id' .
		' AND b.base_id = c.base_id AND b.sbas_id = sb.sbas_id AND u.usr_id = "'.$conn->escape_string($usr_id).'" AND u.base_id = b.base_id' .
		' ORDER BY c.ord asc, sselcont_id DESC';

		if($rs = $conn->query($sql))
		{
			while( ($row=$conn->fetch_assoc($rs)) && $continu )
			{ 
					$parm['lst'] .= $row['base_id'] . '_' . $row['record_id'].';' ;
			}
			$conn->free_result($rs);
		}
		
		
	}
	elseif($parm['SSTTID']!='' && $parm['lst']!=null && $parm['lst']!='')
	{
		
	}
	
	$lst = liste::filter(explode(';',$parm['lst'] ));	

	foreach($lst as $basrec)
	{
		if($basrec && count($basrec)==2)
		{
			$sbasid = phrasea::sbasFromBas($basrec[0]);
			if(!isset($ndocs[$sbasid]))
				$ndocs[$sbasid] = 0;
			$ndocs[$sbasid]++;
			
		}
	}
	
	$parm['lst'] = implode(';',$lst);	
	$tmpbasrec = liste::addType($lst);

	$types = null;
	$baseprefs = null;
	
	$dstatus = status::getDisplayStatus();
	
	foreach($tmpbasrec as $onebasrec)
	{
		if($onebasrec!='')
		{
			// on va regarder le nb de sustitution 
			$basrec =  explode('_',$onebasrec);
			if(sizeof($basrec)==3)
			{
				$sbasid = phrasea::sbasFromBas($basrec[0]);


					
				// on verifie que on a le droits de changer les status sur les collections des documents droppe

					if(!isset($sbasSet[$sbasid]))
					{
						$baseprefs[$sbasid] = databox::get_sxml_structure($sbasid);
						$types[$sbasid] = null;
						$tmp_lst[$sbasid] = '';
						
						$sbasSet[$sbasid] = true;
						$tbits[$sbasid] = isset($dstatus[$sbasid]) ? $dstatus[$sbasid] : array();
						foreach($tbits[$sbasid] as $bit=>$values)
							$tbits[$sbasid][$bit]['nset'] = 0;
						$nrecs[$sbasid] = 0;
						
						$nbgrouping[$sbasid] = 0;
					}
				
					if(!isset($types[$sbasid][$basrec[2]]))
						$types[$sbasid][$basrec[2]] = null;
					
					$types[$sbasid][$basrec[2]][] = $basrec[0].'_'.$basrec[1];
					
					if( function_exists("phrasea_isgrp") && phrasea_isgrp($ses_id, $basrec[0], $basrec[1]))
					{
						$nbgrouping[$sbasid]++;
					}
					
					$nrecs[$sbasid]++;
	
					$sta = strrev(phrasea_status($ses_id, (int)$basrec[0], (int)$basrec[1]));
					foreach($tbits[$sbasid] as $bit=>$values)
						$tbits[$sbasid][$bit]["nset"] += substr($sta, $bit, 1)!="0" ? 1 : 0;
						
					$tmp_lst[$sbasid] = ($tmp_lst[$sbasid]!=''?';':'').$basrec[1];
			}
		}		
	}
	
?>
	<body class="bodyprofile" onload="loaded();">
		<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
		<div id="tabs">
			
			<ul>
				<li><a href="#statut"><?php echo _('prod::proprietes:: status')?></a></li>
				<li><a href="#type"><?php echo _('prod::proprietes:: type')?></a></li>
			</ul>
			
			<div id="statut" class="tabBox">
				<form name="formstatus" id="formstatus" action="chgstatus.php" method="post">	
					<?php
				//nbre total de doc modifiables
				$nrecsum = 0;
				//nbr total de doc
				$ndocsum = 0;
				
				if($sbasSet !== null)
				{
					foreach($nrecs as $rec)
						$nrecsum += $rec;
					if($ndocs != null)
					{
						foreach($ndocs as $doc)
							$ndocsum += $doc;
					}
					else
						$ndocsum = 0;
						
					foreach($sbasSet as $sbasid=>$truth)
					{
						echo "<center><br><span style='font-weight:bold;'>".$sbasNames[$sbasid]."</span><br><br>";
	
						if($nrecs[$sbasid]==0 && $nbgrouping[$sbasid]>0)	
							echo '<div>',sprintf(_('prod::status: edition de status de %d regroupements'),$nbgrouping[$sbasid]),'</div>';
						if($nrecs[$sbasid]>0 && $nbgrouping[$sbasid]==0)
							echo '<div>',sprintf(_('prod::status: edition de status de %d documents'),$nrecs[$sbasid]),'</div>';
						
						foreach($tbits[$sbasid] as $bit=>$values)
							$tbits[$sbasid][$bit]["status"] = $values["nset"]==0 ? 0 : ($values["nset"]==$nrecs[$sbasid] ? 1 : 2);
							
//						if(isset($baseprefs[$sbasid]) && $baseprefs[$sbasid]->statbits->bit)
//						{
							$nsb[$sbasid]=count($tbits[$sbasid]);
//							foreach($baseprefs[$sbasid]->statbits->bit as $sb)
//							{
//								$bit = (int)($sb["n"]);
//								if($bit>=0 && $bit<=63)
//								{
//									$tbits[$sbasid][$bit]["name"] = (string)($sb);
//									if((string)($sb) !==null)
//										$nsb[$sbasid]++;
//									
//									$tbits[$sbasid][$bit]["labelOn"] = (string)($sb["labelOn"]);
//									$tbits[$sbasid][$bit]["labelOff"] = (string)($sb["labelOff"]);
//									
//								}
//							}
				
							if($nsb[$sbasid] > 19)
								echo "<div style='width:98%; height:240; overflow:scroll' id='dividstatus".$sbasid."'>";
							else
								echo "<div style='width:98%; overflow:hidden' id='dividstatus".$sbasid."'>";
					
							echo "<center><table cellpadding='0' cellspacing='0' id='tableidstatus".$sbasid."'>";
	
							foreach($tbits[$sbasid] as $bit=>$values)
							{
//								if($tbits[$sbasid][$bit]["name"]!==null)
//								{
									if(!isset($ndefined[$sbasid]))
										$ndefined[$sbasid] = 0;
									$ndefined[$sbasid]++;
									$inverse = ($values["status"]=="2"?"2":($values["status"]=="0"?"1":"0"));
									echo "<tr>" .
											"<td style='text-align:left;width:150px'>" .
												"<a id='linkoff_".$sbasid."_".$bit."' href='#' style='color:#404040' onclick='evt_clk_stat_inv(this)' sbas='".$sbasid."' bit='".$bit."' status='".$inverse."'>" .
													"<img width='12' height='12' id='cc_off_".$sbasid."_".$bit."' src='/skins/icons/ccoch".$inverse.".gif'>";
												if($values['img_off'])
													echo '<img src="' . $values['img_off'].'" title="'.$values['labeloff'].'" style="width:16px;height:16px;vertical-align:bottom" /> ';
									echo $values["labeloff"]."</a>".
											"</td>" .
											"<td style='text-align:left;width:150px'>" .
												"<a name='cca".$sbasid."' href='#' style='color:#404040' id='linkon_".$sbasid."_".$bit."' onclick='evt_clk_stat(this)' bit='".$bit."' sbas='".$sbasid."' status='".$values['status']."'>" .
													"<img width='12' height='12' id='cc_on_".$sbasid."_$bit' src='/skins/icons/ccoch".$values['status'].".gif'>";
									if($values['img_on'])
										echo '<img src="' . $values['img_on'].'" title="'.$values['labelon'].'" style="width:16px;height:16px;vertical-align:bottom" /> ';
									echo $values["labelon"]."</a>".
											"</td>" .
										"</tr>";
//								}		
							}
							?>
									<input type="hidden" style="width:200px" name="presa" id="mska<?php echo $sbasid?>" value="???">
									<input type="hidden" style="width:200px" name="preso" id="msko<?php echo $sbasid?>" value="???">

								</table>
							</center>
							<?php 
							$lib = $ndefined[$sbasid]>0 ? _('prod::status: remettre a zero les status non nommes') : _('prod::status: remetter a zero tous les status');
						
							if($ndefined[$sbasid]==0)
								echo _('prod::status: aucun status n\'est defini sur cette base') . "<br/>\n";
							
							echo "</div>";
								
	
					
							if($nbgrouping[$sbasid]>0)
							{
							?>
								<br>
								<table style="border:#ff0000 1px solid;">
									<tr>
										<td style="width:25px;">
												<img onclick="chksonstatus(this);" status="1" id="cc_chg_stat_son" src="/skins/icons/ccoch1.gif">
										</td>	
										<td style="width:250px; text-align:left">
										<?php echo _('prod::status: changer egalement le status des document rattaches aux regroupements')?>
										</td>
									</tr>
								</table>
							<?php
							}
						}
//					}
				}
				elseif($ndocs !== null)
				{
					foreach($ndocs as $doc)
						$ndocsum += $doc;
				}
				else
				{
					//j'ai pas du comprendre quelque chose
				}
				
				
				?>
							
					<script language="javascript" type="text/javascript">
					
		
					function loaded()
					{
						w = window.dialogArguments ? window.dialogArguments : self.opener;
						self.focus();
						<?php if($nrecsum>0 && $nrecsum<$ndocsum) 
						{
						$mess = sprintf(_('prod::status: %d documents ne peuvent avoir une edition des status'),($ndocsum-$nrecsum));
						?>
						alert("<?php echo $mess?>");	
						<?php
						}
						elseif($nrecsum==0) 
						{ 
						$mess = _('prod::status:Vous n\'avez pas les droits suffisants pour changer le status des documents selectionnes');
						?>
						alert("<?php echo $mess?>");
						parent.hideDwnl();
						<?php } ?>
						
					}
					
					</script>
					<input type="hidden" style="width:200px" name="mska" id="MSKA" value="???">
					<input type="hidden" style="width:200px" name="msko" id="MSKO" value="???">
					<input type="hidden" name="chg_status_son" value="">
					<input type="hidden" name="act" value="WORK" />
					<input type="hidden" name="lst" value="<?php echo $parm["lst"]?>" />
					<div style="margin-top : 10px;text-align:center;">
						<input type="button" class="input-button" value="<?php echo _('boutton::valider')?>" onclick="doChgStat(true);" /> 
						<input type="button" class="input-button" value="<?php echo _('boutton::annuler')?>" onclick="parent.hideDwnl();" />
					</div>
				</form>
			</div>		
			<div id="type" class="tabBox">
				<form name="formtypedoc" action="chgtype.php" method="post">	
					<div style="width:100%;">
					<?php
						$first = true;
						foreach($types as $sbas_id=>$typeBR)
						{
							if(!$first)
								echo '<hr/>';
							$first = false;
							echo '<h5>'.$sbasNames[$sbas_id]."</h5>";
							
							//check des differents types pour cette SBAS
							$connsbas = connection::getInstance($sbas_id);
							if($connsbas)
							{
								$result = $connsbas->query("SHOW COLUMNS FROM record LIKE 'type'");
								$arryEnum = false;
								if($connsbas->num_rows( $result ) > 0 )
								{
								   $row=$connsbas->fetch_assoc($result);
								   preg_match_all("/'(.*?)'/", $row['Type'], $matches);
								   $arryEnum = $matches[1];
								}
							}
							
							$selectGal = '<div style="text-align:center;"><select class="sbasSelect" id="select'.$sbas_id.'" onchange=\'selectGal("'.$sbas_id.'");\'>';
							$selectGal .= '<option value="0">'._('prod::type: appliquer a tous les documents selectionnes').'</option>';
							
							foreach($arryEnum as $option)
							{
								$selectGal .= '<option value="'.$option.'">'.$option.'</option>';
							}
							$selectGal .= '</select><br/><br/></div>';
							
							echo $selectGal;
							unset($selectGal);
							
							foreach($typeBR as $type=>$BR)
							{
								echo '<div style="float:left;width:100%;">';
								foreach($BR as $rec)
								{
									$rec2 = explode('_',$rec);
									if(sizeof($rec2)==2)
									{
										$tmpsd = phrasea_subdefs($ses_id, $rec2[0],$rec2[1]);
				
										$dis = '';
										$class = 'select'.$sbas_id;
										
										if( function_exists("phrasea_isgrp") && phrasea_isgrp($ses_id, $rec2[0], $rec2[1]))
										{
											$dis = 'disabled="disabled"';
											$class = 'selectREG'.$sbas_id;
										}
				
										$select = '<select '.$dis.' id="img'.$rec2[0].'_'.$rec2[1].'" class="'.$class.'">';
										foreach($arryEnum as $option)
										{
											$sel = '';
											if(trim($option) == $type)
												$sel = 'selected="selected"';
											
											$select .= '<option '.$sel.' value="'.$option.'">'.$option.'</option>';
										}
										$select .= '</select>';
				
										echo '<div style="with:100%;text-align:center;font-size:10px;float:left;width:100px;height:130px;">Record '.$rec2[1]."<br/>";
										$thumbnail = answer::getThumbnail($ses_id, $rec2[0],$rec2[1]);
										echo '<div style="height:60px;"><img src="'.$thumbnail['thumbnail'].'" width="'.($thumbnail['w']/3).'" height="'.($thumbnail['h']/3).'" /></div>';
										echo '<div style="height:28px;">'.$select.'</div></div>';
										flush();
									}
								}
								echo '</div>';
							}	
						}
					?>
					<input type="hidden" name="ACT" value="SEND" />
					<input type="hidden" name="typelst" id="typelst" value="" />
					<div style="margin-top : 5px;text-align:center;">
						<input type="button" class="input-button" value="<?php echo _('boutton::valider')?>" onclick="validChgType();" /> 
						<input type="button" class="input-button" value="<?php echo _('boutton::annuler')?>" onclick="parent.hideDwnl();" />
					</div>
				</form>
			</div>		
		</div>		
		
	</body>
</html>

