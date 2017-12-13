"use strict";
angular.module('api').factory('$measure', function(http)
{
    return {
        downChannel: function(info, callback)
        {
            var path = "/measure/downChannel";
            var param = {};
            http.getRequest(path, param, callback);
        },
        upChannel: function(info, callback)
        {
            var path = "/measure/upChannel";
            var param = {};
            http.postRequest(path, param, callback);
        },
      
    };
});