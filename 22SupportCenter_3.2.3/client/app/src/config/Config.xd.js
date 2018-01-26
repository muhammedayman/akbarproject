dojo.hostenv.packageLoaded({
depends: [["provide", "app.config.Config"],
["require", "qu.core.Config"]],
definePackage: function(dojo){dojo.provide("app.config.Config");

dojo.require("qu.core.Config");

dojo.lang.declare("app.config.Config", qu.core.Config, 
	//initializer
	function() {
		this.currentIconSet = this.defaultIconSet;
		this.pathIcons = this.iconPathPrefix + this.currentIconSet;
		this.setvicePath = this.hostPath + "/.." + "/server/services/";
	},

{
	aplicationName: "supportCenter",
	version: "3.2.3",

	pathTemplate: "app/template",
	iconPathPrefix: "lib/icons/",
	defaultIconSet: "crystalsvg",

	interfaceLanguage: "English"

});

}});