dojo.require("dijit.layout.ContentPane");
dojo.require("dijit.layout.StackContainer");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.CheckBox");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.form.Form");
dojo.require("dijit.ProgressBar");
install = new function() {
	this.selected = function(page){
		dijit.byId("previous").setDisabled(page.isFirstChild);
		if(page.isLastChild)
		{
			dijit.byId("previous").setDisabled(true);
			dijit.byId("next").setDisabled(false);
			dijit.byId("next").onClick = function() {
				window.location = "../";
			}
		}
		install.currentPage = page;
		if(page.title == "Start" || 
		   page.title == "installation type" ||
		   page.title == "Database" ||
		   page.title == "Installing")
		{
			dijit.byId("next").setDisabled(true);
		}
		if(page.title == "Installing")
		{
			dijit.byId("previous").setDisabled(true);
		}
	}
	this.onLoad = function() {
		dojo.subscribe("wizard-selectChild", install.selected);
		install.selected(dijit.byId("start"));
		dijit.byId("next").setDisabled(install.currentPage);
		install.getPerms();
		setInterval(install.getPerms, 2000);
		setInterval(install.checkDbInput, 1000);
		dojo.forEach(['installtype-new', 'installtype-cms'], function(e)
		{
			dijit.byId(e)._clicked = install.onTypeRadioClick;
		});
		dijit.byId("installtype-reset")._clicked = install.onResetRadioClick;
	}
	this.checkDbInput = function() {
		if (install.currentPage.title == "Database") {
			var form = dijit.byId("form").getValues();
			if(form.db_type != "" &&
			   form.db_name != "" &&
			   form.db_prefix != "" &&
			   form.db_username != "" &&
			   form.db_password != "")
			{
				dijit.byId("next").setDisabled(false);
			}
			else if(dijit.byId("next").disabled == false)
				dijit.byId("next").setDisabled(true);
		}
	}
	this.getPerms = function()
	{
		if (install.currentPage.title == "Start") {
			dojo.xhrGet({
				url: "./backend.php?action=checkpermissions",
				load: function(data, args){
					var html = "<ul>";
					var ready = true;
					for (key in data) {
						html += "<li>" + key.replace("../", "") + ": ";
						if (data[key] == "ok") 
							html += "<span style='color: green'>";
						else {
							html += "<span style='color: red'>";
							ready = false;
						}
						html += data[key] + "</span></li>";
					}
					html += "</ul>";
					dojo.byId("perms").innerHTML = html;
					dijit.byId("next").setDisabled(!ready);
				},
				handleAs: "json"
			});
		}
	}
	this.onTypeRadioClick = function(e) {
		if(!this.checked) {
			this.setChecked(true);
			dijit.byId("next").setDisabled(false);
			dijit.byId("next").onClick = function(e) {
				this.onClick = function(e) { dijit.byId('wizard').forward(); }
			};
		}
	}
	this.onResetRadioClick = function(e) {
		if(!this.checked) {
			this.setChecked(true);
			dijit.byId("next").setDisabled(false);
			dijit.byId("next").onClick = function(e) {
				dijit.byId("wizard").selectChild("install-page");
				this.onClick = function(e) { dijit.byId('wizard').forward(); }
			};
		}
	}
}
dojo.addOnLoad(install.onLoad);