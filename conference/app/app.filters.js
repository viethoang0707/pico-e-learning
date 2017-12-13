/**
 * @author thanhvk
 * created on 10.10.2016
 */
 'use strict';

angular.module('app')
.filter('decodeBase64', ['$base64', function($base64) {
    return function(base64Str) {
        var url = '';

        if (base64Str) {
            url = $base64.decode(base64Str);
        }

        return url;
    }   
}]);