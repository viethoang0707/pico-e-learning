/**
 * @author thanhvk
 * created on 30.09.2016
 */
 'use strict';

angular.module('mainModule')
.controller('AdminCreateRoomCtrl', function ($meeting, $rootScope, $scope, $q, $location, $log, $mdSidenav, $base64, viMdToast, Fullscreen, localStorageService) {
    $scope.token = localStorageService.get('tokenConference');
    $scope.room = {};

    if (!$scope.token) {
        $location.path('/admin');
    }

    function createRoom() {
        var info     = {
            token: $scope.token,
            meeting: $scope.room            
        };  

        if ($scope.room.logoutUrl) {
            var base64EncodedString = $base64.encode($scope.room.logoutUrl);
            info.meeting.logoutUrl = encodeURIComponent(base64EncodedString);
        }              

        $meeting.createMeeting(info, function(result) {
            if (result.status && result.data.status) {
                viMdToast.success('Create room success!');
                $scope.room._id = result.data.id;
                localStorageService.set('roomAdmin', $scope.room);
                $location.path('/admin/rooms/edit');
            } else {
                viMdToast.error('Create room error!');
            }
        });  
    }

    $scope.save = function() {
        createRoom();
    };

    $scope.cancel = function() {
        $location.path('/admin/rooms');
    };

    function getRoomsList() {
        var deffered    = $q.defer(),
            roomsList   = null,
            info        = {
                token: $scope.token
            };

        $meeting.listMeeting(info, function(result) {
            if (result.status) {
                roomsList = result.data.meetingList;
            } else {
                $log.error('Error get rooms list:', result);
            }   

            deffered.resolve(roomsList);                   
        });
        
        return deffered.promise;
    }

    getRoomsList()
    .then(function(roomsList) {
        $scope.roomsList = roomsList ? roomsList.reverse() : [];
    });

    $scope.toggleSidenav = function(eleId) {
        $mdSidenav(eleId).toggle();
    };
});