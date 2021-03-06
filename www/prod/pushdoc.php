<?php
define('ZFONT', 'freesans');
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";
$session = session::getInstance();
require(GV_RootPath.'lib/push.api.php');
require(GV_RootPath.'lib/countries.php');

$request = httpRequest::getInstance();
$parm = $request->get_parms("lst"
					,"ACT"
					,"textmail"
					,"lstusr"
					,"nameBask"
					,"SSTTID"
					,"view_all"
					,"is_push"
					,"accuse"
					,"timValS"
					,"token"
					);

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
phrasea::headers();
					
$act = $parm['ACT'];
$parmLST = $parm['lst'];


if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	die();
		
$ctry = getCountries($lng);
	
if($act == "LOAD")
{	
	$token = random::generatePassword (16);
	$prod_datas = $session->prod;
	$prod_datas['push'][$token] = array('lst'=>array(),'usrs'=>array(),'ssel_id'=>null);
	$session->prod = $prod_datas;

	?>
<html lang="<?php echo $session->usr_i18n;?>"><link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
			<base target="_self">
			<script type="text/javascript">
			function loaded()
			{
				document.forms[0].submit();
			}
			</script>
		</head>
		<body onload="loaded();">
			<form name="formpushdoc" action="pushdoc.php" method="post">	
				<input type="hidden" name="lst" value="<?php echo $parm['lst']?>" />
				<input type="hidden" name="token" value="<?php echo $token?>" />
				<input type="hidden" name="ACT" value="STEP2" />
				<input type="hidden" name="SSTTID" value="<?php echo $parm['SSTTID']?>" />
			</form>
		</body>
	</html>
	<?php
	exit();
}


if($act == "STEP2")
{
	$num=0;
	
	if($parm['SSTTID'] != '')
	{
		$basket = basket::getInstance($parm['SSTTID']);
		$lst = array();
		foreach($basket->elements as $basket_element)
			$lst[]=$basket_element->base_id.'_'.$basket_element->record_id;
	}
	else
	{
		$lst = explode(";", $parmLST);
	}		
	
	$lst = whatCanIPush($usr_id,$ses_id,$lst);
	
	$canAdmin = whatCanIAdmin($usr_id,$ses_id);	
	$canSendHD = sendHdOk($usr_id,$ses_id,$lst);

	if(isset($lst))
	{
		$parmLST = implode(';',$lst);
		$num = count($lst);
	}
	
	if(!isset($session->prod['push'][$parm['token']]))
		return;
	
	$prod_datas = $session->prod;
	$prod_datas['push'][$parm['token']]['lst'] = $lst;
	$prod_datas['push'][$parm['token']]['usrs'] = array();
	$prod_datas['push'][$parm['token']]['ssel_id'] = $parm['SSTTID'];
	$session->prod = $prod_datas;
	
	?>
	
<html lang="<?php echo $session->usr_i18n;?>">
		<title id="doc_title"><?php echo _('action : push')?></title>
		<head>
			<link rel="icon" type="image/png" href="/favicon.ico" />
			<base target="_self">
			<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
			<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css');?>/jquery-ui.css" />
			<link type="text/css" rel="stylesheet" href="/include/minify/f=skins/prod/<?php echo user::getPrefs('css');?>/prodcolor.css" />
			<style type="text/css">
	#tabs
	{
		position:absolute;
		top:0;
		right:0;
		left:0;
		bottom:0;
	}
	.bodyprofile .ui-tabs .ui-tabs-panel{
		position:absolute;
		top:31px;
		bottom:0;
		left:0;
		right:0;
	}
	.ui-widget-content a{
		color:#1a1a1a;
	}
	table.tablesorter  tr th{
		background-image: url(/skins/icons/bg.gif);
		background-repeat: no-repeat;
		background-position: center right;
		cursor: pointer;
		background-color:#777777
	}
	table.tablesorter  tr th.SortUp {
		background-image: url(/skins/icons/desc.gif);
	}
	table.tablesorter  tr th.hover{
		background-color: #376974;
	}
	table.tablesorter  tr th.active{
		background-color: #61bcd0;
	}
	table.tablesorter tr th.SortDown {
		background-image: url(/skins/icons/asc.gif);
	}
	.filterActive{
		background-color:red;
		color:black;
	}
				
				.pushlist tr.g
				{
				    BACKGROUND-COLOR: #888888;
				}
				.pushlist tr.selected{
					background-color:red;
				}
				.pushlist{
					table-layout:fixed;
					width:100%;
					white-space:nowrap;
					}
				.pushlist tr{
					cursor:pointer;
					}
				.pushlist tr td{
					overflow:hidden;
					}
				
				.pushlist #LIGREF{
					background-color:#666666;
					}
					#alert_nbuser{
						color:red;
					}
					.boxCloser{
						color:white;
						position:relative;
						z-index:32768;
						float:right;
					}
			</style>
		<?php
		if($num == 0){
		?>
			<script type="text/javascript">
			alert("<?php echo _('Vous ne pouvez pusher aucun de ces documents')?>");
			parent.hideDwnl();
			</script>
		<?php
		die();
		}
		?>
		<script type="text/javascript" src="/include/minify/g=push"></script>
<script type="text/javascript" src="/include/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : "textareas",
		theme : "simple"
	});
</script>
	</head>
	<body class="bodyprofile">
		<div class="boxCloser" onclick="parent.hideDwnl();"><?php echo _('boutton::fermer')?></div>
		<div id="tabs">
			<ul>
				<li><a href="#Push">Push</a></li>	
				<?php
				if(count($canAdmin)>0)
				{
				?>
					<li><a href="#User_add"><?php echo _('Push::Ajout d\'utilisateur')?></a></li>
				<?php 
				}
				?>
			</ul>
			<div id="Push">
				<div style="width:100%;height:100%;overflow-x:hidden;overflow-y:auto;">
					<div style="margin-left:20px;font-style:italic;"><img style="vertical-align:middle;" src="/skins/icons/infoB.gif" /> <?php  echo sprintf(_('%d documents vont etre pushes'),$num)?></div>
					<div id='STEP_1' style="display:block;" class="STEP wizardMode">
						<table style="margin:0 auto;text-align:left;width:600px;">
							<tr class="appLauncher" onclick="onlyValid(false);">
								<td>
									<input type="button" id="pushIt" value="<?php echo _('module::DIFFUSER'); ?>"/>
								</td>
								<td style="width:40px;">
								</td>
								<td>
									<label for="pushIt" class="wizardLabel"><?php echo _('Push::unpush permet d\'envoyer un lot d\'image a des destinataires')?></label>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="height:80px;">
									<hr/>
								</td>
							</tr>
							<tr class="appLauncher" onclick="onlyValid(true);">
								<td>
									<input type="button" id="checkIt" value="<?php echo _('module::VALIDER'); ?>"/>
								</td>
								<td style="width:40px;">
								</td>
								<td>
									<label for="checkIt" class="wizardLabel"><?php echo _('Push::une validation est une demande d\'appreciation a d\'autres personnes')?></label>
								</td>
							</tr>
						</table>
					</div>
					
					<div id='STEP_2' class="STEP">
						<div>
							<div><?php echo _('Push::charger une recherche');?> <select id="searchilist"><?php echo loadILists($usr_id,$ses_id,$lng);?></select> <input style="display:none;" type="button" onclick="deleteIlist()" id="ilistremover" class="input-button" value="<?php echo _('boutton::supprimer')?>"/></div>
							<form id="search_form" name="search_form" onsubmit="specialsearch(true,1);return(false);">
								<table border="0" >
									<tr>
										<td colspan="4">
											<table border="0" cellpadding="2px" cellspacing="5px" id="filters">
												<tr class="filter">
													<td>
														<select class="operator" name='operator'>
															<option value='and'><?php echo _('Push::filtrer avec')?></option>
															<option value='except'><?php echo _('Push::filter sans')?></option>
														</select>
													</td>
													<td>
														<select class="field" style="width:130px;border:#cccccc 1px solid;font-size:10px;" name='filtre'>
															<option value='LOGIN'><?php echo _('Push::filter on login')?></option>
															<option value='NAME'><?php echo _('Push::filter on name')?></option>
															<option value='MAIL'><?php echo _('Push::filter on emails')?></option>
															<!-- <option value='COMPANY'><?php echo _('Push::filter on companies');?></option>
															<option value='FCT'><?php echo _('Push::filter on functions');?></option>
															<option value='ACT'><?php echo _('Push::filter on activities');?></option>
															<option value='LASTMODEL'><?php echo _('Push::filter on templates')?></option>-->
														</select>
													</td>
													<td>
														<select class="fieldlike" style="width:130px;border:#cccccc 1px solid;font-size:10px;" name='typ'>
															<option value='BEGIN'><?php echo _('Push::filter starts')?></option>
															<option value='CONT'><?php echo _('Push::filter contains')?></option>
															<option value='END'><?php echo _('Push::filter ends')?></option>
														</select>
													</td>
													<td>
													 <input class="fieldsearch" type="text" style="font-size:9px; width:100px;" value="" >&nbsp;
													</td>
													<td>
														<span style="font-weight:bold;font-size:12px;cursor:pointer;" onclick="removeFilter(this);">-</span>
													</td>
													<td>
														<span style="font-weight:bold;font-size:12px;cursor:pointer;" onclick="addFilter(this);">+</span>
													</td>
												</tr>
											</table>
										</td>
									
										<td style="text-align:center;">
										 <input type='button' class='input-button' value='OK' onclick='specialsearch(true,1);return(false);'/>
										 <input style="margin-top:5px;" type='button' class='input-button' value='Reset' onclick='document.forms["search_form"].reset();specialsearch(true);'/>
										</td>
									</tr>
											<?php

												$conn = connection::getInstance();
												$baslist = array(); 
												$sql = 'SELECT DISTINCT(b.base_id) FROM (bas b, basusr u)' .
														' WHERE u.usr_id="'.$conn->escape_string($usr_id).'"' .
														' AND b.base_id =u.base_id' .
														' AND u.canpush="1"' .
														' AND u.actif="1"' .
														' AND b.active="1"';
											
												if($rs = $conn->query($sql))
												{
													while($row = $conn->fetch_assoc($rs) )
													{
														$baslist[] = $conn->escape_string($row['base_id']);
													}
													$conn->free_result($rs);
												}
											
												$sql = 'SELECT DISTINCT usr.activite' .
													' FROM usr' .
													' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
													' left join demand on usr.usr_id=demand.usr_id' .
													' WHERE ((b.base_id="'.implode('" OR b.base_id="',$baslist).'"))' .
													' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
													' AND usr.model_of=0 ORDER BY usr.activite ASC' ;
												$htmlacti = '<option value="">Toutes</option>';
												if($rs = $conn->query($sql))
												{
													while($row = $conn->fetch_assoc($rs))
													{
														$htmlacti .= '<option value="'.$row['activite'].'">'.$row['activite'].'</option>';
													}
												}
												$sql = 'SELECT DISTINCT usr.fonction' .
													' FROM usr' .
													' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
													' left join demand on usr.usr_id=demand.usr_id' .
													' WHERE ((b.base_id="'.implode('" OR b.base_id="',$baslist).'"))' .
													' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
													' AND usr.model_of=0  ORDER BY usr.activite ASC' ;
												$htmlfonction = '<option value="">Toutes</option>';
												if($rs = $conn->query($sql))
												{
													while($row = $conn->fetch_assoc($rs))
													{
														$htmlfonction .= '<option value="'.$row['fonction'].'">'.$row['fonction'].'</option>';
													}
												}
												$sql = 'SELECT DISTINCT usr.pays' .
													' FROM usr' .
													' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
													' left join demand on usr.usr_id=demand.usr_id' .
													' WHERE ((b.base_id="'.implode('" OR b.base_id="',$baslist).'"))' .
													' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
													' AND usr.model_of=0 ' ;
												$disCoun = array();
												
												$ctry = getCountries($lng);
												if($rs = $conn->query($sql))
												{
													while($row = $conn->fetch_assoc($rs))
													{
														if(isset($ctry[$row['pays']]))
															$disCoun[$row['pays']] = $ctry[$row['pays']]; 
													}
												}
												
												
												$sql = 'SELECT DISTINCT usr.societe' .
													' FROM usr' .
													' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
													' left join demand on usr.usr_id=demand.usr_id' .
													' WHERE ((b.base_id="'.implode('" OR b.base_id="',$baslist).'"))' .
													' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
													' AND usr.model_of=0 ORDER BY usr.societe ASC' ;
												$htmlsocie = '<option value="">Toutes</option>';
												if($rs = $conn->query($sql))
												{
													while($row = $conn->fetch_assoc($rs))
													{
														$htmlsocie .= '<option value="'.$row['societe'].'">'.$row['societe'].'</option>';
													}
												}
												$sql = 'SELECT DISTINCT usr.lastModel' .
													' FROM usr' .
													' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
													' left join demand on usr.usr_id=demand.usr_id' .
													' WHERE ((b.base_id="'.implode('" OR b.base_id="',$baslist).'"))' .
													' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
													' AND usr.model_of=0 ORDER BY usr.lastModel ASC' ;
												$htmltemplate = '<option value="">Toutes</option>';
												if($rs = $conn->query($sql))
												{
													while($row = $conn->fetch_assoc($rs))
													{
														$htmltemplate .= '<option value="'.$row['lastModel'].'">'.$row['lastModel'].'</option>';
													}
												}
	
											?>
									<tr>
										<td colspan="4">
											<?php echo _('push:: Filtrez aussi sur : '); ?>
											<a href="#" class="filtermultipays" onclick="addFilterMulti('pays',this)"><?php echo _('push::(filtrez aussi sur) pays '); ?></a>
											<a href="#" class="filtermultisociete" onclick="addFilterMulti('societe',this)"><?php echo _('push::(filtrez aussi sur) societes '); ?></a>
											<a href="#" class="filtermultiactivite" onclick="addFilterMulti('activite',this)"><?php echo _('push::(filtrez aussi sur) activites '); ?></a>
											<a href="#" class="filtermultifonction" onclick="addFilterMulti('fonction',this)"><?php echo _('push::(filtrez aussi sur) fonctions '); ?></a>
											<a href="#" class="filtermultitemplate" onclick="addFilterMulti('template',this)"><?php echo _('push::(filtrez aussi sur) modeles '); ?></a>
										<a href="#" class="filtermultilist" onclick="addFilterMulti('lists',this)"><?php echo _('push::(filtrez aussi sur) listes '); ?></a>
										</td>
									</tr>
								</table>
								<div style="float:left;width:100%;">
									<div style="float:left;width:220px;display:none;" id="filter_multi_pays">
											<div><?php echo _('push:: dans les pays '); ?></div>
											<select id="searchcountry" class="fieldcountry" style="width:200px;" size=8 multiple>
												<option value="">Tous les pays</option>
											<?php
												foreach($disCoun as $k=>$v)
													echo '<option value="'.$k.'">'.$v.'</option>'
											
											?>
											</select>
									</div>
									<div style="float:left;width:220px;display:none;" id="filter_multi_activite">
											<div><?php echo _('push::(filtrez aussi sur) activites '); ?></div>
											<select id="searchactivite" class="fieldactivity" style="width:200px;" size=8 multiple>
												<?php echo $htmlacti?>
											</select>
									</div>
									<div style="float:left;width:220px;display:none;" id="filter_multi_fonction">
											<div><?php echo _('push::(filtrez aussi sur) fonctions '); ?></div>
											<select id="searchfunction" class="fieldfunction" style="width:200px;" size=8 multiple>
												<?php echo $htmlfonction?>
											</select>
									</div>
								<div style="float:left;width:220px;display:none;" id="filter_multi_lists">
										<div><?php echo _('push::(filtrez aussi sur) listes '); ?></div>
										<select id="searchlist" name="searchlist[]" class="fieldlist" style="width:200px;" size=8 multiple>
											<?php echo loadLists($usr_id,$ses_id,$lng)?>
										</select>
										<input type="button" class="input-button" value="<?php echo _('boutton::supprimer');?>" id="listDeleter"/>
								</div>
								<div style="float:left;width:220px;display:none;" id="filter_multi_societe">
											<div><?php echo _('push::(filtrez aussi sur) societes '); ?></div>
											<select id="searchsociete" class="fieldsociete" style="width:200px;" size=8 multiple>
												<?php echo $htmlsocie?>
											</select>
									</div>
									<div style="float:left;width:220px;display:none;" id="filter_multi_template">
											<div><?php echo _('push::(filtrez aussi sur) modeles '); ?></div>
											<select id="searchtemplate" class="fieldtemplate" style="width:200px;" size=8 multiple>
												<?php echo $htmltemplate?>
											</select>
									</div>
								
								</div>
							</form>
							<div style="float:left;width:100%;">
										 <?php echo _('push:: enregistrer cette recherche '); ?>
										 <img title="<?php echo _('push:: enregistrez cette recherche et re-executez la a tout moment'); ?>" style="vertical-align:middle;" src="/skins/icons/infoB.gif" />
										 <input id="INTELL_LIST" type="text" value="" > <img onclick="saveiList();" src="/skins/icons/save.png" />
							</div>
						</div>
						<div style="float:left;width:100%">					
								<div id="search_list_wrapper" style='margin:20px;'>
								</div>
							<div style="margin-top:5px;text-align:right;" class="wizardMode">
								<input type="button" class='input-button' value="<?php echo _('wizard:: previous step');?>" onclick="previousStep();"/> 
								<input type="button" class='input-button' value="<?php echo _('wizard:: next step');?>" onclick="nextStep();"/> 
							</div>
						</div>
					</div>
							
							
							
							
							
						
					<form name="formpushdoc" action="pushdoc.php" method="post">	
						<div id='STEP_3' class="STEP">
							<div>
									<table border="0" style="margin:0 auto;">
										<tr id='BasketTitle'>
											<td align="right" valign="middle"><label for='nameBask' style='float:none;'><?php echo _('Push::nom du panier a creer')?> :</label></td>
											<td align="left" valign="top"><input name="nameBask" id="nameBask" style="width:400px" type="text" value=""></td>
										</tr>
										<tr id='timeVal' style='display:none;'>
											<td align="right" valign="middle"><label for='timValS' style='float:none;'><?php echo _('Push::duree de la validation')?></label></td>
											<td align="left" valign="top">
												<select id="timValS" name="timValS">
												<?php
												for($i=0;$i!=21;$i++)
												{	
													$sel="";
													if($i == GV_val_expiration)
														$sel = "selected='selected'";
													$n = $i;
													if($i == 0)
														$n=_('Push::duree illimitee');
													echo "<option ".$sel." class='TIME_VAL' value='".$i."'>".$n."</option>";
												}
												?>
												</select>
											</td>
										</tr>
										<tr id='viewOpt' style='display:none;'>
											<td align="right" valign="top"><label for='view_all' style='float:none;'><?php echo _('push:: Permettre aux utilisateurs de voir le choix des autres')?> </label></td>
											<td align="left" valign="top" ><input value="1" type="checkbox" name="view_all" id="view_all"  /></td>
										</tr>
										<tr>
											<td align="right" valign="top"><label for='accuse' style='float:none;'><?php echo _('Accuse de reception')?> </label></td>
											<td align="left" valign="top" ><input name="accuse" id="accuse" type="checkbox" value="1"/></td>
										</tr>
										<tr>
											<td align="right" valign="top"><label for='textmail' style='float:none;'><?php echo _('phraseanet:: contenu du mail')?> </label></td>
											<td align="left" valign="top" ><textarea name="textmail" id="textmail" rows="8" cols="170" style="height:300px;width:400px;"></textarea></td>
										</tr>
									</table>
							</div>
							<div style="text-align:center;">
								<input class="wizardMode" type="button" class='input-button' value="<?php echo _('wizard:: previous step');?>" onclick="previousStep();"/> 
								<input type="button" onclick="doSendpush(this)" value="<?php echo _('boutton::envoyer')?>" />
								<img src="/skins/icons/loader-push.gif" id="push_sending" style="visibility:hidden">
								<input type="button" onclick="parent.hideDwnl();" value="<?php echo _('boutton::annuler')?>" />
							</div>
						</div>
						<input type="hidden" id="ACT" name="ACT" value="SEND" />
						<input type="hidden" id="is_push" name="is_push" value="1" />
						<input type="hidden" id="token" name="token" value="<?php echo $parm['token']?>" />
					</form>
					<input type="hidden" id="SSTTID" value="<?php echo $parm['SSTTID']?>" />
				</div>	
			</div>		
			<?php
			if(count($canAdmin)>0)
			{
			?>
				<div id="User_add">
				
								<div id="DIV_ADDUSER">
									<span style=""><?php echo _('prod::push: ajouter un utilisateur')?></span> : <input type="text" value="" id="NEW_MAIL" />
									<input type="button" value="<?php echo _('boutton::valider')?>" onclick="adduserDisp();"/>
					
					
					
					
					
													<div id='ADD_USR' style="xvisibility:hidden;">
		
													</div>
					
					
					
								</div>
				</div>		
			<?php
			}
			?>
		</div>		
	</body>
</html>
<?php
}
############## END ACT STEP2 ######################


############## ACT SEND ######################
if($act == "SEND")
{	
	if(!isset($session->prod['push'][$parm['token']]))
		exit();
	?>
	
<html lang="<?php echo $session->usr_i18n;?>">
		<head>
			<link rel="icon" type="image/png" href="/favicon.png" />
			<link REL="stylesheet" TYPE="text/css" HREF="/include/minify/f=skins/prod/000000/prodcolor.css" />
		</head>
		<body>
	<?php
	if($parm['is_push']==1)
	{	
		$conn = connection::getInstance();
	
		$parmLST = $session->prod['push'][$parm['token']]['lst'];

		$parmLST = whatCanIPush($usr_id,$ses_id,$parmLST);
		
		$lstUsrs = $session->prod['push'][$parm['token']]['usrs'];
		
		$users = array();
		foreach($lstUsrs as $usr=>$right)
		{
			$users[$usr]=array('canHD'=>(in_array($right['HD'],array('0','1'))?$right['HD']:0))	;
		}
		
		
		$push = pushIt($usr_id,$ses_id,$parm['nameBask'],$parmLST,$users,$parm["textmail"],$lng, $parm['accuse']);
		$nbchu = $push['nbchu'];
		$my_link = $push['mylink'];
		$Endusers = $push['users'];
		
		$lstbySbas = array();
		foreach($parmLST as $br)
		{	
			$br = explode('_',$br);
			$lstbySbas[phrasea::sbasFromBas($br[0])][] = $br[1];
		}
		
		foreach($lstbySbas as $sbas=>$lst)
		{
			foreach($lst as $record)
				foreach($Endusers as $u)
					answer::logEvent($sbas,$record,'push',$u,'');
				
		}
		
		echo "<div style='text-align:center;margin-top:20px;'>".sprintf(_('Push:: %d paniers envoyes avec success'),$nbchu)."</div><div style='text-align:center;margin-top:20px;'>".'</div>' ;
		$prov =  GV_ServerName ;
					
		if(isset($my_link) && strlen($my_link)>4)
		{
			echo "<div style='text-align:center;margin-top:20px;'><a href='".$my_link."' target='_blank'>"._('Push:: acces direct au panier envoye')."</a></div>" ;
		}
		?>
		<div style='text-align:center;margin-top:20px;'><input type="button" onclick="parent.hideDwnl();" value="<?php echo _('boutton::fermer')?>" /></div>
		<?php
	}

############## SI C'EST UN PUSH VALIDATOR ######################
	if($parm['is_push']==0)
	{	
		$conn = connection::getInstance();
			
			
		$justCreated = false;
		
		$ssel_id = $session->prod['push'][$parm['token']]['ssel_id'];
		
		if(!$ssel_id || trim($ssel_id) == '')
		{
			$lst = array_reverse($session->prod['push'][$parm['token']]['lst']);
			
			$basket = new basket();
			$basket->name = $parm["nameBask"];
			$basket->save();
			$basket->push_list($lst, false);
			$basket->flatten();
			$ssel_id = $basket->ssel_id;
			
			$outinfos = _('prod::push: votre nouveau panier a ete cree avec succes ; il contient vos documents de validation');
		}
		else
		{
			$basket = basket::getInstance($ssel_id);
			
			if($basket->is_grouping)
			{
                          $basket = clone $basket;
                          $ssel_id = $basket->ssel_id;
			}
			
			$basket->flatten();
		}
		
	
		$my_link = '';
		if($ssel_id && is_numeric($ssel_id))
		{
			$lstUsrs = $session->prod['push'][$parm['token']]['usrs'];
			$users = array();
			foreach($lstUsrs as $usr=>$right)
			{
				$users[$usr]=array('canHD'=>(in_array($right['HD'],array('0','1'))?$right['HD']:'0'),'canRate'=>'0','canAgree'=>'1','canSeeOther'=>($parm['view_all'] == '1' ? '1' : '0'),'canZone'=>'0');
			}
			
			if(!array_key_exists($session->usr_id, $lstUsrs))
				$users[$session->usr_id]=array('canHD'=>'0','canRate'=>'0','canAgree'=>'1','canSeeOther'=>'1','canZone'=>'0');

			$push = pushValidation($usr_id,$ses_id,$ssel_id,$users,$parm['timValS'],$parm["textmail"], $parm['accuse']);
			$my_link = $push['mylink'];
			
			
			$Endusers = $push['users'];
		
			$lstbySbas = array();
			
			$sql = 'SELECT base_id, record_id FROM sselcont WHERE ssel_id="'.$conn->escape_string($ssel_id).'"';
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$lstbySbas[phrasea::sbasFromBas($row['base_id'])][] = $row['record_id'];
				}
			}
			foreach($lstbySbas as $sbas=>$lst)
			{
				foreach($lst as $record)
					foreach($Endusers as $u)
						answer::logEvent($sbas,$record,'validate',$u,'');
			}
		}
		
		if(isset($outinfos))
			echo "<div style='text-align:center;margin-top:20px;'>".$outinfos."</div>";
		echo "<div style='text-align:center;margin-top:20px;'></div>" ;
		if(isset($my_link) && strlen($my_link)>4)
			echo "<div style='text-align:center;margin-top:20px;'><a href='".$my_link."' target='_blank'>"._('prod::push: acceder directement a votre espace de validation')."</div>" ;
	
		echo "<div style='text-align:center;margin-top:20px;'><a onclick=\"parent.hideDwnl();\" style='cursor:pointer;'>"._('boutton::fermer')."</a></div>";
	}
	?>
	<script type="text/javascript">
		parent.refreshBaskets('current');
	</script>
	</body>
	<?php
	
}

?>