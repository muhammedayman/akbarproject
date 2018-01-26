dojo.hostenv.packageLoaded({
depends: [["provide", "qu.widget.DropdownDatePicker"],
["require", "dojo.widget.DropdownDatePicker"]],
definePackage: function(dojo){dojo.provide("qu.widget.DropdownDatePicker");

dojo.require("dojo.widget.DropdownDatePicker");

dojo.widget.defineWidget("qu.widget.DropdownDatePicker", dojo.widget.DropdownDatePicker, 
{

	namespace: "qu",
	quId: ""
	
});

}});