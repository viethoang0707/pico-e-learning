/**
 * @author thanhvk
 * created on 29.09.2016
 */
 'use strict';

angular.module('mainModule')
.controller('AdminRoomsListCtrl', function ($meeting, $member, $rootScope, $scope, $q, $location, $log, $mdSidenav, $base64, sharedProperties, localStorageService) {
    $scope.token = localStorageService.get('tokenConference');
    $scope.room = null;

    function getMembersOfRoom(room) {
        var deffered    = $q.defer(),
            membersList = null,
            info        = {
                token: $scope.token,
                meetingId: room._id
            };

        $member.listMember(info, function(result) {
            if (result.status) {
                membersList = result.data.memberList;
            } else {
                $log.error('Error get list member:', result);
            }

            deffered.resolve(membersList);
        });

        return deffered.promise;
    }

    $scope.toggleSidenav = function(eleId) {
        $mdSidenav(eleId).toggle();
    };

    $scope.showRoomDetail = function(room) {
        $scope.room = room;

        getMembersOfRoom(room)
        .then(function(membersList) {
            $scope.room.membersList = membersList ? membersList : [];
        });
    };

    $scope.gotoEditRoom = function() {
        localStorageService.set('roomAdmin', $scope.room);
        $location.path('/admin/rooms/edit');
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
});