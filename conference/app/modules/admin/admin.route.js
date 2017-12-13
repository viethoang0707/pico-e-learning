'use strict';
angular.module('adminModule').config(function ($routeProvider) {
    $routeProvider
        .when('/admin', {
            templateUrl: 'app/modules/admin/views/adminAuthView.html',
            controller: 'AdminAuthCtrl',
            controllerAs: 'app/modules/admin/controllers/adminAuthController'
        })
        .when('/admin/rooms', {
            templateUrl: 'app/modules/admin/views/adminRoomsListView.html',
            controller: 'AdminRoomsListCtrl',
            controllerAs: 'app/modules/admin/controllers/adminRoomsListController'
        })
        .when('/admin/rooms/create', {
            templateUrl: 'app/modules/admin/views/adminCreateRoomView.html',
            controller: 'AdminCreateRoomCtrl',
            controllerAs: 'app/modules/admin/controllers/adminCreateRoomController'
        })
        .when('/admin/rooms/edit', {
            templateUrl: 'app/modules/admin/views/adminEditRoomView.html',
            controller: 'AdminEditRoomCtrl',
            controllerAs: 'app/modules/admin/controllers/adminEditRoomCtrl'
        })
        .otherwise({
            redirectTo: '/'
        });        
});