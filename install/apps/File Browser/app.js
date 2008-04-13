({
	windows: [],
	init: function(args)
	{
		dojo.require("dijit.Toolbar");
		dojo.require("dijit.layout.LayoutContainer");
		dojo.require("dijit.layout.SplitContainer");
		dojo.require("dijit.form.Button");
		dojo.require("dijit.form.TextBox");
		dojo.require("dijit.Dialog");
		api.addDojoCss("dojox/widget/FileInput/FileInput.css");
		dojo.require("dojox.widget.FileInputAuto");
		dojo.requireLocalization("desktop", "common");
		dojo.requireLocalization("desktop", "apps");
		dojo.requireLocalization("desktop", "places");
		
		var cm = dojo.i18n.getLocalization("desktop", "common");
		var app = dojo.i18n.getLocalization("desktop", "apps");
		var places = dojo.i18n.getLocalization("desktop", "places");
		this.win = new api.Window({
			title: app["File Browser"],
			onClose: dojo.hitch(this, this.kill)
		});
			this.client = new dijit.layout.SplitContainer({layoutAlign: "client"});
			this.fileArea = new api.Filearea({path: (args.path || "file://"), sizeShare: 70})
			this.pane = new dijit.layout.ContentPane({sizeMin: 0, sizeShare: 30});
			var menu = new dijit.Menu({
				style: "width: 100%;"
			});
			dojo.forEach(desktop.config.filesystem.places, function(item) {
				var item = new dijit.MenuItem({label: places[item.name] || item.name,
					iconClass: item.icon || "icon-16-places-folder",
					onClick: dojo.hitch(this.fileArea, "setPath", item.path)
				});
				menu.addChild(item);
			}, this);
			this.pane.setContent(menu.domNode);
			this.client.addChild(this.pane);
			this.client.addChild(this.fileArea);
			
			this.pathbar = new dijit.Toolbar({layoutAlign: "top"});
			this.pathbox = new dijit.form.TextBox({
				style: "width: 90%;",
				value: args.path || "file://"
			});
			dojo.connect(this.fileArea, "onPathChange", this, function() {
				this.pathbox.setValue(this.fileArea.path);
			});
			this.pathbar.addChild(this.pathbox);
			this.goButton = new dijit.form.Button({
				style: "position: absolute; top: 0px; right: 0px;",
				label: cm.go,
				onClick: dojo.hitch(this, function() {
					this.fileArea.setPath(this.pathbox.getValue());
				})
			});
			this.pathbar.addChild(this.goButton);
			
			
		this.toolbar = new dijit.Toolbar({layoutAlign: "top"});
			var button = new dijit.form.Button({
				onClick: dojo.hitch(this.fileArea, function() {
					this.setPath("file://");
				}),
				iconClass: "icon-16-places-user-home",
				label: places.Home
			});
			this.toolbar.addChild(button);
			var button = new dijit.form.Button({
				onClick: dojo.hitch(this.fileArea, this.fileArea.up),
				iconClass: "icon-16-actions-go-up",
				label: cm.up
			});
			this.toolbar.addChild(button);
			var button = new dijit.form.Button({
				onClick: dojo.hitch(this.fileArea, this.fileArea.refresh),
				iconClass: "icon-16-actions-view-refresh",
				label: cm.refresh
			});
			this.toolbar.addChild(button);
			this.upbutton = new dijit.form.Button({
				onClick: dojo.hitch(this, "openUploader"),
				iconClass: "icon-16-actions-mail-send-receive",
				label: cm.upload,
			});
			this.toolbar.addChild(this.upbutton);
		this.win.addChild(this.toolbar);
		this.win.addChild(this.pathbar);
		this.win.addChild(this.client);
		this.win.show();
		this.win.startup();
		this.win.onDestroy = dojo.hitch(this, this.kill);
		this.fileArea.refresh();
		this.uploader.startup();
	},
	
	openUploader: function() {
		var cm = dojo.i18n.getLocalization("desktop", "common");
		var uploader = new dojox.widget.FileInputAuto({
			name: "uploadedfile",
			url: api.xhr("api.fs.io.upload")+"&path="+encodeURIComponent(this.fileArea.path),
			onComplete: dojo.hitch(this, function(data, ioArgs, widgetRef){
				widgetRef.setMessage(data.status+": "+data.details);
				this.fileArea.refresh();
			})
		});
		var win = new api.Window({
			title: cm.upload,
			width: "400px",
			height: "100px"
		});
		this.windows.push(win);
		var cpane = new dijit.layout.ContentPane({
			layoutAlign: "client",
			style: "padding: 10px;"
		});
		var div = document.createElement("div");
		dojo.addClass(div, "tundra");
		div.appendChild(uploader.domNode);
		cpane.setContent(div);
		win.addChild(cpane);
		
		
		var bpane = new dijit.layout.ContentPane({
			layoutAlign: "bottom"
		});
		var div = document.createElement("div");
		var button = new dijit.form.Button({
			label: cm.close,
			onClick: dojo.hitch(win, "close")
		});
		div.appendChild(button.domNode);
		dojo.addClass(div, "floatRight");
		bpane.setContent(div);
		win.addChild(bpane);
		
		dojo.style(uploader.inputNode, "width", "163px");
		uploader.startup();
		win.show();
	},
	
	kill: function() {
		if(!this.win.closed) { this.win.close(); }
		dojo.forEach(this.windows, function(win) {
			if(!win.closed) win.close();
		});
	}
})