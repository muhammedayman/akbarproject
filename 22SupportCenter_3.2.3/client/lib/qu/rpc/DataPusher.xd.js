dojo.hostenv.packageLoaded({
depends: [["provide", "qu.rpc.DataPusher"],
["require", "qu.sql.ResultSet"]],
definePackage: function(dojo){dojo.provide("qu.rpc.DataPusher");

dojo.require("qu.sql.ResultSet");

dojo.lang.declare("qu.rpc.DataPusher", null,
	function () {
	},
	{
		push: function(data) {
			for (var i in data) {
				var pushedData = data[i];
				var rs = new qu.sql.ResultSet();
				try {
					if(pushedData != null) {
						rs.createFromObject(pushedData);
					}
				} catch (inError) {
					dojo.debug("Error creating ResultSet from object: " + i);
					return;
				}
				this.onLoad(i, rs);
			}
		},
		
		//other objects will connect to this method if they will want to listen pusher
		onLoad: function (id, inResult) {
		}
	}		
);

}});