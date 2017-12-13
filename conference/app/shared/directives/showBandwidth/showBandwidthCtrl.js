/**
 * @author thanhvk
 * created on 08.10.2016
 */
 'use strict';

angular.module('app')
    .directive('showBandwidth', ['$http', '$q', '$location', '$timeout', function($http, $q, $location, $timeout) {
        return {
            restrict: 'E',
            templateUrl: 'app/shared/directives/showBandwidth/showBandwidthView.html',
            scope: {
                max: '=max',
                scale: '=scale'
            },
            link: function($scope) {
                var imageUrl = "https://localhost:3000/assets/images/hybrid.jpg"; 
                // var imageUrl = $location.protocol() + '://' + $location.host() + ':' + $location.port() + '/assets/images/hybrid.jpg';
                // var imageUrl = "https://training.demo.vietinterview.com:9444/public/nghiepvu.pdf";
                var downloadSize = 23670; //bytes
                // var speedDownMbpsAvg = 0;
                var speedDownMbpsArr = [];
                var measureDownloadSpeedArr = [];

                function measureDownloadSpeed() {
                    var deferred = $q.defer(),
                        startTime, 
                        endTime;
                    startTime = (new Date()).getTime();

                    $http({
                        method: 'GET',
                        url: imageUrl
                    }).then(function successCallback(response) {
                        endTime = (new Date()).getTime();
                        var duration = (endTime - startTime) / 1000;
                        var bitsLoaded = downloadSize * 8;

                        var speedDownBps = (bitsLoaded / duration).toFixed(2);
                        var speedDownKbps = (speedDownBps / 1024).toFixed(2);
                        var speedDownMbps = (speedDownKbps / 1024).toFixed(2);

                        $timeout(function() {
                            if ($scope.speedDownMbps === 0) {
                                $scope.speedDownMbpsAvg = Number(speedDownMbps);
                                $scope.speedDownMbps = Number(speedDownMbps);
                            } else {
                                speedDownMbps = ($scope.speedDownMbpsAvg + Number(speedDownMbps)) / 2;
                                $scope.speedDownMbpsAvg = Number(speedDownMbps.toFixed(2));
                                $scope.speedDownMbps = Number(speedDownMbps.toFixed(2));
                            }

                            deferred.resolve();
                        }, 60);                        
                    }, function errorCallback(response) {
                        deferred.resolve(null);
                    });

                    return deferred.promise;
                };

                $scope.measureDownloadSpeedAverage = function() {
                    // speedDownMbpsAvg = 0;
                    $scope.speedDownMbpsAvg = 0;
                    $scope.speedDownMbps = 0;

                    for (var i = 0; i < 100; i++) {
                        measureDownloadSpeedArr.push(measureDownloadSpeed);
                    }

                    measureDownloadSpeedArr.reduce(function(previousValue, currentValue) {
                        return previousValue.then(function() {
                            return currentValue();
                        });
                    }, $q.resolve());
                    // .then(function() {
                    //     $scope.speedDownMbps = speedDownMbpsAvg;
                    //     $scope.speedDownMbpsAvg = speedDownMbpsAvg;
                    // });
                };

                $scope.measureDownloadSpeedAverage();
            }
        };
    }]); 