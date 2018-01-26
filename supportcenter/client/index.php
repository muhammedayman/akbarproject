<?php
	ini_set('zend.ze1_compatibility_mode', true);
	header('Content-Type:text/html; charset=UTF-8'); 
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";

	error_reporting(0);
	define('SERVER_PATH', dirname(dirname(__FILE__)) . '/server/');
	define('CLASS_PATH', SERVER_PATH . 'include/');
	require_once CLASS_PATH . 'QUnit.class.php';
	$config = QUnit::newObj('QUnit_Config', SERVER_PATH.'settings/config.inc.php');
	QUnit::includeClass('QUnit_Db_Object');
	$db = QUnit_Db_Object::getDriver($config->get('db'));
	$connect = $db->connect();
	$config->loadDbSettings($connect);
	$lang = QUnit::newObj('QUnit_Lang_Parser');
	$lang->setDefaultLanguage($config->get('defaultLanguage'));

?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		<title><?php echo $lang->get('booting');?></title>
		<link rel="stylesheet" href="lib/style/main.css" type="text/css" />
		<link rel="stylesheet" href="app/web/style/main.css" type="text/css" />
		<link rel="stylesheet" href="custom/custom.css" type="text/css"/>
		<link rel="shortcut icon" href="app/web/images/favicon.png" type="image/png" />
		<link rel="icon" href="app/web/images/favicon.png" type="image/png" />

		<script type="text/javascript" src="gears/gears_init.js"></script>
		<script type="text/javascript">
			try {
				var localServer = google.gears.factory.create('beta.localserver', '1.0');
				var store = localServer.createManagedStore("SupportCenter");
				store.manifestUrl = "gears/gears_manifest.json.js";
				store.checkForUpdate();
				store.enabled = true;
			} catch (e) {
			}
		</script>

		<script type="text/javascript">
			djConfig = {
				isDebug: false,
				useXDomain: true,
				dojoRichTextFrameUrl: "lib/dojo/src/widget/templates/richtextframe.html",
				dojoIframeHistoryUrl: "lib/dojo/src/widget/templates/iframe_history.html",
				xdWaitSeconds: 30,
				baseScriptUri: "lib/dojo/",
				debugContainerId:"quDebugContainer",
				cacheBust: "ver1build110329064447"
			};
			if(djConfig.isDebug) {
				djConfig.cacheBust = new Date().valueOf().toString();
			}
			var myStr = new String(window.location);
			quConfig = {
                defaultFontSize: "<?php echo $config->get('defaultFontSize'); ?>",
                defaultFont: "<?php echo $config->get('defaultFont'); ?>",
                showPriorityInSubmitForm: "<?php echo $config->get('showPriorityInSubmitForm'); ?>",
                passwordEncoding: "<?php echo $config->get('passwordEncoding'); ?>",
                disableMailGateway: "<?php echo $config->get('disableMailGateway'); ?>",
                disableNotifications: "<?php echo $config->get('disableNotifications'); ?>",
                knowledgeBaseURL: "<?php echo $config->get('knowledgeBaseURL'); ?>",
                knowledgeBaseModule: "<?php echo $config->get('knowledgeBaseModule'); ?>",
                workReporting: "<?php echo $config->get('workReporting'); ?>",
                agentSeeJustAgentsFromHisQueues: "<?php echo $config->get('agentSeeJustAgentsFromHisQueues'); ?>",
                agentCanNotSeeAdmins: "<?php echo $config->get('agentCanNotSeeAdmins'); ?>",
                agentUserAccess: "<?php echo $config->get('agentUserAccess'); ?>",
                applicationURL: "<?php echo $config->get('applicationURL'); ?>",
                autoAssignTicketToAgent: "<?php echo $config->get('autoAssignTicketToAgent'); ?>",
                defaultPriority: "<?php echo $config->get('defaultPriority'); ?>",
                onlineAgentsView: "<?php echo $config->get('onlineAgentsView'); ?>",
                dbLevel: "<?php echo $config->get('dbLevel'); ?>",
                useOutbox: <?php echo ($config->get('useOutbox') == 'y' ? 'true' : 'false'); ?>,
                DownloadManager: <?php echo ($config->get('DownloadManager') == 'y' ? 'true' : 'false'); ?>,
                agentDeleteTickets: "<?php echo $config->get('agentDeleteTickets'); ?>",
                hideGroups: <?php echo ($config->get('hideGroups') == 'y' ? 'true' : 'false'); ?>,
                brand: "<?php echo $config->get('brand'); ?>",
				isPreloadTemplates: true,
				isPreloadServices: true,
				mainClass: "app.view.Main",
				loginClass: "app.view.Welcome",
				logoutClass: "app.view.Welcome",
				title: "Support Center",
				isLoginRequired: true,
				isBrowserSupported: false,
				bootFilename: "index.php",
				packageDomain: myStr.substring(0, myStr.lastIndexOf("/")),
				dojoSrc: "dojo.build.php",
				requestContentType: "<?php 
				    if (strlen($config->get('requestContentType'))) {
				        echo $config->get('requestContentType');
				    } else {
				        if (strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
				            echo 'aplication/json-rpc';
				        } else {
				            echo 'multipart/form-data';
				        }
				    }
		        ?>",
				systemLanguages: "<?php
				
				$objLang = QUnit::newObj('QUnit_Lang_Parser');
				$langs = $objLang->getAvaibleLanguages();
				
				$res = '';
				foreach ($langs as $language) {
					$res .= (strlen($res) ? '|' : '') . $language[0];
				}
				echo $res;
				
				?>",
				interfaceLanguage: "<?php echo (strlen($config->get('defaultLanguage')) ? $config->get('defaultLanguage') : "English"); ?>",
				loginLanguage: "<?php echo $_GET['language']; ?>",
				ticketStatusNewColor: "<?php echo (strlen($config->get('ticketStatusNewColor')) ? $config->get('ticketStatusNewColor') : ""); ?>",
				ticketStatusResolvedColor: "<?php echo (strlen($config->get('ticketStatusResolvedColor')) ? $config->get('ticketStatusResolvedColor') : ""); ?>",
				ticketStatusSpamColor: "<?php echo (strlen($config->get('ticketStatusSpamColor')) ? $config->get('ticketStatusSpamColor') : ""); ?>",
				ticketStatusDeadColor: "<?php echo (strlen($config->get('ticketStatusDeadColor')) ? $config->get('ticketStatusDeadColor') : ""); ?>",
				ticketStatusWorkInProgressColor: "<?php echo (strlen($config->get('ticketStatusWorkInProgressColor')) ? $config->get('ticketStatusWorkInProgressColor') : ""); ?>",
				ticketStatusBouncedColor: "<?php echo (strlen($config->get('ticketStatusBouncedColor')) ? $config->get('ticketStatusBouncedColor') : ""); ?>",
				ticketStatusAwaitingReplyColor: "<?php echo (strlen($config->get('ticketStatusAwaitingReplyColor')) ? $config->get('ticketStatusAwaitingReplyColor') : ""); ?>",
				ticketStatusCustomerReplyColor: "<?php echo (strlen($config->get('ticketStatusCustomerReplyColor')) ? $config->get('ticketStatusCustomerReplyColor') : ""); ?>",			
				agentCanSeeOwnReports: <?php echo ($config->get('agentCanSeeOwnReports') == 'y' ? 'true' : 'false'); ?>,
                mceConfig: {
					mode : "none",
					theme : "advanced",
					plugins : "inlinepopups",
					theme_advanced_toolbar_location : "top",
					theme_advanced_toolbar_align : "left",
					theme_advanced_buttons1 : "undo,redo,separator,bold,italic,underline,fontselect,fontsizeselect,separator,forecolor,backcolor,separator,bullist,numlist,separator,outdent,indent,separator,link,unlink",
					theme_advanced_buttons2 : "",
					theme_advanced_buttons3 : "",
					width: "100%",
					height: "250",
					accessibility_focus : false
				},
				uploadMaxSize: <?php echo min(getIniSize('post_max_size'), getIniSize('upload_max_filesize'), getIniSize('memory_limit')/2);
				function getIniSize($setting) {
					$val = trim(ini_get($setting));
					if (!strlen($val)) $val = '10g';
					$last = strtolower($val{strlen($val)-1});
					switch($last) {
						case 'g':
						$val *= 1024;
						case 'm':
						$val *= 1024;
						case 'k':
						$val *= 1024;
					}
					return $val;
				}
				?>
			};
            var qu = new Object();
            var app = new Object();
            app.custom = new Object();
		</script>
		<script type="text/javascript">
			function getCookie(/*String*/ inName) {
				var cookies = document.cookie.split("; ");
				for(var i = 0; i<cookies.length; i++) {
					var c = cookies[i];
					var pos = c.indexOf("=");
					var n = c.substring(0, pos);
					if(n == inName) {
						return c.substring(pos + 1);
					}
				}
			return null;
			}
			if(getCookie("language") !== null) {
				quConfig.interfaceLanguage = getCookie("language");
			}
			
			function fc_readData(param) {
				var obj = document.getElementById(param);
				if(!obj) {
			        return "Data object '" + param + "' not found!";
			    } else {
			        return obj.value;
			    }
			}		</script>
	</head>
	<body onload="__init__()" style="z-index:0;direction: <?php echo $lang->get('languageDirection'); ?>;">
		<noscript>
			<div style="padding:2em;">
				<h1>Javascript not found :(</h1>
				<p>
					Please, enable javascript in order to experience the perfection of this rich internet application (RIA).
				</p>
			</div>	
		</noscript>
		<div id="quIndicator" style="
			position: fixed;
			top: 1px;
			right: 1px;
			_position: absolute;
			visibility: visible;
			display: block;
			background-color: #cc4444;
			color: white;
			padding: 3px;
            z-index: 10000;
		">
		Loading...
		</div>
        <div id="quDialogBackground" style="
			position: absolute;
			top: 0px;
			left: 0px;
			width: 0px;
			height: 0px;
			display: none;
			z-index: 1;
			background-color: black;
			_background-color: white;
			opacity: 0.2;
			filter: alpha(opacity=20);
		">
		</div>
		<div id="quLoading" style="
			position: absolute;
			width: 250px;
			top: 250px;
			height: 30px;
			background-color: white;
			color: black;
			font-weight: bold;
			border: 2px solid #CDDEEE;
			font-size: 14px;
			padding-top: 11px;
			_padding-top: 4px;
			padding-left: 32px;
			visibility: hidden;
            z-index: 1000;
            -moz-border-radius: 100%;
			">
			<img src="app/web/images/progress.gif" alt="progress indicator" style="width:10px; height:10px" />
			<span id="quBootMessage"><?php echo $lang->get('booting');?></span>
		</div>
		
		<div id="quHeader" style="display:none;">
			<?php include("custom/header.php") ?>
		</div>
		<div id="quContent"></div>
		<div id="quFooter" style="display:none;">
			<?php include("custom/footer.php") ?>
		</div>

		<script type="text/javascript">
			document.title = quConfig.title;
			dots = document.getElementById("quLoading");
			dots.style.left = (document.body.clientWidth/2 - 125) + "px";
			dots.style.visibility = "visible";
            app.background = document.getElementById("quDialogBackground");
		</script>
		<script type="text/javascript">
			quThrobber = {};
			quThrobber.message = "Loading...";
			quThrobber.show = function(inMessage) {
				var message = inMessage;
				if(typeof(message) != "string") {
					message = quThrobber.getMessage();
				}
				try {
					var throbber = document.getElementById("quIndicator");
					throbber.innerHTML = message;
					throbber.style.display = "block";
				} catch (inError) {}
			}
			quThrobber.hide = function() {
				try {
					var throbber = document.getElementById("quIndicator");
					throbber.style.display = "none";
				} catch (inError) {}
			}
			quThrobber.setMessage = function(inMessage) {
				quThrobber.message = inMessage;
			}
			quThrobber.getMessage = function() {
				return quThrobber.message;
			}
		</script>
		<script type="text/javascript">
			var __Environment__ = {
				init: function () {
					this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
					this.version = this.searchVersion(navigator.userAgent)
						|| this.searchVersion(navigator.appVersion)
						|| "an unknown version";
					this.os = this.searchString(this.dataOS) || "an unknown OS";
				},
				searchString: function (data) {
					for (var i=0;i<data.length;i++)	{
						var dataString = data[i].string;
						var dataProp = data[i].prop;
						this.versionSearchString = data[i].versionSearch || data[i].identity;
						if (dataString) {
							if (dataString.indexOf(data[i].subString) != -1)
								return data[i].identity;
						}
						else if (dataProp)
							return data[i].identity;
					}
				},
				searchVersion: function (dataString) {
					var index = dataString.indexOf(this.versionSearchString);
					if (index == -1) return;
					return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
				},
				dataBrowser: [
					{string: navigator.userAgent, subString: "OmniWeb", versionSearch: "OmniWeb/", identity: "OmniWeb"},
					{string: navigator.vendor, subString: "Apple", identity: "Safari"},
					{prop: window.opera, identity: "Opera"},
					{string: navigator.vendor, subString: "iCab", identity: "iCab"},
					{string: navigator.vendor, subString: "KDE", identity: "Konqueror"},
					{string: navigator.userAgent, subString: "Firefox",	identity: "Firefox"},
					{string: navigator.vendor, subString: "Camino",	identity: "Camino"},
					{string: navigator.userAgent, subString: "Netscape", identity: "Netscape"}, // for newer Netscapes (6+)
					{string: navigator.userAgent, subString: "MSIE", identity: "Explorer", versionSearch: "MSIE"},
					{string: navigator.userAgent, subString: "Gecko", identity: "Mozilla", versionSearch: "rv"},
					{string: navigator.userAgent, subString: "Mozilla", identity: "Netscape", versionSearch: "Mozilla"} // for older Netscapes (4-)
				],
				dataOS : [
					{string: navigator.platform, subString: "Win", identity: "Windows"},
					{string: navigator.platform, subString: "Mac", identity: "Mac"},
					{string: navigator.platform, subString: "Linux", identity: "Linux"}
				]
			};
			
			__Environment__.init();
			
//			if(
//				(__Environment__.browser == "Firefox" && __Environment__.version >= 1.5) ||
//				(__Environment__.browser == "Opera" && __Environment__.version >= 9) ||
//				(__Environment__.browser == "Explorer" && __Environment__.version >= 6) ||
//				(__Environment__.browser == "Mozilla" && __Environment__.version >= 1.7) /*||
//				(__Environment__.browser == "Konqueror" && __Environment__.version >= 3.5) ||
//				(__Environment__.browser == "Safari" && __Environment__.version >= 0)*/
//			) {
				quConfig.isBrowserSupported = true;
				document.write('<');
				document.write('script type="text/javascript" src="lib/dojo/' + quConfig.dojoSrc + "?" + djConfig.cacheBust + '">');
				document.write('</');
				document.write('script>');
//			} else {
//				quConfig.isBrowserSupported = false;
//			}
			var d = new Date();
			var mD = new Date(d.valueOf() + 60000 * 24 * 30 * 60);
			document.cookie = "version=" + djConfig.cacheBust + "; expires=" + mD.toGMTString() + "; path=/";
		</script>
		<script type="text/javascript">
			function __boot__() {
				if(dojo.byId("quBootMessage").innerHTML == "<?php echo $lang->get('booting');?>") {
					dojo.byId("quBootMessage").innerHTML = "<?php echo $lang->get('loadingLibraries');?>";
					setTimeout("__boot__()", 0);
					return;
				}
				if(dojo.byId("quBootMessage").innerHTML == "<?php echo $lang->get('loadingLibraries');?>") {
					dojo.registerModulePath("dojo", quConfig.packageDomain + "/lib/dojo/src");
					dojo.registerModulePath("qu", quConfig.packageDomain + "/lib/qu");
					dojo.registerModulePath("3rd", quConfig.packageDomain + "/lib/3rd");
					dojo.registerModulePath("app", quConfig.packageDomain + "/app/src");
					dojo.require("qu.EntryPoint");
					dojo.addOnLoad(function() {
						if(!quConfig.isPreloadTemplates) app.template = null;
						if(!quConfig.isPreloadServices) app.services = {};
						qu.boot.boot();
						dojo.byId("quBootMessage").innerHTML = "<?php echo $lang->get('launchingApplication');?>";
						setTimeout("__boot__()", 0);
						return;
					});
				}
				if(dojo.byId("quBootMessage").innerHTML == "<?php echo $lang->get('launchingApplication');?>") {
					qu.boot.launch();
                    dojo.body().removeChild(dojo.byId("quLoading"));
				}
			}

			function __init__() {
				if(quConfig.isBrowserSupported) {
					dojo.addOnLoad(__boot__);
				} else {
					window.location = "err_unsupported.html";
				}
			}
		</script>
		<a href="http://www.qualityunit.com/supportcenter/" style="display: none;">SupportCenter</a>
	</body>
</html>
