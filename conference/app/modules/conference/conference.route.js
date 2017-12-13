'use strict';
angular.module('conferenceModule').config(function ($routeProvider, $sceDelegateProvider, localStorageServiceProvider)
{
    $routeProvider.when('/room',
            {
                templateUrl: 'app/modules/conference/views/room.html',
                controller: 'RoomController',
                controllerAs: 'app/modules/conference/controllers/room'
            });

});