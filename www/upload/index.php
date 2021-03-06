<?php
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
require( GV_RootPath . 'lib/unicode/lownodiacritics_utf8.php' );
$session = session::getInstance();


$lng = isset($session->locale) ? $session->locale : GV_default_lng;

$conn = connection::getInstance();

if (isset($session->usr_id) && isset($session->ses_id))
{
  $ses_id = $session->ses_id;
  $usr_id = $session->usr_id;

  if (!$session->upload)
  {
    header("Location: /client/");
    exit();
  }
}
else
{
  header("Location: /login/upload/");
  exit();
}

function filize($x)
{
  return '*.' . $x;
}

if (!($ph_session = phrasea_open_session($ses_id, $usr_id)))
  die();

user::updateClientInfos(8);

phrasea::headers();

$avStatus = array();
$sql = 'SELECT b.base_id FROM basusr bu, bas b 
        WHERE usr_id="' . $conn->escape_string($usr_id) . '"
        AND bu.chgstatus = "1" AND b.base_id=bu.base_id
        AND b.active="1" AND bu.actif="1"';
if ($rs = $conn->query($sql))
{
  while ($row = $conn->fetch_assoc($rs))
  {
    $avStatus[] = $row["base_id"];
  }
}
$avBases = array();
$sql = 'SELECT b.base_id FROM basusr bu, bas b 
        WHERE usr_id="' . $conn->escape_string($usr_id) . '"
        AND canaddrecord = "1" AND b.base_id=bu.base_id
        AND b.active="1" AND bu.actif="1"';
if ($rs = $conn->query($sql))
{
  while ($row = $conn->fetch_assoc($rs))
  {
    $avBases[] = $row["base_id"];
  }
}

if (count($avBases) == 0)
{

  header("Content-Type: text/html; charset=UTF-8");
?>

  <html xmlns="http://www.w3.org/1999/xhtml"
        lang="<?php echo $session->usr_i18n; ?>">
    <head>
      <meta http-equiv="X-UA-Compatible" content="chrome=1">
      <title>
      <?php echo GV_homeTitle, ' ', _('admin::monitor: module upload'); ?>
    </title>
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
    <link type="text/css" rel="stylesheet"
          href="/include/minify/f=skins/common/main.css" />
    <link href="css/default.css" rel="stylesheet" type="text/css" />
    <link href="css/jquery-ui-1.7.2.custom.css"
          rel="stylesheet" type="text/css" />

    <style type="text/css">
      body{
        background-color:black;
        color:white;
        overflow:auto;
      }

      #mainMenu a, #mainMenu b{
        color:white;
      }
    </style>
    <script type="text/javascript" src="/include/minify/g=upload"></script>
  </head>
  <body>
    <?php
      $twig = new supertwig();
      $twig->display('common/menubar.twig', array('module' => 'upload'));
    ?>

      <div id="content">
      <?php echo _('upload:You do not have right to upload datas'); ?>
    </div>
  </body>
</html>

<?php
      die();
    }

    $colls = '';
    $datasSB = array();
    $dstatus = status::getDisplayStatus();

    foreach ($ph_session['bases'] as $base)
    {

      $groupopen = false;
      $collections = array();

      $connsbas = connection::getInstance($base['sbas_id']);

      $sql = 'SELECT distinct base_id, server_coll_id FROM bas
              WHERE sbas_id="' . $conn->escape_string($base['sbas_id']) . '"
              AND (base_id = "' . implode('" OR base_id="', $avBases) . '")
              ORDER BY ord ASC , server_coll_id ASC';
      if ($connsbas && ($rs = $conn->query($sql)))
      {
        while ($row = $conn->fetch_assoc($rs))
        {
          $sql = 'SELECT htmlname, prefs FROM coll
                  WHERE coll_id = "' .
                  $connsbas->escape_string($row['server_coll_id']) . '"';
          if ($rsB = $connsbas->query($sql))
          {
            if ($rowB = $connsbas->fetch_assoc($rsB))
            {
              $collections[] = array(
                  'base_id' => $row['base_id'],
                  'name' => $rowB['htmlname'],
                  'prefs' => $rowB['prefs']
              );
            }
          }
        }
        $conn->free_result($rs);
      }




      foreach ($collections as $coll)
      {
        if (in_array($coll['base_id'], $avBases))
        {
          if (!$groupopen)
          {
            $colls .= '<optgroup
              label="' . phrasea::sbas_names($base['sbas_id']) . '">';
            $groupopen = true;
          }
          $colls .= '<option
              value="' . $coll['base_id'] . '">' . $coll['name'] . '</option>';
        }

        if (in_array($coll['base_id'], $avStatus))
        {

          $status = '0000000000000000000000000000' .
                  '000000000000000000000000000000000000';
          if ($sxe = simplexml_load_string($coll['prefs']))
          {
            if ($sxe->status)
            {
              $status = (string) ($sxe->status);

              if ($rs = $conn->query('SELECT bin(' . $status . ') as status'))
              {
                if ($row = $conn->fetch_assoc($rs))
                {
                  $status = $row['status'];

                  while (strlen($status) < 64)
                    $status = '0' . $status;
                }
              }
            }
          }

          $datasSB[$coll['base_id']] = '<div style="display:none;"
            class="status_box" id="status_' . $coll['base_id'] . '"><table>';

          $currentdatasSB = '';
          $sbas_id = phrasea::sbasFromBas($coll['base_id']);

          if (isset($dstatus[$sbas_id]))
          {
            foreach ($dstatus[$sbas_id] as $n => $statbit)
            {

              $imgoff = '';
              $imgon = '';

              if ($statbit['img_off'])
                $imgoff = '<img src="' . $statbit['img_off'] . '"
                  title="' . $statbit['labeloff'] . '"
                  style="width:16px;height:16px;vertical-align:bottom" />';
              if ($statbit['img_on'])
                $imgon = '<img src="' . $statbit['img_on'] . '"
                  title="' . $statbit['labelon'] . '"
                  style="width:16px;height:16px;vertical-align:bottom" />';

              $datasSB[$coll['base_id']] .= '
		<tr style="height: 24px;">
                  <td id="status_off_' . $coll['base_id'] . '_' . $n . '"
                        class="status_off ' . (($status[63 - (int) $n] == '0') ? 'active' : '') . '">' .
                      $imgoff . ' ' . $statbit['labeloff'] .
                      '</td>
                  <td>
                  <div style="width:50px;margin:0 20px;" class="slider_status"></div></td>
                  <td class="status_on ' . (($status[63 - (int) $n] == '1') ? 'active' : '') . '" id="status_on_' . $coll['base_id'] . '_' . $n . '">' .
                      $imgon . ' ' . $statbit['labelon'] . '</td>
						</tr>';
            }
          }

          $datasSB[$coll['base_id']] .= '</table></div>';
        }
      }
      if ($groupopen)
        $colls .= '</optgroup>';
    }


    $maxVolume = min((int) get_cfg_var('upload_max_filesize'), (int) get_cfg_var('post_max_size'));


    header("Content-Type: text/html; charset=UTF-8");
?>

    <html xmlns="http://www.w3.org/1999/xhtml" >
      <head>
        <title><?php echo GV_homeTitle, ' ', _('admin::monitor: module upload'); ?></title>
        <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
        <link href="css/jquery-ui-1.8.5.custom.css" rel="stylesheet" type="text/css" />
        <link href="css/default.css" rel="stylesheet" type="text/css" />

        <style type="text/css">
<?php
    $theFont = '.theFont {font-weight:bold;color:#73B304;font-size: 14px;font-family:Arial }';
    echo $theFont;
?>
        </style>
        <script type="text/javascript" src="/include/minify/g=upload"></script>
        <script type="text/javascript">

          var p4 = {};

          var language = {
            'ok':'<?php echo str_replace("'", "\'", _('boutton::valider')) ?>',
            'annuler':'<?php echo str_replace("'", "\'", _('boutton::annuler')) ?>',
            'pleaseselect':'<?php echo str_replace("'", "\'",
                                    _('Selectionner une action')) ?>',
            'norecordselected':'<?php echo str_replace("'", "\'",
                                    _('Aucune enregistrement selectionne')) ?>',
            'transfert_active':'<?php echo str_replace("'", "\'", _('Transfert en court, vous devez attendre la fin du transfert')) ?>',
            'queue_not_empty' : '<?php echo str_replace("'", "\'", _('File d\'attente n\'est pas vide, souhaitez vous supprimer ces elements ?')) ?>'
          };

          function sessionactive(){
            $.ajax({
              type: "POST",
              url: "/include/updses.php",
              dataType: 'json',
              data: {
                app : 8,
                usr : <?php echo $usr_id ?>
              },
              error: function(){
                window.setTimeout("sessionactive();", 10000);
              },
              timeout: function(){
                window.setTimeout("sessionactive();", 10000);
              },
              success: function(data){
                if(data)
                  manageSession(data);
                var t = 20000;
                if(data.apps && parseInt(data.apps)>1)
                  t = Math.round((Math.sqrt(parseInt(data.apps)-1) * 1.3 * 20000));
                window.setTimeout("sessionactive();", t);
                return;
              }
            })
          };
          sessionactive();

          window.onbeforeunload = function()
          {
            var xhr_object = null;
            if(window.XMLHttpRequest) // Firefox
              xhr_object = new XMLHttpRequest();
            else if(window.ActiveXObject) // Internet Explorer
              xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
            else  // XMLHttpRequest non supporte par le navigateur
              return;
            url= "../include/delses.php?app=8&t="+Math.random();
            xhr_object.open("GET", url, false);
            xhr_object.send(null);

          };

          //This event comes from the Queue Plugin
          function queueComplete(numFilesUploaded) {
            var status = document.getElementById("divStatus");
            if(numFilesUploaded>1)
              status.innerHTML = $.sprintf("<?php echo str_replace('"', '&quot;', _('upload:: %d fichiers uploades')); ?>",numFilesUploaded);
            else
              status.innerHTML = $.sprintf("<?php echo str_replace('"', '&quot;', _('upload:: %d fichier uploade')); ?>",numFilesUploaded);

            checkQuarantineSize();
          }

          $(document).ready(function() {
            var settings = {
              flash_url : "swfupload/swfupload.swf",
              upload_url: "upload.php",
              post_params: {"session" : "<?php echo session_id(); ?>"},
              file_size_limit : "<?php echo $maxVolume . ' MB'; ?>",
              file_types : "<?php echo implode(';', array_map("filize", explode(',', GV_appletAllowedFileExt))) ?>",
              file_types_description : "These Files",
              file_upload_limit : 0,
              requeue_on_error : true,
              file_post_name : "Filedata",
              file_queue_limit : 0,
              custom_settings : {
                progressTarget : "fsUploadProgress",
                cancelButtonId : "btnCancel"
              },
              debug:false,

              // Button settings
              button_image_url: "images/fond400.gif",
              button_width: "400",
              button_height: "30",
              button_placeholder_id: "spanButtonPlaceHolder",
              button_text: '<span class="theFont"><?php echo str_replace("'", "\'", sprintf(_('upload :: choisir les fichiers  a uploader (max : %d MB)'), $maxVolume)); ?></span>',
              button_text_style: "<?php echo $theFont ?>",
              button_text_left_padding: 12,
              button_text_top_padding: 3,
              button_window_mode:'transparent',
              button_cursor : SWFUpload.CURSOR.HAND,


              // The event handler functions are defined in handlers.js
              file_queued_handler : fileQueued,
              file_queue_error_handler : fileQueueError,
              file_dialog_complete_handler : fileDialogComplete,
              upload_start_handler : uploadStart,
              upload_progress_handler : uploadProgress,
              upload_error_handler : uploadError,
              upload_success_handler : uploadSuccess,
              upload_complete_handler : uploadComplete,
              queue_complete_handler : queueComplete	// Queue plugin event
            };

            swfu = new SWFUpload(settings);

            $('#step1 .classic_switch, #flash_return .classic_switch').bind('click', function(event){
              classic_switch();
              return false;
            });
          });

          function reverseOrder()
          {
            var elems = $('#fsUploadProgress li');
            var arr = $.makeArray(elems);
            arr.reverse();
            $(arr).appendTo($('#fsUploadProgress'));
          }
          function classic_switch()
          {
            $('#step1, #step2, #step2classic, #step4, #flash_return').toggle();
          }

        </script>
      </head>
      <body>
    <?php
    $count = 0;
    try
    {
      $lazaret = new lazaret();
      $count = $lazaret->get_count();
    }
    catch (Exception $e)
    {
      
    }

    $twig = new supertwig();
    $twig->display('common/menubar.twig', array('module' => 'upload'));
    ?>
    <div id="content">
      <div class="tabs">
        <ul>
          <li><a href="#manager"><?php echo _('Upload Manager') ?></a></li>
          <li><a id="quarantine-tab" href="/upload/uploadFeedback.php?action=get_lazaret_html"><?php echo _('Quarantaine'); ?><span id="quarantine_size">(<?php echo (int) $count; ?>)</span></a></li>
        </ul>
        <div id="manager">
          <form id="form1" action="upload.php" method="post" enctype="multipart/form-data" onsubmit="return false;" target="classic_upload">
            <div style="height:60px;margin:20px 0;">
              <div id="step1" style="cursor:pointer;xwidth:800px;height:30px;"><span id="spanButtonPlaceHolder"></span>
                <div>
                  <a href="#" class="classic_switch" ><?php echo _('Utiliser l\'upload classique') ?></a>
                </div>
              </div>
              <div id="flash_return" style="display:none;">
                <a href="#" class="classic_switch" ><?php echo _('Retour a l\'upload flash') ?></a>
              </div>
            </div>
            <table style="width:100%;">
              <tr>
                <td style="width:33%;">
                  <div id="step2">
                    <div class="fieldset flash" style="margin:20px 0;">
                      <span class="legend"><?php echo _('upload:: Re-ordonner les fichiers') ?></span>
                      <div>
                        <ul id="fsUploadProgress">
                        </ul>
                      </div>
                      <div style="text-align:right">
                        <a href="javascript:void();" onclick="reverseOrder(); return(false);">
                          <?php echo _('upload:: inverser') ?>
                        </a>
                      </div>
                    </div>
                  </div>
                  <div id="step2classic" style="display:none;">
                    <div class="fieldset flash" style="margin:20px 0;">
                      <span class="legend"><?php echo _('upload:: Selectionner des fichiers') ?></span>
                      <div>
                        <input type="file" id="input_file_0" name="Filedata"/>
                      </div>
                      <div id="classic_parms">

                      </div>
                    </div>
                  </div>
                </td>
                <td style="width:33%;">
                  <div id="step3">
                    <div class="fieldset flash" style="margin:20px 0;">
                      <span class="legend"><?php echo _('upload:: Que faire avec les fichiers') ?></span>
                      <div id="coll_selector">
                        <label for="collselect"><?php echo _('upload:: Destination (collection) :') ?></label><select id="collselect" onchange="showStatus();"><?php echo $colls ?></select>
                        <?php echo _('upload:: Status :') ?>
                          <div id="status_wrapper">
                          <?php
                          foreach ($datasSB as $base_id => $dat)
                          {
                            echo $dat;
                          }
                          ?>
                        </div>
                      </div>
                    </div>

                    <div class="theFont" style="margin:20px 0;"></div>
                    <div>
                      <input type="button" value="<?php echo _('upload:: demarrer') ?>" onclick="startIt();" style="margin-left: 2px; font-size: 8pt; height: 29px;" />
                      <img src="/skins/icons/loader000000.gif" id="classic_loader" style="display:none;vertical-align:middle;" />
                    </div>
                  </div>
                </td>
                <td style="width:33%;">
                  <div class="fieldset flash" style="margin:20px 0;" id="step4">
                    <span class="legend" id="divStatus"><?php echo sprintf(_('upload:: %d fichier uploade'), 0) ?></span>
                    <div style="margin:10px;text-align:center;">
                      <input id="btnCancel" type="button" value="<?php echo _('upload:: annuler tous les telechargements') ?>" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left: 2px; font-size: 8pt; height: 29px;" />
                    </div>
                    <div id="QUEUE">
                    </div>
                  </div>
                </td>
              </tr>
            </table>
          </form>
        </div>
        <div id="lazaret">
        </div>
      </div>
    </div>
    <div id="global_operation" style="display:none;" title="actions par lot">
      <div>
        <select name="action">
          <option value="">
            <?php echo _('Action'); ?>
          </option>
          <option value="add">
            <?php echo _('Ajouter les documents bloques'); ?>
          </option>
          <option value="substitute">
            <?php echo _('Substituer quand possible ou Ajouter les documents bloques'); ?>
          </option>
          <option value="delete">
            <?php echo _('Supprimer les documents bloques'); ?>
          </option>
        </select>
      </div>
      <div>
        <input type="checkbox" class="delete_previous" id="delete_previous_global" />
        <label for="delete_previous_global">
          <?php echo _('Supprimer precedentes propositions a la substitution'); ?>
        </label>
      </div>
    </div>
  </body>
</html>
