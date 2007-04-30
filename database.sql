-- NOTE: DON'T ACTUALLY RUN THIS! THE INSTALLER (install.php) will install for you!
-- Changed the charset to utf8, it's more standard...
-- Also fixed that nasty "there can be only one auto column and it must be defined as a key" bug.
-- Now the user can specify a prefix! Woo Hoo!!!
-- --------------------------------------------------------
-- 
-- Table structure for table `#__apps`
-- 
DROP TABLE IF EXISTS `#__apps`;
CREATE TABLE `#__apps` (
  `ID` int(20) NOT NULL auto_increment PRIMARY KEY,
  `name` mediumtext NOT NULL,
  `author` mediumtext NOT NULL,
  `email` mediumtext NOT NULL,
  `code` longtext NOT NULL,
  `library` longtext NOT NULL,
  `version` text NOT NULL,
  `maturity` mediumtext NOT NULL,
  `category` mediumtext NOT NULL
) TYPE=MyISAM CHARACTER SET `utf8` COLLATE `utf8_general_ci` AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `#__apps`
-- 

REPLACE INTO `#__apps` (`ID`, `name`, `author`, `email`, `code`, `library`, `version`, `maturity`, `category`) VALUES 
(1, 'Calculator', 'Psychiccyberfreak', 'bj@psychdesigns.net', 'var winHTML = \\''<STYLE type=\\"text/css\\"> .calcBtn { font-weight: bold; width: 100%; height: 100%; } </style><form name=\\"calculator\\"><table border=\\"0\\" cellpadding=\\"2\\" cellspacing=\\"0\\" width=\\"100%\\" height=\\"95%\\"><tr><td colspan=\\"4\\"><input type=\\"text\\" name=\\"calcResults\\" value=\\"0\\" style=\\"text-align: right; width: 100%;\\"></td></tr><tr><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" C \\" name=\\"calclear\\" onclick=\\"gCalculator.OnClick(\\\\\\''c\\\\\\'')\\"></td><td></td><td></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" = \\" name=\\"calequal\\" onclick=\\"gCalculator.OnClick(\\\\\\''=\\\\\\'')\\"></td></tr><tr><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 7 \\" name=\\"cal7\\" onclick=\\"gCalculator.OnClick(\\\\\\''7\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''7\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 8 \\" name=\\"cal8\\" onclick=\\"gCalculator.OnClick(\\\\\\''8\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''8\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 9 \\" name=\\"cal9\\" onclick=\\"gCalculator.OnClick(\\\\\\''9\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''9\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" / \\" name=\\"caldiv\\" onclick=\\"gCalculator.OnClick(\\\\\\''/\\\\\\'')\\"></td></tr><tr><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 4 \\" name=\\"cal4\\" onclick=\\"gCalculator.OnClick(\\\\\\''4\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''4\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 5 \\" name=\\"cal5\\" onclick=\\"gCalculator.OnClick(\\\\\\''5\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''5\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 6 \\" name=\\"cal6\\" onclick=\\"gCalculator.OnClick(\\\\\\''6\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''6\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" * \\" name=\\"calmul\\" onclick=\\"gCalculator.OnClick(\\\\\\''*\\\\\\'')\\"></td></tr><tr><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 1 \\" name=\\"cal1\\" onclick=\\"gCalculator.OnClick(\\\\\\''1\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''1\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 2 \\" name=\\"cal2\\" onclick=\\"gCalculator.OnClick(\\\\\\''2\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''2\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 3 \\" name=\\"cal3\\" onclick=\\"gCalculator.OnClick(\\\\\\''3\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''3\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" + \\" name=\\"calplus\\" onclick=\\"gCalculator.OnClick(\\\\\\''+\\\\\\'')\\"></td></tr><tr><td colspan=\\"2\\"><input class=\\"calcBtn\\" type=\\"button\\" value=\\" 0 \\" name=\\"cal0\\" onclick=\\"gCalculator.OnClick(\\\\\\''0\\\\\\'')\\" ondblclick=\\"gCalculator.OnClick(\\\\\\''0\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" . \\" name=\\"caldec\\" onclick=\\"gCalculator.OnClick(\\\\\\''.\\\\\\'')\\"></td><td><input class=\\"calcBtn\\" type=\\"button\\" value=\\" - \\" name=\\"calminus\\" onclick=\\"gCalculator.OnClick(\\\\\\''-\\\\\\'')\\"></td></tr></table></form>\\'';\r\n\r\nthis.window = new api.window();\r\nthis.window.title=\\"Calculator\\";\r\nthis.window.write(winHTML);\r\nthis.window.width=\\"190px\\";\r\nthis.window.height=\\"215px\\";\r\nthis.window.show();', 'function Calculator_OnClick(keyStr)\r\n\r\n{\r\n\r\nvar resultsField = document.calculator.calcResults;\r\n\r\n\r\n\r\nswitch (keyStr)\r\n\r\n{\r\n\r\ncase \\"0\\":\r\n\r\ncase \\"1\\":\r\n\r\ncase \\"2\\":\r\n\r\ncase \\"3\\":\r\n\r\ncase \\"4\\":\r\n\r\ncase \\"5\\":\r\n\r\ncase \\"6\\":\r\n\r\ncase \\"7\\":\r\n\r\ncase \\"8\\":\r\n\r\ncase \\"9\\":\r\n\r\ncase \\"0\\":\r\n\r\ncase \\".\\":\r\n\r\n\r\n\r\nif ((this.lastOp==this.opClear) || (this.lastOp==this.opOperator))\r\n\r\n{\r\n\r\nresultsField.value = keyStr;\r\n\r\n}\r\n\r\nelse\r\n\r\n{\r\n\r\n// ignore extra decimals\r\n\r\nif ((keyStr!=\\".\\") || (resultsField.value.indexOf(\\".\\")<0))\r\n\r\n{\r\n\r\nresultsField.value += keyStr;\r\n\r\n}\r\n\r\n\r\n\r\n}\r\n\r\n\r\n\r\nthis.lastOp = this.opNumber;\r\n\r\nbreak;\r\n\r\n\r\n\r\ncase \\"*\\":\r\n\r\ncase \\"/\\":\r\n\r\ncase \\"+\\":\r\n\r\ncase \\"-\\":\r\n\r\nif (this.lastOp==this.opNumber)\r\n\r\nthis.Calc();\r\n\r\nthis.evalStr += resultsField.value + keyStr;\r\n\r\n\r\n\r\nthis.lastOp = this.opOperator;\r\n\r\nbreak;\r\n\r\n\r\n\r\ncase \\"=\\":\r\n\r\nthis.Calc();\r\n\r\nthis.lastOp = this.opClear;\r\n\r\nbreak;\r\n\r\n\r\n\r\ncase \\"c\\":\r\n\r\nresultsField.value = \\"0\\";\r\n\r\nthis.lastOp = this.opClear;\r\n\r\nbreak;\r\n\r\n\r\n\r\ndefault:\r\n\r\nalert(\\"\\''\\" + keyStr + \\"\\'' not recognized.\\");\r\n\r\n}\r\n\r\n\r\n\r\n}\r\n\r\n\r\n\r\nfunction Calculator_Calc()\r\n\r\n{\r\n\r\nvar resultsField = document.calculator.calcResults;\r\n\r\n//alert(\\"eval:\\"+this.evalStr+resultsField.value);\r\n\r\nresultsField.value = eval(this.evalStr+resultsField.value);\r\n\r\nthis.evalStr = \\"\\";\r\n\r\n}\r\n\r\n\r\n\r\nfunction Calculator()\r\n\r\n{\r\n\r\nthis.evalStr = \\"\\";\r\n\r\n\r\n\r\nthis.opNumber = 0;\r\n\r\nthis.opOperator = 1;\r\n\r\nthis.opClear = 2;\r\n\r\n\r\n\r\nthis.lastOp = this.opClear;\r\n\r\n\r\n\r\nthis.OnClick = Calculator_OnClick;\r\n\r\nthis.Calc = Calculator_Calc;\r\n\r\n}\r\n\r\n\r\n\r\ngCalculator = new Calculator(); ', '1.0', 'Alpha', 'Office'),
(2, 'Web Browser', 'Psychiccyberfreak', 'bj@psychdesigns.net', 'this.window = new api.window();\r\nthis.window.write(\\''<form name=\\"submitbox\\" action=\\"#\\" onSubmit=\\"return gBrowser.Go()\\" >\\'');\r\nthis.window.write(\\''<form name=\\"submitbox\\" action=\\"#\\" onSubmit=\\"return gBrowser.Go()\\" >\\'');\r\nthis.window.write(\\''<input type=\\"text\\" id=\\"browserUrlBox\\" value=\\"http://www.google.com/\\" style=\\"width: 94%;\\" />\\'');\r\nthis.window.write(\\''<input type=\\"button\\" value=\\"Go\\" onClick=\\"gBrowser.Go()\\" style=\\"width: 6%;\\"><br />\\'');\r\nthis.window.write(\\''<iframe style=\\"width: 99%; height: 90%; background-color: #FFFFFF;\\" src=\\"http://www.google.com\\" id=\\"browserIframe\\" /></form>\\'');\r\nthis.window.title=\\"Web Browser\\";\r\nthis.window.height=\\"400px\\";\r\nthis.window.width=\\"500px\\";\r\nthis.window.show();', 'function browser_Go()\r\n\r\n{\r\n\r\nurlbox = document.getElementById(\\"browserUrlBox\\");\r\n\r\nURL = urlbox.value;\r\n\r\nif(URL.charAt(4) == \\":\\" && URL.charAt(5) == \\"/\\" && URL.charAt(6) == \\"/\\")\r\n\r\n{\r\n\r\n}\r\n\r\nelse\r\n\r\n{\r\n\r\n//but wait, what if it\\''s an FTP site?\r\n\r\nif(URL.charAt(3) == \\":\\" && URL.charAt(4) == \\"/\\" && URL.charAt(5) == \\"/\\")\r\n\r\n{\r\n\r\n}\r\n\r\nelse\r\n\r\n{\r\n\r\n//if it starts with an \\"ftp.\\", it\\''s most likely an FTP site.\r\n\r\nif((URL.charAt(0) == \\"F\\" || URL.charAt(0) == \\"f\\") && (URL.charAt(1) == \\"T\\" || URL.charAt(1) == \\"t\\") && (URL.charAt(2) == \\"P\\" || URL.charAt(2) == \\"p\\") && URL.charAt(3) == \\".\\")\r\n\r\n{\r\n\r\nURL = \\"ftp://\\"+URL;\r\n\r\n}\r\n\r\nelse\r\n\r\n{\r\n\r\n//ok, it\\''s probably a plain old HTTP site...\r\n\r\nURL = \\"http://\\"+URL;\r\n\r\n}\r\n\r\n}\r\n\r\n}\r\n\r\nIframe = document.getElementById(\\"browserIframe\\");\r\n\r\nIframe.src = URL;\r\n\r\nurlbox.value = URL;\r\n\r\n\r\n\r\nreturn false;\r\n\r\n}\r\n\r\n\r\n\r\nfunction Browser()\r\n\r\n{\r\n\r\nthis.Go = browser_Go;\r\n\r\nreturn false;\r\n\r\n}\r\n\r\n\r\n\r\ngBrowser = new Browser(); ', '1.0', 'Alpha', 'Internet'),
(3, 'Control Panel', 'Psychiccyberfreak', 'bj@psychdesigns.net', 'winHTML = \\"<div padding=10>\\";\r\nwinHTML += \\"<fieldset>\\";\r\nwinHTML += \\"<legend>Background Color</legend><div width=\\''100%\\'' style=\\''float: none;\\''>\\";\r\nwinHTML += \\"<a href=\\''javascript: document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"red\\\\\\";\\'' style=\\''background: red; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"orange\\\\\\";\\'' style=\\''background: orange; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"yellow\\\\\\";\\'' style=\\''background: yellow; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"lime\\\\\\";\\'' style=\\''background: red; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"green\\\\\\";\\'' style=\\''background: green; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"teal\\\\\\";\\'' style=\\''background: teal; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"blue\\\\\\";\\'' style=\\''background: blue; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"purple\\\\\\";\\'' style=\\''background: purple; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"black\\\\\\";\\'' style=\\''background: black; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"<a onclick=\\''document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value = \\\\\\"white\\\\\\";\\'' style=\\''background: white; float:left; width:20px; height:20px; margin:4px; cursor:pointer; outline:none;\\''></a>\\";\r\nwinHTML += \\"</div><br /><br /><b>Or HTML color:</b><input type=\\''text\\'' id=\\''ctrlpanel_color\\'' maxlength=\\''7\\'' size=\\''7\\'' value=\\''#\\'' />\\";\r\nwinHTML += \\"</fieldset>\\";\r\nwinHTML += \\"<fieldset>\\"\r\nwinHTML += \\"<legend>Background Image</legend>\\";\r\nwinHTML += \\"<b>Default: </b><input checked type=\\''radio\\'' name=\\''ctrlpanel_image\\'' onFocus=\\''document.getElementById(\\\\\\"ctrlpanel_text\\\\\\").value = \\\\\\"./wallpaper/default.gif\\\\\\";\\'' />\\";\r\nwinHTML += \\"<b>None: </b><input type=\\''radio\\'' name=\\''ctrlpanel_image\\'' onFocus=\\''document.getElementById(\\\\\\"ctrlpanel_text\\\\\\").value = \\\\\\"\\\\\\";\\'' />\\";\r\nwinHTML += \\"<b>Other: </b><input type=\\''radio\\'' name=\\''ctrlpanel_image\\'' id=\\''ctrlpanel_image_other\\'' onFocus=\\''document.getElementById(\\\\\\"ctrlpanel_text\\\\\\").value = \\\\\\"http://\\\\\\";\\'' />\\";\r\nwinHTML += \\"<br /><b>Image URL: </b><input type=\\''text\\'' name=\\''ctrlpanel_text\\'' value=\\''./wallpaper/default.gif\\'' id=\\''ctrlpanel_text\\'' />\\";\r\nwinHTML += \\"</fieldset><br /><input type=\\''button\\'' onClick=\\''api.registry.saveValue(0, \\\\\\"bgimg\\\\\\", document.getElementById(\\\\\\"ctrlpanel_text\\\\\\").value); setWallpaper(document.getElementById(\\\\\\"ctrlpanel_text\\\\\\").value); setWallpaperColor(document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value); api.registry.saveValue(0, \\\\\\"bgcolor\\\\\\", document.getElementById(\\\\\\"ctrlpanel_color\\\\\\").value);\\'' value=\\''Save\\'' />\\";\r\nwinHTML += \\"</div>\\";\r\nthis.window = new api.window();\r\nthis.window.title=\\"Control Panel\\";\r\nthis.window.width=\\"330px\\";\r\nthis.window.height=\\"275px\\";\r\nthis.window.write(winHTML);\r\nthis.window.show();\r\napi.registry.getValue(0, \\"bgimg\\", \\"gPanel.img\\");\r\napi.registry.getValue(0, \\"bgcolor\\", \\"gPanel.color\\");', 'function panel_loadvalue_img(img) {\r\ndocument.getElementById(\\"ctrlpanel_text\\").value = img;\r\n}\r\nfunction panel_loadvalue_color(color) {\r\ndocument.getElementById(\\"ctrlpanel_color\\").value = color;\r\n}\r\n\r\nfunction panel()\r\n{\r\nthis.img = panel_loadvalue_img;\r\nthis.color = panel_loadvalue_color;\r\nreturn false;\r\n}\r\n\r\ngPanel = new panel();', '1.0', 'Stable', 'System'),
(4, 'Notepad', 'Psychiccyberfreak', 'bj@psychdesigns.net', 'api.registry.getValue(this.id, \\"notepad\\", this.hitch(this.notepad));', 'this.notepad=function(text) {\r\n\r\nthis.window = new api.window();\r\n\r\nthis.window.write(\\"<div padding=10>\\");\r\nthis.window.write(\\"<textarea id=\\''notepad\\"+this.instance+\\"\\'' style=\\''width: 100%; height: 90%;\\''>\\");\r\nthis.window.write(text);\r\nthis.window.write(\\"</textarea>\\");\r\nthis.window.write(\\"<input onclick=\\\\\\"note = document.getElementById(\\''notepad\\"+this.instance+\\"\\'').value.replace(/\\\\\\\\n/gi, \\'' \\'' ); api.registry.saveValue(\\"+this.id+\\", \\''notepad\\'', note);\\\\\\" value=\\''Save\\'' type=\\''button\\'' />\\");\r\n\r\nthis.window.title=\\"Notepad\\";\r\nthis.window.width=\\"300px\\";\r\nthis.window.height=\\"305px\\";\r\nthis.window.show();\r\n}', '1.0', 'Beta', 'Office'),
(5, 'File Get Test', 'Jaymacdonald', 'jaymac407@gmail.com', 'api.fs.getFile(\\"ReadMe.txt\\",\\"docs/\\",this.hitch(this.start));', 'this.start=function(){\r\nwinHTML = \\"Path: \\" + api.fs.getFileResult[\\"directory\\"] + \\"\\" + api.fs.getFileResult[\\"file\\"] + \\"<br>\\";\r\nwinHTML += \\"Contents: \\"+api.fs.getFileResult[\\"contents\\"];\r\nthis.window = new api.window();\r\nthis.window.write(winHTML);\r\nthis.window.height=\\"305px\\";\r\nthis.window.width=\\"300px\\";\r\nthis.window.title=\\"Notepad\\";\r\nthis.window.show();\r\n}', '1.0', 'Beta', 'System'),
(6, 'File Explorer', 'Jaymacdonald', 'jaymac407@gmail.com', 'api.fs.listFiles(this.hitch(this.start));', 'this.start = function() {\r\n  var innerHTML;\r\n  innerHTML = \\"<h2>File Explorer</h2>\\";\r\n  var i = 0;\r\n  jayCount = api.fs.listFilesResult[\\"count\\"];\r\n  while(i <= jayCount)\r\n  {\r\n    innerHTML += \\"<p> File: \\"+api.fs.listFilesResult[i][\\"file\\"]+\\"| Directory: \\"+api.fs.listFilesResult[i][\\"directory\\"]+\\"</p>\\";\r\n    i++;\r\n  }\r\n  innerHTML += \\"End of file list.\\";\r\n  this.win1 = new api.window();\r\n  this.win1.write(innerHTML);\r\n  this.win1.title = \\"File Explorer\\";\r\n  this.win1.height = \\"190px\\";\r\n  this.win1.width = \\"200px\\";\r\n  this.win1.show();\r\n}', '0.1', 'Beta', 'Office');
(7, 'Katana IDE', 'Psychiccyberfreak', 'bj@psychdesigns.net', '/*\r\nIDE layout plan\r\n.--------------------------------------------------------------.\r\n| File | Insert | Functions | App |                            |\r\n|--------------------------------------------------------------|\r\n|  init()  | +-----------------------------------------------+ |\r\n|func1(arg)| | function code goes here (this is an editor)   | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | |                                               | |\r\n|          | +-----------------------------------------------+ |\r\n\\''--------------------------------------------------------------\\''\r\nthe toolbar has some things. File lets you open an app, make a new app, or save an app, or make a new app.\r\ninsert has various shortcuts, like this.hitch for example.\r\nfunctions lets you make new functions and remove unwanted functions.\r\napp let\\''s you compile an app to a package, or run an app.\r\n\r\nthe function list acts like tabs, click on one, and the data from the previous editor is saved, and swapped out for the new one.\r\nthe layout is acheved using a splitContainer.\r\n*/\r\n\r\n\r\n/*\r\nthis.functions = new Array();\r\n\r\n\r\nthis.window = new api.window();\r\nthis.window.title = \\"Katana IDE\\";\r\nthis.window.write(\\"<div id=\\''ide_toolbar\\"+this.instance+\\"\\'' style=\\''height: 3%; width: 100%;\\''></div>\\");\r\nthis.window.write(\\"<div id=\\''ide_body\\"+this.instance+\\"\\'' style=\\''height: 97%; width: 100%;\\''>\\");\r\nthis.window.write(\\"<div id=\\''ide_funclist\\"+this.instance+\\"\\'' style=\\''width: 150px; height: 100%;\\''></div>\\");\r\nthis.window.write(\\"<div id=\\''ide_codearea\\"+this.instance+\\"\\'' style=\\''height: 100%;\\''></div>\\");\r\nthis.window.write(\\"</div>\\");\r\nthis.window.show();\r\nthis.body = dojo.widget.createWidget(\\"splitContainer\\", {id: \\"ide_body\\"+this.instance, orientation: \\"vertical\\"}, document.getElementById(\\"ide_body\\"+this.instance));\r\n\r\nthis.funclist = dojo.widget.createWidget(\\"ContentPane\\", {id: \\"ide_funclist\\"+this.instance}, document.getElementById(\\"ide_funclist\\"+this.instance));\r\n\r\nthis.codearea = dojo.widget.createWidget(\\"ContentPane\\", {id: \\"ide_codearea\\"+this.instance}, document.getElementById(\\"ide_codearea\\"+this.instance));\r\n\r\nget this working later\r\nthis.toolbar = document.getElementById(\\"ide_toolbar\\"+this.instance);\r\nthis.toolbar = dojo.widget.createWidget(\\"Toolbar\\", {}, this.toolbar);\r\nthis.filemenu = this.toolbar.addChild(\\"File\\", null, {name: \\"File\\"});\r\nthis.filemenu.addChild(\\"New\\", null, {name: \\"File\\", onClick: this.hitch(this.new)});\r\n*/', 'this.load = function(appID)\r\n{\r\n     var url = \\"../backend/app.php?id=\\"+appID;\r\n     dojo.io.bind({\r\n          url: url,\r\n          load: this.hitch(this.doLoad),\r\n          mimetype: \\"text/plain\\"\r\n     });\r\n}\r\n\r\nthis.doLoad = function(type, data, evt)\r\n{\r\n     data = data.split(\\"[==separator==]\\");\r\n     init = data[4];\r\n     funcs = data[5];\r\n     funcs = funcs.split(\\"\\\\\\\\n\\");\r\n     level = 0;\r\n     buffer = \\"\\";\r\n     code = new Array();\r\n     funccount = 0;\r\n     funcnames = new Array();\r\n     for(line in funcs)\r\n     {\r\n          line = buffer+line;\r\n          buffer = \\"\\";\r\n          //if line contains a \\''{\\'', add one to level\r\n          if(level == 1)\r\n          {\r\n               //get the function name and add it to funcnames[funccount], and add anything after the \\''{\\'' to code[funccount]\r\n          }\r\n          //if line contains a \\''}\\'' subtract one from level\r\n          if(level >= 1)\r\n          {\r\n               code[funccount] += line;\r\n          }\r\n          if(level == 0) // && line contains a \\''}\\''\r\n          {\r\n               //add everything before the last \\''}\\'' to code, if there\\''s anything after the last \\''}\\'', add it to buffer\r\n               funccount++;\r\n          } \r\n     }\r\n     if(level != 0)\r\n     {\r\n          //throw an error at the user\r\n     }\r\n     else\r\n     {\r\n          this.addFunc(\\"init\\", init);\r\n          c = 0;\r\n          for(function in code)\r\n          {\r\n               this.addFunc(funcnames[c], function);\r\n               c++;\r\n          }\r\n     }\r\n}\r\n\r\nthis.addFunc = function(name, code)\r\n{\r\n     //add a func to the ide\\''s GUI\r\n     if(name == \\"init\\")\r\n     {\r\n          this.functions[0] = new Object();\r\n          this.functions[0].name = name;\r\n          this.functions[0].code = code;\r\n          document.getElementById(\\"\\"+this.instance).innerHTML = \\"\\";\r\n     }\r\n     else\r\n     {\r\n          this.functions[this.functions.length] = new Object();\r\n          this.functions[this.functions.length-1].name = name;\r\n          this.functions[this.functions.length-1].name = name;\r\n     }\r\n}\r\n', '1.0', 'Stable', 'System');

-- 
-- Table structure for table `#__users`
-- 
DROP TABLE IF EXISTS `#__users`;
CREATE TABLE `#__users` (
  `username` mediumtext NOT NULL,
  `email` mediumtext NOT NULL,
  `password` mediumtext NOT NULL,
  `logged` tinyint(1) NOT NULL default '0',
  `ID` int(11) NOT NULL auto_increment PRIMARY KEY,
  `level` mediumtext NOT NULL
) TYPE=MyISAM CHARACTER SET `utf8` COLLATE `utf8_general_ci` AUTO_INCREMENT=1 ;

-- Registry
DROP TABLE IF EXISTS `#__registry`;
CREATE TABLE `#__registry` (
  `ID` int(11) NOT NULL auto_increment PRIMARY KEY,
  `userid` int(11) NOT NULL,
  `appid` int(20) NOT NULL,
  `varname` mediumtext NOT NULL,
  `value` mediumtext NOT NULL
) TYPE=MyISAM CHARACTER SET `utf8` COLLATE `utf8_general_ci` AUTO_INCREMENT=1 ;

-- filesystem
DROP TABLE IF EXISTS `#__filesystem`;
CREATE TABLE `#__filesystem` (
  `ID` int(11) NOT NULL auto_increment PRIMARY KEY,
  `userid` int(11) NOT NULL,
  `file` mediumtext NOT NULL,
  `directory` mediumtext NOT NULL,
  `location` mediumtext NOT NULL
) TYPE=MyISAM CHARACTER SET `utf8` COLLATE `utf8_general_ci` AUTO_INCREMENT=1 ;
REPLACE INTO `#__filesystem` VALUES (1,1,"ReadMe.txt","docs/","readme.txt");