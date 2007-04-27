/**********************************\
|           Psych Desktop          |
|            API Library           |
|      (c) 2006 Psych Designs      |
| All functions here can be called |
| 	  via api.functionname();      |
\**********************************/
function api() {
    //Window API
    this.window = function() { }
    this.window = new this.window();    
    this.window = function()
    {
    	this.id = "win"+windowcounter;
    	windowcounter++;
    	this.innerHTML = "";
    	this.height = "400px";
    	this.width = "500px";
    	this.title = "";
    	this.empty = function()
    	{
    		this.window.innerHTML = "";
    		if(document.getElementById(this.id))
    		{
    			document.getElementById(this.window.id).innerHTML = "";
    		}
    	}
    	this.write = function(string)
    	{
    		this.innerHTML += string;
    		if(document.getElementById(this.id))
    		{
    			document.getElementById(this.id).innerHTML += string;
    		}		
    	}
    	this.show = function()
    	{
    		windiv=document.createElement("div");
    		windiv.style.position="absolute";
    		windiv.style.top = "5%";
    		windiv.style.left = "5%";
    		windiv.style.width=this.width;
    		windiv.style.height=this.height;
    		windiv.style.zindex="100";
    		windiv.innerHTML=this.innerHTML;
    		document.getElementById("windowcontainer").appendChild(windiv);
    		this.window = dojo.widget.createWidget("FloatingPane", {
    			hasShadow: true,
    			resizable: true,
    			displayCloseAction: true,
    			title: this.title,
    			displayMaximizeAction: true,
    			displayMinimizeAction: true,
    			taskBarId: "appbar",
    			toggle: "explode",
    			toggleDuration: 300,
    			constrainToContainer: true
    		}, windiv);		
    	}
    	this.destroy = function()
    	{
    		if(this.window) { this.window.destroy(); }
    		else { api.toaster("Warning in app: No window shown."); }
    	}
    }    
    // Registry API

    this.registry = function() { }
    this.registry = new this.registry();

    this.registry.getValue = function(appid,varname,callback)
    {
        api.registry.callback = callback;
        ui_loadingIndicator(0);
        var url = "../backend/api.php?registry=load&appid="+appid+"&varname="+varname;
        dojo.io.bind({
        url: url,
        load: function(type, data, http) { api.registry.processRegistryGet(type, data, http, callback); },
        error: sys_toastererr, mimetype: "text/plain"
        });
    }
    this.registry.saveValue = function(appid,varname,value)
    {
        ui_loadingIndicator(0);
        var url = "../backend/api.php?registry=save&appid="+appid+"&varname="+varname+"&value="+value;
        dojo.io.bind({
            url: url,
            error: sys_toastererr,
            mimetype: "text/plain"
        });
        ui_loadingIndicator(1);
    }
    this.registry.processRegistryGet = function(type, data, evt, callback)
    {
        api.registry.value = data;
        if(callback) { callback(data); }
        ui_loadingIndicator(1);
    }

    //filesystem api
    this.fs = function() { }
    this.fs = new this.fs();

    this.fs.getFile = function(file,directory,callback)
    {
        ui_loadingIndicator(0);
        var url = "../backend/api.php?fs=load&file="+file+"&directory="+directory;
        dojo.io.bind({url: url,        load: function(type, data, http) { api.fs.getFileProcess(type, data, http, callback); },
        error: sys_toastererr, mimetype: "text/xml"
        });
    }
	this.fs.listFiles = function(callback)
    {
        ui_loadingIndicator(0);
        var url = "../backend/api.php?fs=list";
        dojo.io.bind({
        url: url,
        load: function(type, data, http) { api.fs.fileListProcess(type, data, http, callback); },
        error: sys_toastererr,
        mimetype: "text/xml"
        });
    }
	this.fs.getFileResult = new Array();
	this.fs.listFilesResult = new Array(99,99);
    this.fs.getFileProcess = function(type, data, evt, callback)
    {
        var results = data.getElementsByTagName('contents');
		api.fs.getFileResult["contents"] = results[0].firstChild.nodeValue;
		api.fs.getFileResult["file"] = results[0].getAttribute("file");
		api.fs.getFileResult["directory"] = results[0].getAttribute("directory");
        if(callback) { callback() }
        ui_loadingIndicator(1);
        api.toaster("Security Note: FileSystem was accessed.");
    }
	this.fs.fileListProcess = function(type, data, evt, callback)
    {
		var results = data.getElementsByTagName('file');
		for(var i = 0; i<results.length; i++){
		api.fs.listFilesResult["count"] = i;
		api.fs.listFilesResult[i] = {};
		api.fs.listFilesResult[i]["file"] = results[i].firstChild.nodeValue;
		api.fs.listFilesResult[i]["directory"] = results[i].getAttribute("directory");
		}
        if(callback) { callback() }
        ui_loadingIndicator(1);
        api.toaster("Security Note: FileSystem was accessed.");
    }
    }
api = new api();

function sys_toastererr(type, error)
{
    api.toaster("Error in AJAX call: "+error.message);
    ui_loadingIndicator(1);
}