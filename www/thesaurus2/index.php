<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );

$session = session::getInstance();

$request = httpRequest::getInstance();
$parm = $request->get_parms(
					"bas",
					"res"
				);

$lng = isset($session->locale)?$session->locale:GV_default_lng;

$conn = connection::getInstance();

if(isset($session->usr_id) && isset($session->ses_id))
{
	$ses_id = $session->ses_id;
	$usr_id = $session->usr_id;
	
	$sql = 'SELECT invite FROM usr WHERE usr_id="'.$conn->escape_string($usr_id).'"';
	if($rs = $conn->query($sql))
	{
		if($row = $conn->fetch_assoc($rs))
		{
			if($row['invite'] == '1')
			{
				header("Location: /login/thesaurus/");
				exit();
			}
		}
	}
}
else{
	header("Location: /login/thesaurus/");
	exit();
}
phrasea::headers();

if(!($ph_session = phrasea_open_session($ses_id,$usr_id)))
	die();	
	
	


// on liste les bases dont on peut éditer le thésaurus
// todo : ajouter 'bas_edit_thesaurus' dans sbasusr. pour l'instant on simule avec bas_edit_thesaurus=bas_bas_modify_struct
$sql = "SELECT
 sbas.sbas_id,
 host, port, dbname, sqlengine, user, pwd,
 usr.usr_id,
 (sbasusr.bas_manage) AS bas_manage,
 (sbasusr.bas_modify_struct) AS bas_modify_struct,
 (sbasusr.bas_modif_th) AS bas_edit_thesaurus
FROM
 (usr INNER JOIN sbasusr ON usr.usr_id='".$conn->escape_string($usr_id)."' AND usr.usr_id=sbasusr.usr_id AND model_of=0)
 INNER JOIN sbas ON sbas.sbas_id=sbasusr.sbas_id
HAVING bas_edit_thesaurus>0
ORDER BY sbas.ord";

// print($sql);


?>
<html lang="<?php echo $session->usr_i18n;?>">
<head>
<meta http-equiv="X-UA-Compatible" content="chrome=1">

<link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand()?>" />


</head>

<body onload="ckok();">
<br/>
<br/>
<br/>
<center>
<?php
$select_bases = "";
$nbases = 0;
$last_base = null;
$usr_id = null;
if($rs = $conn->query($sql))
{
	while($row = $conn->fetch_assoc($rs))
	{
		$usr_id = $row["usr_id"];	// tjrs le m�me
		$connbas = connection::getInstance($row['sbas_id']);
		if($connbas)
		{
			$name = phrasea::sbas_names($row['sbas_id']);
			$select_bases .= "<option value=\"".$row["sbas_id"]."\">".$name."</option>\n";
			$last_base = array("sbid"=>$row["sbas_id"], "name"=>$name);
			$nbases++;
		}
	}
	$conn->free_result($rs);
}

if($nbases > 0)
{
?>

<form name="fBase" action="./thesaurus.php" method="post">
	<input type="hidden" name="res" value="<?php echo $parm["res"]?>" />
	<input type="hidden" name="uid" value="<?php echo $usr_id?>" />
	<?php echo p4string::MakeString(_('thesaurus:: Editer le thesaurus')) ?>
<?php
	if($nbases == 1)
	{
		printf("\t<input type=\"hidden\" name=\"bid\" value=\"%s\"><b>%s</b><br/>\n", $last_base["sbid"], $last_base["name"]);
?>
	<script type="text/javascript">
	function ckok()
	{
		ck = false;
		fl = document.getElementsByName("piv");
		for(i=0; !ck && i<fl.length; i++)
			ck = fl[i].checked;
		document.getElementById("button_ok").disabled = !ck;
	}
	</script>
<?php
	}
	else
	{
?>
	<select name="bid" onchange="ckok();return(true);">
		<option value=""><?php echo p4string::MakeString(_('phraseanet:: choisir')) /* Editer le th�saurus de la base : */ ?></option>
		<?php echo $select_bases?>
	</select>
<?php
?>
	<br/>
	<script type="text/javascript">
	function ckok()
	{
		ck = false;
		fl = document.getElementsByName("piv");
		for(i=0; !ck && i<fl.length; i++)
			ck = fl[i].checked;
		ck &= document.forms[0].bid.selectedIndex > 0;
		document.getElementById("button_ok").disabled = !ck;
	}
	</script>

	<br/>
	<table>
<?php
	}

	$nf = 0;
	$tlng = user::avLanguages();
	foreach($tlng as $lng_code=>$lng)
	{
		printf("<tr><td>%s</td>", $nf==0 ? p4string::MakeString(_('thesaurus:: langue pivot')) /* Langue pivot : */ : "");
		print("<td style=\"text-align:left\"><input type='radio' onclick=\"ckok();return(true);\" value='$lng_code' name='piv'><img src='/skins/lng/".$lng_code."_flag_18.gif' />&nbsp;(".$lng_code.")</td></tr>\n");
		$nf++;
	}

?>
	</table>
	<br/>
	<br/>
	<input type="hidden" name="ses" value="<?php echo $ses_id?>" />
	<input type="hidden" name="usr" value="<?php echo $usr_id?>" />
	<input id="button_ok" type="submit" style="width:80px;" value="<?php echo p4string::MakeString(_('boutton::valider'))?>" /><br/>
</form>
<?php
}
else
{
?>
 	<?php echo p4string::MakeString(_('thesaurus:: Vous n\'avez acces a aucune base'))?>
	<script type="text/javascript">
	function ckok()
	{
	}
	</script>
<?php
}
?>
</center>
</body>
</html>