dojo.hostenv.packageLoaded({
depends: [["kwCompoundRequire", {
    browser: 	[
    	"qu.util.Date",
    	"3rd.jscalendar.calendar",
    	"3rd.jscalendar.calendar-setup",
    	"3rd.jscalendar.lang.calendar-en"
    	]
}],
["provide", "3rd.jscalendar.*"]],
definePackage: function(dojo){dojo.kwCompoundRequire({
    browser: 	[
    	"qu.util.Date",
    	"3rd.jscalendar.calendar",
    	"3rd.jscalendar.calendar-setup",
    	"3rd.jscalendar.lang.calendar-en"
    	]
});
dojo.provide("3rd.jscalendar.*");

}});