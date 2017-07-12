;(function (window, undefined) {

	"use strict";

	function HashRouter() {
		var salf = this;

		this._routes = {};

		window.addEventListener('load', function () {
			salf.action();
		});

		window.addEventListener('hashchange', function () {
			salf.action();
		});

	}

	HashRouter.prototype.add = function (route, callback) {
		if (!this._routes[route]) this._routes[route] = [];
		this._routes[route].push( {'callback': callback} ); 
	};

	HashRouter.prototype.action = function () {
		var salf = this;

		var route = this.getRouteName();

		if (this._routes[route]) {			
			this._routes[route].forEach(function (route) {
				route.callback.call(salf, salf.getParams());
			});
		}

	};

	HashRouter.prototype.getParams = function () {
		var query = window.location.hash.substr(1).split("?")[1];
		var params = query ? query.split("&") : [];

		var paramsObject = {};
		for(var i = 0; i < params.length; i++) {
		    var a = params[i].split("=");

		    if (a[1] === undefined || a[1] === ''){
		    	paramsObject[a[0]] = null	
		    } else {
		    	paramsObject[a[0]] =  decodeURIComponent(a[1]);
		    }
		}
		
		return paramsObject;

	};

	HashRouter.prototype.getRouteName= function () {
		return window.location.hash.substr(1).split("?")[0];
	};

	window.HashRouter = HashRouter;

})(window);