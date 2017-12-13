/**
 * @author thanhvk
 * created on 30.09.2016
 */
'use strict';

angular.module('app')
    .service('sharedProperties', function () {
        var objectValue = null;

        return {
            setObject: function(obj) {
                objectValue = obj;
            },
            getObject: function () {
                return objectValue;
            }            
        };
    });
angular.module('app')    
    .service('viMdToast', function($mdToast) {
        var position        = 'top right',
            timeDelay       = 2000,
            errorClass      = 'vi-toast-danger',
            successClass    = 'vi-toast-success';

        function showToast(msg, msgClass) {
            $mdToast.show(
                $mdToast.simple()
                    .textContent(msg)
                    .position(position)
                    .toastClass(msgClass)
                    .hideDelay(timeDelay)
            );
        }
        
        function error(msg) {
            showToast(msg, errorClass);
        }

        function success(msg) {
            showToast(msg, successClass);
        }

        return {
            error: error,
            success: success
        };
    });