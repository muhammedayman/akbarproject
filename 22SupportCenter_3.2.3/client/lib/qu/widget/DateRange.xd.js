dojo.hostenv.packageLoaded({
depends: [["provide", "qu.widget.DateRange"],
["require", "dojo.widget.HtmlWidget"],
["require", "qu.widget.DropdownDatePicker"],
["require", "qu.widget.ComboBox"]],
definePackage: function(dojo){dojo.provide("qu.widget.DateRange");

dojo.require("dojo.widget.HtmlWidget");
dojo.require("qu.widget.DropdownDatePicker");
dojo.require("qu.widget.ComboBox");

dojo.widget.defineWidget("qu.widget.DateRange", dojo.widget.HtmlWidget, 

function () {
	this.fuzzyRanges = [
		["today",  function() {
			var today = qu.widget.DateRange.getToday();
			return {
				from: today,
				to: today
			}
		}
		],
		["yesterday", function() {
			var yesterday = new Date();
			yesterday.setTime(yesterday.getTime() - qu.widget.DateRange.day);
			return {
				from: yesterday,
				to: yesterday
			};
		}
		],
		["thisWeek", function() {
			var today = qu.widget.DateRange.getToday();
			var start = today.valueOf() - (qu.widget.DateRange.day * (today.getDay() - 1));
			var end = today.valueOf() + (qu.widget.DateRange.day * 6) - (qu.widget.DateRange.day * (today.getDay() - 1));
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["lastWeek", function() {
			var today = qu.widget.DateRange.getToday();
			var start = today.valueOf() - (qu.widget.DateRange.day * (today.getDay() - 1)) - (qu.widget.DateRange.day * 7);
			var end = today.valueOf() + (qu.widget.DateRange.day * 6) - (qu.widget.DateRange.day * (today.getDay() - 1)) - (qu.widget.DateRange.day * 7);
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["last7Days", function() {
			var today = qu.widget.DateRange.getToday();
			var start = today.valueOf() - (qu.widget.DateRange.day * 7);
			var end = today.valueOf();
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["last30Days", function() {
			var today = qu.widget.DateRange.getToday();
			var start = today.valueOf() - (qu.widget.DateRange.day * 30);
			var end = today.valueOf();
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["thisMonth", function() {
			var today = qu.widget.DateRange.getToday();
			var startDay = new Date(today.getFullYear(), today.getMonth(),1);
			var endDay = new Date(today.getFullYear(), today.getMonth(), today.getMonthDays(today.getMonth()));
			
			var start = startDay.valueOf();
			var end = endDay.valueOf();
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["lastMonth", function() {
			var today = qu.widget.DateRange.getToday();
			var startDay = new Date(today.getFullYear(), today.getMonth()-1,1);
			var endDay = new Date(today.getFullYear(), today.getMonth()-1, today.getMonthDays(startDay.getMonth()));
			
			var start = startDay.valueOf();
			var end = endDay.valueOf();
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["thisYear", function() {
			var today = qu.widget.DateRange.getToday();
			var startDay = new Date(today.getFullYear(), 0,1);
			var endDay = new Date(today.getFullYear(), 11, 31);
			
			var start = startDay.valueOf();
			var end = endDay.valueOf();
			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}],
		["lastYear", function() {
			var today = qu.widget.DateRange.getToday();
			var startDay = new Date(today.getFullYear()-1, 0,1);
			var endDay = new Date(today.getFullYear()-1, 11, 31);

			var start = startDay.valueOf();
			var end = endDay.valueOf();

			return {
				from: new Date().setTime(start),
				to: new Date().setTime(end)
			}
		}]
	]
},

{
	
	namespace: "qu",
	widgetType: "DateRange",
	isContainer: false,
	custom: false,
	quId: "",
	
	templateString: '<div>' +
			'<div dojoAttachPoint="fuzzyNode" style="float:left;margin-right:4px;"></div>' +
			'<span dojoAttachPoint="fromNode" style="margin-right:4px;"></span>' +
			'<span dojoAttachPoint="toNode"></span>' +
			'</div>',
	templateCssPath: dojo.uri.dojoUri("../qu/widget/templates/css/DateRange.css?" + djConfig.cacheBust),
	
	fillInTemplate: function() {
		this.createSubwidgets();
		this.initSubwidgets();
		this.initConnections();
	},
	
	createSubwidgets: function() {
		this.fuzzyWidget = new dojo.widget.createWidget("qu:ComboBox");
		this.fuzzyNode.appendChild(this.fuzzyWidget.domNode);
		
		this.fromWidget = new dojo.widget.createWidget("qu:DropdownDatePicker");
		this.fromWidget.displayFormat = "yyyy-MM-dd";
		this.fromWidget.startDate = "2000-01-01";
		this.fromWidget.endDate = "2030-12-31";
		this.fromNode.appendChild(this.fromWidget.domNode);
		
		this.toWidget = new dojo.widget.createWidget("qu:DropdownDatePicker");
		this.toWidget.displayFormat = "yyyy-MM-dd";
		this.toWidget.startDate = "2000-01-01";
		this.toWidget.endDate = "2030-12-31";
		this.toNode.appendChild(this.toWidget.domNode);
	},
	
	initSubwidgets: function() {
		this.fuzzyWidget.add("", this._getI18n("whenever"));
		dojo.lang.forEach(this.fuzzyRanges, function(elm, index, arr){
			var caption = this._getI18n(elm[0]);
			this.fuzzyWidget.add(elm[0], caption);
		}, this);
		this.setValue("");
	},
	
	initConnections: function() {
		dojo.event.connectOnce(this.fuzzyWidget, "onChange", this, "onFuzzyChange");
		dojo.event.connect(this.fromWidget, "onValueChanged", this, "onCustom");
		dojo.event.connect(this.toWidget, "onValueChanged", this, "onCustom");
	},
	
	onCustom: function() {
		this.fuzzyWidget.setCaption(this._getI18n("custom"));
		this.custom = true;
		this.onChange();
	},
	
	onFuzzyChange: function() {
		this.custom = false;
		dojo.event.disconnect(this.fromWidget, "onValueChanged", this, "onCustom");
		dojo.event.disconnect(this.toWidget, "onValueChanged", this, "onCustom");
		if(this.fuzzyWidget.getSelection() == "") {
			this.fromWidget.inputNode.value = "";
			this.toWidget.inputNode.value = "";
		} else {
			var fuzzyFunction = this._getFuzzyRange(this.fuzzyWidget.getSelection());
			result = fuzzyFunction();
			this.setRange(result.from, result.to);
			
		}
		dojo.event.connect(this.fromWidget, "onValueChanged", this, "onCustom");
		dojo.event.connect(this.toWidget, "onValueChanged", this, "onCustom");
		this.onChange();
	},
	
	onChange: function() {
		//stub method
	},
	
	_getFuzzyRange: function(inKey) {
		var result = null;
		dojo.lang.forEach(this.fuzzyRanges, function(elm, index, arr) {
			if(inKey == elm[0]) {
				result = elm[1];
			}
		});
		return result;
	},
	
	_getI18n: function(inKey)  {
		if(typeof app != "undefined" && typeof app.i18n != "undefined") {
			return app.i18n.get(inKey);
		}
		return inKey
	},
	
	setRange: function(inFrom, inTo) {
		this.fromWidget.setDate(inFrom);
		this.toWidget.setDate(inTo);
	},
	
	getRange: function() {
		var from = this.fromWidget.getDate();
		var to = this.toWidget.getDate(); 
		if(from instanceof Date && dojo.string.trim(this.fromWidget.inputNode.value) != "") {
			from.setHours(0);
			from.setMinutes(0);
			from.setSeconds(0);
			from.setMilliseconds(0);
		} else {
			from = new String("");
		}
		if(to instanceof Date && dojo.string.trim(this.toWidget.inputNode.value) != "") {
			to.setHours(23);
			to.setMinutes(59);
			to.setSeconds(59);
			to.setMilliseconds(999);
		} else {
			to = new String("");
		}
		return {
			from: from,
			to: to
		}
	},
	
	toString: function() {
		var range = this.getRange();
		return [range.from.toString(), range.to.toString()];
	},
	
	getFrom: function() {
		var range = this.getRange();
		var ret = "";
		if (range.from instanceof Date) {
			ret = range.from.print("%Y-%m-%d %H:%M:%S");
		} else {
			ret = range.from.toString();
		}
		return ret;
	},

	getTo: function() {
		var range = this.getRange();
		if (range.to instanceof Date) {
			return range.to.print("%Y-%m-%d %H:%M:%S");
		} else {
			return range.to.toString();
		}
	},
	
	getFuzzyRange: function() {
		if (this.custom !== false) {
			return "custom";
		} else {
			return this.fuzzyWidget.getSelection();
		}
	},
	
	setValue: function(fuzzyValue, fromValue, toValue) {
		if (fuzzyValue == "custom") {
			this.setRange(fromValue, toValue);
			this.onCustom();
		} else {
			this.fuzzyWidget.select(fuzzyValue);
			this.onFuzzyChange();
		}
	}
});

qu.widget.DateRange.getToday = function() {
	var today = new Date();
	today.setHours(0);
	today.setMinutes(0);
	today.setSeconds(0);
	today.setMilliseconds(0);
	return today;
};

qu.widget.DateRange.day = 86400000; //miliseconds per day

}});