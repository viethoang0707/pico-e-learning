'use strict';
angular.module('trainingModule').config(function ($routeProvider, $sceDelegateProvider, localStorageServiceProvider)
{
    $routeProvider.when('/training',
            {
                templateUrl: 'app/modules/training/views/trainingRoom.html',
                controller: 'TrainingRoomController',
                controllerAs: 'app/modules/training/controllers/trainingRoom'
            });

});