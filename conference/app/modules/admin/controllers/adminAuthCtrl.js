/**
 * @author thanhvk
 * created on 28.09.2016
 */
 'use strict';

angular.module('mainModule')
.controller('AdminAuthCtrl', function ($auth, $rootScope, $scope, $q, $location, $mdToast, viMdToast, Fullscreen, localStorageService) {
  $scope.auth = {
    emailLogin: '',
    passwordLogin: ''
  };

  $scope.loginAccount = function() {
    var info = {};
    info.email = $scope.auth.emailLogin;
    info.password = $scope.auth.passwordLogin;

    $auth.loginAccount(info, function(result) {
      if (result.status) {
        localStorageService.set('tokenConference', result.data.token);
        $location.path('/admin/rooms');
      } else {
        viMdToast.error('Email or password incorrect!');
      }
    });
  };

  $scope.loginAccountKeyPress = function(event) {
    if (event.code === 'Enter') {
      $scope.loginAccount();
    }
  };
});
