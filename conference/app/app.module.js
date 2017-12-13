'use strict';
angular.module('app', [
    // third-party module
    'ngAnimate', 'ngCookies', 'ngSanitize', 'ngMessages', 'ngAria', 'ngRoute', 'ngLocale',
    'toastr', 'pascalprecht.translate', 'ui.bootstrap', 'ui.router', 'underscore','FBAngular',
    'ui.bootstrap.datetimepicker','pageslide-directive',   'angular-simple-chat','ngAudio',
    'LocalStorageModule', 'ngFileUpload', 'ui.select','ngFileSaver','base64','textAngular','ng.deviceDetector', 
    'timer', 'ngMaterial', 'ngMessages', 'pascalprecht.translate','ngActivityIndicator', 'pdf', 'duScroll',
    
    // application module
    'config', 'api',  'mainModule', 'adminModule', 'conferenceModule', 'trainingModule'
]);

