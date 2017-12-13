"use strict";
angular.module('api').factory('$auth', function(http)
{
    return {
        loginAccount: function(info, callback)
        {
            var path = "/auth/local";
            var param = {
                email: info.email,
                password: info.password
            };
            http.postRequest(path, param, callback);
        }          
    };
});