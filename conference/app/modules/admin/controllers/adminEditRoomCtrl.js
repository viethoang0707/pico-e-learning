/**
 * @author thanhvk
 * created on 30.09.2016
 */
 'use strict';

angular.module('mainModule')
.controller('AdminEditRoomCtrl', function ($meeting, $member, $rootScope, $scope, $q, $location, $log, $mdToast, $mdSidenav, $base64, viMdToast, sharedProperties, localStorageService, _) {
    $scope.token = localStorageService.get('tokenConference');
    $scope.room = localStorageService.get('roomAdmin');
    if ($scope.room.logoutUrl) {
        var base64EncodedString = decodeURIComponent($scope.room.logoutUrl);
        $scope.room.logoutUrl = $base64.decode(base64EncodedString);
    }    
    $scope.member = null;
    $scope.showMemberForm = false;

    // Functions init
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
                $log.error('Error get rooms list');
            }   

            deffered.resolve(roomsList);                   
        });
        
        return deffered.promise;
    }

    function getMembersOfRoom() {
        var deffered    = $q.defer(),
            membersList = null,
            info        = {
                token: $scope.token,
                meetingId: $scope.room._id
            };

        $member.listMember(info, function(result) {
            if (result.status) {
                membersList = result.data.memberList;
            } else {
                $log.error('Error get list member');
            }

            deffered.resolve(membersList);
        });

        return deffered.promise;
    }

    getRoomsList()
    .then(function(roomsList) {
        $scope.roomsList = roomsList ? roomsList.reverse() : [];
    })
    .then(function() {
        return getMembersOfRoom();
    })
    .then(function(membersList) {
        $scope.membersList = membersList ? membersList.reverse() : [];
    });

    // Functions of room
    function updateRoomsList() {
        $scope.roomsList = $scope.roomsList.map(function(room) {
            return (room = (room._id === $scope.room._id) ? $scope.room : room);
        });
    }

    function updateRoom() {
        var info = {
            token: $scope.token,
            meeting: {
                id: $scope.room._id,
                name: $scope.room.name,
                meetingRef: $scope.room.meetingRef,
                welcome: $scope.room.welcome,
                duration: $scope.room.duration,
                logoutUrl: $scope.room.logoutUrl,
            }
        };

        if (info.meeting.logoutUrl) {
            var base64EncodedString = $base64.encode(info.meeting.logoutUrl);
            info.meeting.logoutUrl = encodeURIComponent(base64EncodedString);
        }  

        $meeting.updateMeeting(info, function(result) {
            if (result.status) {
                viMdToast.success('Update room success!');
                updateRoomsList();
                localStorageService.set('roomAdmin', $scope.roomsList[0]);                
            } else {
                viMdToast.error('Update room error!');
            }
        });
    } 

    $scope.cancel = function() {
        $location.path('/admin/rooms');
    };

    $scope.save = function() {
        updateRoom();
    };   

    $scope.showRoomDetail = function(room) {
        $scope.showMemberForm = false;
        $scope.room = room;

        getMembersOfRoom()
        .then(function(membersList) {
            $scope.membersList = membersList ? membersList.reverse() : [];
        });
    };

    // Functions of member
    function updateMembersList(action, memberId) {
        var memberIdx;
        if (action === 'create' && memberId) {
            $scope.member._id = memberId;
            $scope.membersList.unshift($scope.member);
        } else if (action === 'remove' && memberId) {
            memberIdx = _.findIndex($scope.membersList, {_id: memberId});
            if (memberIdx !== -1) {
                $scope.membersList.splice(memberIdx, 1);
            }
        } else {
            memberIdx = _.findIndex($scope.membersList, {_id: memberId});
            if (memberIdx !== -1) {
                $scope.membersList[memberId] = $scope.member;
            }
        }
    }

    function addMember() {
        var deffered = $q.defer(),
            info = {
                token: $scope.token,
                meetingId: $scope.room._id,
                member: $scope.member
            };

        $member.addMember(info, function(result) {
            var member = null;

            if (result.status && result.data.status) {
                viMdToast.success('Add member success!');
                member = result.data;
            } else {
                viMdToast.error('Add member error!');
            }

            deffered.resolve(member);
        });

        return deffered.promise;
    }

    function updateMember() {
        $scope.member.role = $scope.member.role.toLowerCase();
        var deffered    = $q.defer(),
            member      = null,
            info        = {
                token: $scope.token,
                meetingId: $scope.room._id,
                member: $scope.member
            };

        $member.updateMember(info, function(result) {
            if (result.status && result.data.status) {
                viMdToast.success('Update member success!');
                member = result.data;
            } else {
                viMdToast.error('Update member error!');
            }

            deffered.resolve(member);
        });

        return deffered.promise;
    }

    $scope.saveMember = function() {
        if ($scope.member && $scope.member._id) {
            updateMember()
            .then(function(result) {
                if (result) {
                    updateMembersList('update', null);
                }
            });
        } else {
            addMember()
            .then(function(result) {
                if (result) {
                    updateMembersList('create', result.id);
                }
            });
        }
    };

    function removeMember() {
        var deffered    = $q.defer(),
            info        = {
                token: $scope.token,
                id: $scope.member._id
            };

        $member.removeMember(info, function(result) {
            if (result.status) {
                viMdToast.success('Delete member success!');
            } else {
                viMdToast.error('Delete member error!');
            }
            deffered.resolve($scope.member._id);
        });

        return deffered.promise;
    }

    $scope.removeMember = function() {
        removeMember()
        .then(function(memberId) {
            $scope.member = null;
            updateMembersList('remove', memberId);
        });
    };

    $scope.showEditMemberForm = function() {
        $scope.showMemberForm = true;
    };

    $scope.showAddMemberForm = function() {
        $scope.showMemberForm = true;
        $scope.member = {};
    };

    $scope.cancelMember = function() {
        $scope.showMemberForm = false;
    };

    $scope.showMemberDetail = function(member) {
        $scope.showMemberForm = false;
        $scope.member = member;
    };

    $scope.toggleSidenav = function(eleId) {
        $mdSidenav(eleId).toggle();
    };
});