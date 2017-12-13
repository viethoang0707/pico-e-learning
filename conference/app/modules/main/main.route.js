'use strict';
angular.module('mainModule').config(function ($routeProvider) {
    $routeProvider.when('/home', {
        templateUrl: 'app/modules/main/views/main.html',
        controller: 'HomeController',
        controllerAs: 'app/modules/main/controllers/main'
    })
    .when('/trustedlogin', {
        templateUrl: 'app/modules/main/views/trustedlogin.html',
        controller: 'TrustedLoginController',
        controllerAs: 'app/modules/main/controllers/trustedlogin'
    });  
});