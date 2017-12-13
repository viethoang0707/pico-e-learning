'use strict';

/**
 * @ngdoc function
 * @name conferenceApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the conferenceApp
 */
angular.module('mainModule')
    .controller('HomeController', function ($rootScope, $scope, $member, $location, $log, viMdToast, Fullscreen, localStorageService) {
        $scope.meetingAuth = {kind: 'conference'};

        // Login to room
	    $scope.loginMeeting = function () {
		    Fullscreen.all();
            var info = {
                meetingId: $scope.meetingAuth.meetingId,
                email: $scope.meetingAuth.email,
                password: $scope.meetingAuth.password
            };

            $member.login(info, function(result) {
                if (result.status && result.data.status) {
                    localStorageService.set("meeting",result.data.meeting);
                    localStorageService.set("member",result.data.member);
                    localStorageService.set("memberList",result.data.memberList);
                    
                    if ($scope.meetingAuth.kind === 'training') {
                        $location.path('/training');
                    } else {
                        $location.path('/room');
                    }                    
                } else {
                    $log.error(result);
                    viMdToast.error('Input incorrect!');
                }
            });  
	    };

        $scope.loginMeetingKeyPress = function(event) {
            if (event.code === 'Enter') {
                $scope.loginMeeting();
            }
        };
    });
