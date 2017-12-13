'use strict';

/**
 * @ngdoc function
 * @name conferenceApp.controller:MainCtrl
 * @description
 * # MainCtrl
 * Controller of the conferenceApp
 */
angular.module('mainModule')
    .controller('TrustedLoginController', function ($rootScope, $scope, $member, $location, $log, $routeParams,viMdToast, 
            Fullscreen, localStorageService,$base64) {
        $scope.trustedLoginKind = 'training';
        
        // Login to room
        $scope.loginMeeting = function () {
            Fullscreen.all();
            var info = {
                payload: $base64.decode($routeParams.payload),
                checksum: $routeParams.checksum
            };

            $member.trustedLogin(info, function(result) {
                if (result.status && result.data.status) {
                    localStorageService.set("meeting",result.data.meeting);
                    localStorageService.set("member",result.data.member);
                    localStorageService.set("memberList",result.data.memberList);
                    
                    if ($routeParams.kind === 'conference') {
                        $location.path('/room');
                    } else {                        
                        $location.path('/training');
                    } 
                } else {
                    $log.error(result);
                    viMdToast.error('Đã có lỗi xảy ra!');
                }
            });  
        };

        $scope.loginMeetingKeyPress = function(event) {
            if (event.code === 'Enter') {
                $scope.loginMeeting();
            }
        };
    });
