'use strict';
angular.module('app')
.value('duScrollOffset', 0)
.value('duScrollDuration', 1000)
.config(function ($logProvider, $mdThemingProvider, $mdIconProvider, $translateProvider, 
        toastrConfig, enabledLog,$activityIndicatorProvider) {
    // Enable log
    $logProvider.debugEnabled(enabledLog);
    // Set options third-party lib
      toastrConfig.allowHtml = true;
      toastrConfig.timeOut = 3000;
      toastrConfig.positionClass = 'toast-top-right';
      toastrConfig.preventDuplicates = true;
      toastrConfig.closeButton = true;
      toastrConfig.progressBar = true;
    // Enable multi-language
    $translateProvider.useStaticFilesLoader({
        prefix:'./assets/locale/locale-',
        suffix: '.json'
    });
    $translateProvider.preferredLanguage('vi');
    $translateProvider.fallbackLanguage('vi');
    $activityIndicatorProvider.setActivityIndicatorStyle('SpinnerDark');

});