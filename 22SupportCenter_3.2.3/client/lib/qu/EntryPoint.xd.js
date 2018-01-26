dojo.hostenv.packageLoaded({
depends: [["provide", "qu.EntryPoint"],
["require", "dojo.lang.declare"],
["require", "dojo.debug.console"],
["require", "app.view.Templates"],
["require", "app.data.Services"],
["require", "dojo.uri.Uri"],
["require", "dojo.undo.browser"],
["require", "dojo.event.*"],
["require", "qu.core.I18n"],
["require", "qu.rpc.DataPusher"],
["require", "qu.Global"],
["require", "qu.core.User"],
["require", "app.config.Config"],
["require", quConfig.loginClass],
["require", quConfig.mainClass],
["require", quConfig.logoutClass]],
definePackage: function(dojo){dojo.provide("qu.EntryPoint");

dojo.require("dojo.lang.declare");
dojo.require("dojo.debug.console");
dojo.require("app.view.Templates");
dojo.require("app.data.Services");
dojo.require("dojo.uri.Uri");
dojo.require("dojo.undo.browser");
dojo.require("dojo.event.*");

dojo.lang.declare("qu.EntryPoint", null, 
{
	initSingletons: function() {
		dojo.require("qu.core.I18n");
		if (quConfig.loginLanguage.length > 0) {
			app.i18n = new qu.core.I18n(quConfig.loginLanguage);
		} else {
			app.i18n = new qu.core.I18n(quConfig.interfaceLanguage);
		}

		dojo.require("qu.rpc.DataPusher");
		app.dataPusher = new qu.rpc.DataPusher();

		if(typeof(app.i18n.loading) != "undefined") {
			quThrobber.setMessage(app.i18n.get("loading"));
		}
	},
	
	initParams: function() {
		//app.url = new dojo.uri.Uri(window.location);
		var url = window.location.toString();
		var fragment = null;
		if (url.indexOf('?') > 0) {
			fragment = url.substring(url.indexOf('?')+1);
			fragment = fragment.replace(/#/, '&');
		} else if(url.indexOf('#') > 0) {
			fragment = url.substring(url.indexOf('#')+1);
		}
		if(fragment) {
			var fragments = fragment.split("&");
			app.params = {
				count: fragments.length,
				string: fragment
			}
			var param, indexOf;
			for(var index = 0; index < fragments.length; index++) {
				param = new String(fragments[index]);
				if(param.indexOf("=") > 0) {
					app.params[param.substring(0, param.indexOf("="))] = param.substring(param.indexOf("=") + 1);
				} else {
					app.params[param] = true;
				}
			}
		} else {
			app.params = {
				count: 0,
				string: ""	
			}
		}
	},
	
	initConnections: function() {
		dojo.event.connectOnce(window, "onresize", this, "resizeBackground");
	},
	
	resizeBackground: function() {
		var h = Math.max(document.documentElement.scrollHeight || document.body.scrollHeight,
		dojo.html.getViewport().height);
		
		var w = dojo.html.getViewport().width;
		with(app.background.style) {
			width = w + "px";
			height = h + "px";
		}
		app.iframe.size(dojo.body());
	},
	
	boot: function() {
		dojo.require("qu.Global");
		this.initSingletons();
		dojo.require("qu.core.User");
		dojo.require("app.config.Config");
		app.config = new app.config.Config();
	},
	
	launch: function(inMainClass) {
		this.initParams();
		this.initConnections();
		this.mainClass = inMainClass || quConfig.mainClass;
		var execClass = this.mainClass;
		if(quConfig.isLoginRequired 
			&& !qu.user.isLoggedIn()) {
			execClass = quConfig.loginClass;
		}
		dojo.require(quConfig.loginClass);
		dojo.require(quConfig.mainClass);
		dojo.byId("quContent").innerHTML = "";
		app.main = eval("new " + execClass + "(dojo.byId('quContent'))");
		app.main["main"]();
	},
	
	logout: function() {
		dojo.undo.browser.addToHistory({changeUrl: "login"});
		qu.user.logout();
		dojo.require(quConfig.logoutClass);
		this.removeWidgets();
		app.main.dispose();
		dojo.byId("quContent").innerHTML = "";
		app.main = eval("new " + quConfig.logoutClass + "(dojo.byId('quContent'))");
		app.main["main"]();
		window.location = quConfig.bootFilename + "?logout";
	},
	
	forceLogout: function() {
		qu.user.logout(false);
		dojo.byId("quContent").innerHTML = app.i18n.get("sessionNotActive");
		window.location = quConfig.bootFilename;
	},
	
	removeWidgets: function() {
		for(var x=dojo.widget.manager.widgets.length-1; x>=0; x--){
			try{
				dojo.widget.manager.remove(x);
				delete dojo.widget.manager.widgets[x];
			}catch(e){ }
		}
	},
	
	setHeaderVisible: function(inBool) {
		if(inBool === true) {
			dojo.byId("quHeader").style.display = "block";
		} else {
			dojo.byId("quHeader").style.display = "none";
		}
	},
	
	setFooterVisible: function(inBool) {
		if(inBool === true) {
			dojo.byId("quFooter").style.display = "block";
		} else {
			dojo.byId("quFooter").style.display = "none";
		}
	}
	
});

//Error object normalization
Error.prototype.toString = function() {
	var errMsg = this.message;
	if(app && app.i18n) {
		errMsg = app.i18n.get("error") + ": " + errMsg;
	};
	return errMsg;
}

qu.boot = new qu.EntryPoint();

}});