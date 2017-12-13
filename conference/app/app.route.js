'use strict';
angular.module('app').config(function ($routeProvider)
{
    $routeProvider.when('/', {
        templateUrl: 'app/modules/main/views/main.html',
        controller: 'HomeController',
        controllerAs: 'app/modules/main/controllers/main'
    });
});
