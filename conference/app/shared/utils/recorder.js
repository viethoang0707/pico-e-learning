'use strict';
angular.module('api').factory('$recorder', function( $log)
{
    var mediaRecorder;
    var recordedBlobs;
    var sourceBuffer;
    var onFileReady;
    
    function handleDataAvailable(event) {
        if (event.data && event.data.size > 0) {
            recordedBlobs.push(event.data);
        }
        if (deviceDetector.browser == 'firefox' && !$scope.recording) {
            var superBuffer = new Blob(recordedBlobs, {
                type: 'video/webm'
            });
            var file = new File([superBuffer], "upload.webm");
            $log.info("Broadcast firefox");
            onFileReady(file);
        }
    };

    function handleStop(event) {
        $log.info('Recorder stopped: ', event);
    };
    
    return {
        start: function(stream)
        {
            recordedBlobs = [];
            var options = {
                mimeType: 'video/webm;codecs=vp8'
            };
            if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                $log.info(options.mimeType + ' is not Supported');
                options = {
                    mimeType: 'video/webm;codecs=vp8'
                };
                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    $log.info(options.mimeType + ' is not Supported');
                    options = {
                        mimeType: 'video/webm'
                    };
                    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                        $log.info(options.mimeType + ' is not Supported');
                        options = {
                            mimeType: ''
                        };
                    }
                }
            }
            try {
                mediaRecorder = new MediaRecorder(stream, options);
            } catch (e) {
                console.error('Exception while creating MediaRecorder: ' + e);
                alert('Exception while creating MediaRecorder: ' + e + '. mimeType: ' + options.mimeType);
                return;
            }
            $log.info('Created MediaRecorder', mediaRecorder, 'with options', options);
            mediaRecorder.onstop = handleStop;
            mediaRecorder.ondataavailable = handleDataAvailable;
            mediaRecorder.start();
        },
        stop: function(callback)
        {
            onFileReady =  callback;
            mediaRecorder.stop();
            $log.info("Stop recording", recordedBlobs);
            // For Firefox, the browser buffere media data and release upon stop recording
            // For Chrome, the browser release data whenver it is available
            if (deviceDetector.browser == 'chrome') {
                var superBuffer = new Blob(recordedBlobs, {
                    type: 'video/webm'
                });
                var file = new File([superBuffer], "upload.webm");
                $log.info("Broadcast chrome");
                onFileReady(file);
            }
            
        }
    };
});