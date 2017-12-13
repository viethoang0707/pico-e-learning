"use strict";

/*
 * Source: https://webrtcexperiment-webrtc.netdna-ssl.com/getScreenId.js
 */
angular.module('api').factory('$screenShare', function(http,$log)
{
    return {
        isEnabled: function(callback) {
            callback(true);
        },
        start: function(callback)
        {
                navigator.getUserMedia = navigator.mozGetUserMedia || navigator.webkitGetUserMedia;
                if (!!navigator.mozGetUserMedia) {
                    option =  {
                        video: {
                            mozMediaSource: 'window',
                            mediaSource: 'window'
                        }
                    }
                    navigator.getUserMedia(option, function (stream) {
                        callback(true,stream,'firefox')
                    }, function (error) {
                        $log.error('Share screen in firfox failed',error);
                        callback(false);
                    });
                    return;
                } 
                postMessage();
                window.addEventListener('message', onIFrameCallback);

                function onIFrameCallback(event) {
                    if (!event.data) return;

                    if (event.data.chromeMediaSourceId) {
                        if (event.data.chromeMediaSourceId === 'PermissionDeniedError') {
                            $log.error('permission-denied');
                            callback(false);
                        } else { 
                            var sourceId =  event.data.chromeMediaSourceId
                            var option = getScreenConstraints(null, sourceId);
                            navigator.getUserMedia(option, function (stream) {
                                callback(true,stream,sourceId)
                            }, function (error) {
                                $log.error('Share screen in Chome failed',error);
                                callback(false);
                            });
                        }
                    }

                    if (event.data.chromeExtensionStatus) {
                        var extStatus = event.data.chromeExtensionStatus
                        var option = getScreenConstraints(extStatus);
                        navigator.getUserMedia(option, function (stream) {
                            callback(true,stream,null)
                        }, function (error) {
                            $log.error('Share screen in Chome failed',error);
                            callback(false);
                        });
                    }

                    // this event listener is no more needed
                    window.removeEventListener('message', onIFrameCallback);
                }
               

                function getScreenConstraints(error, sourceId) {
                    var screen_constraints = {
                        audio: false,
                        video: {
                            mandatory: {
                                chromeMediaSource: error ? 'screen' : 'desktop',
                                maxWidth: window.screen.width > 1920 ? window.screen.width : 1920,
                                maxHeight: window.screen.height > 1080 ? window.screen.height : 1080
                            },
                            optional: []
                        }
                    };

                    if (sourceId) {
                        screen_constraints.video.mandatory.chromeMediaSourceId = sourceId;
                    }

                    return screen_constraints;
                }

                function postMessage() {
                    if (!iframe) {
                        loadIFrame(postMessage);
                        return;
                    }

                    if (!iframe.isLoaded) {
                        setTimeout(postMessage, 100);
                        return;
                    }

                    iframe.contentWindow.postMessage({
                        captureSourceId: true
                    }, '*');
                }

                function loadIFrame(loadCallback) {
                    if (iframe) {
                        loadCallback();
                        return;
                    }

                    iframe = document.createElement('iframe');
                    iframe.onload = function() {
                        iframe.isLoaded = true;

                        loadCallback();
                    };
                    iframe.src = 'https://www.webrtc-experiment.com/getSourceId/'; // https://wwww.yourdomain.com/getScreenId.html
                    iframe.style.display = 'none';
                    (document.body || document.documentElement).appendChild(iframe);
                }

               var iframe;

                // this function is used in v3.0
                window.getScreenConstraints = function(callback) {
                    loadIFrame(function() {
                        getScreenId(function(error, sourceId, screen_constraints) {
                            callback(error, (screen_constraints || {}).video);
                        });
                    });
                };
            
        }          
    }
});