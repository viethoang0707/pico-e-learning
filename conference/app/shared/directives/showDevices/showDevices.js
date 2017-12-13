angular.module('app')
    .directive('showDevices', function() {
        return{
            restrict: 'E', 
            templateUrl: 'app/shared/directives/showDevices/showDevices.html',
            link: function($scope) {
                if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                    navigator.enumerateDevices = function (callback) {
                        navigator.mediaDevices.enumerateDevices().then(callback);
                    };
                }
                function getAllAudioVideoDevices(successCallback, failureCallback) {
                    if (!navigator.enumerateDevices && window.MediaStreamTrack && window.MediaStreamTrack.getSources) {
                        navigator.enumerateDevices = window.MediaStreamTrack.getSources.bind(window.MediaStreamTrack);
                    }
                    if (!navigator.enumerateDevices && navigator.mediaDevices.enumerateDevices) {
                        navigator.enumerateDevices = navigator.mediaDevices.enumerateDevices.bind(navigator);
                    }
                    if (!navigator.enumerateDevices) {
                        failureCallback(null, 'Neither navigator.mediaDevices.enumerateDevices NOR MediaStreamTrack.getSources are available.');
                        return;
                    }
                    var allMdiaDevices = [];
                    var allAudioDevices = [];
                    var allVideoDevices = [];
                    var audioInputDevices = [];
                    var audioOutputDevices = [];
                    var videoInputDevices = [];
                    var videoOutputDevices = [];
                    navigator.enumerateDevices(function (devices) {
                        devices.forEach(function (_device) {
                            var device = {};
                            for (var d in _device) {
                                device[d] = _device[d];
                            }
                            // make sure that we are not fetching duplicate devics
                            var skip;
                            allMdiaDevices.forEach(function (d) {
                                if (d.id === device.id) {
                                    skip = true;
                                }
                            });
                            if (skip) {
                                return;
                            }
                            // if it is MediaStreamTrack.getSources
                            if (device.kind === 'audio') {
                                device.kind = 'audioinput';
                            }
                            if (device.kind === 'video') {
                                device.kind = 'videoinput';
                            }
                            if (!device.deviceId) {
                                device.deviceId = device.id;
                            }
                            if (!device.id) {
                                device.id = device.deviceId;
                            }
                            if (!device.label) {
                                device.label = 'Please invoke getUserMedia once.';
                            }
                            if (device.kind === 'audioinput' || device.kind === 'audio') {
                                audioInputDevices.push(device);
                            }
                            // if (device.kind === 'audiooutput') {
                            //     audioOutputDevices.push(device);
                            // }
                            if (device.kind === 'videoinput' || device.kind === 'video') {
                                videoInputDevices.push(device);
                            }
                            if (device.kind.indexOf('audio') !== -1) {
                                allAudioDevices.push(device);
                            }
                            if (device.kind.indexOf('video') !== -1) {
                                allVideoDevices.push(device);
                            }
                            // there is no 'videoouput' in the spec.
                            // so videoOutputDevices will always be [empty]
                            allMdiaDevices.push(device);
                        });
                        if (successCallback) {
                            successCallback({
                                allMdiaDevices: allMdiaDevices,
                                allVideoDevices: allVideoDevices,
                                allAudioDevices: allAudioDevices,
                                videoInputDevices: videoInputDevices,
                                audioInputDevices: audioInputDevices
                                // audioOutputDevices: audioOutputDevices
                            });
                        }
                    });
                }
                function captureUserMedia(mediaConstraints, successCallback, errorCallback) {
                    navigator.mediaDevices.getUserMedia(mediaConstraints).then(successCallback).catch(errorCallback);
                }
                captureUserMedia({audio: true, video: true}, function (stream) {
                    getAllAudioVideoDevices(function (result) {
                        if (result.allMdiaDevices.length) {
                            console.log('Number of audio/video devices available:', result.allMdiaDevices.length);
                            $scope.allDevices = result.allMdiaDevices.length;
                        }
                        if (result.allVideoDevices.length) {
                            console.log('Number of video devices available:', result.allVideoDevices.length);
                            $scope.videoDevices = result.allVideoDevices.length;
                        }
                        if (result.allAudioDevices.length) {
                            console.log('Number of audio devices available:', result.allAudioDevices.length);
                            $scope.audioDevices = result.allAudioDevices.length;
                        }
                        if (result.videoInputDevices.length) {
                            console.log('Number of video-input devices available:', result.videoInputDevices.length);
                            $scope.videoInputDevices = result.videoInputDevices.length;
                        }
                        if (result.audioInputDevices.length) {
                            console.log('Number of audio-input devices available:', result.audioInputDevices.length);
                            $scope.audioInputDevices = result.audioInputDevices.length;
                        }
                        // if (result.audioOutputDevices.length) {
                        //     console.log('Number of audio-output devices available:', result.audioOutputDevices.length);
                        //     $scope.audioOutputDevices = result.audioOutputDevices.length;
                        // }

                        if (result.allMdiaDevices.length && result.allMdiaDevices[0].label === 'Please invoke getUserMedia once.') {
                            console.log('It seems you did not invoke navigator-getUserMedia before using these API.', 'warning');
                        }

                        $scope.audioInputDevicesName = result.audioInputDevices;
                        // $scope.audioOutputDevices = result.audioOutputDevices;
                        $scope.deviceVideoInputName = result.videoInputDevices;
                        $scope.$apply();
                    }, function (error) {
                        alert(error);
                    });
                }, function (error) {
                    console.log(error);
                });
            }
        }
    });