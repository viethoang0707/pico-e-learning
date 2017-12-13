"use strict";
angular.module('api').factory('$meeting', function(http)
{
    return {
        createMeeting: function(info, callback)
        {
            var path = "/meeting";
            var param = {
                token: info.token,
                meeting: angular.toJson(info.meeting)
            };
            http.postRequest(path, param, callback);
        },
        updateMeeting: function(info, callback)
        {
            var path = "/meeting";
            var param = {
                token: info.token,
                meeting: angular.toJson(info.meeting)
            };
            http.putRequest(path, param, callback);
        },
        endMeeting: function(info, callback)
        {
            var path = "/meeting/end";
            var param = {
                token: info.token,
                id: info.meetingId
            };
            http.postRequest(path, param, callback);
        },
        listMeeting: function(info, callback)
        {
            var path = "/meeting/";
            var param = {
                token: info.token
            };
            http.getRequest(path, param, callback);
        },
        getMeeting: function(info, callback)
        {
            var path = "/meeting/";
            var param = {
                token: info.token,
                id: info.meetingId
            };
            http.getRequest(path, param, callback);
        },
        setMeetingConfig: function(info, callback)
        {
            var path = "/meeting/config";
            var param = {
                token: info.token,
                config: info.config
            };
            http.putRequest(path, param, callback);
        },
        getMeetingConfig: function(info, callback)
        {
            var path = "/meeting/config";
            var param = {
                token: info.token
            };
            http.getRequest(path, param, callback);
        },
        uploadPresentation: function(info, callback)
        {
            var path = "/meeting/uploadPresentation";
            var file = info.file;
            http.uploadRequest(path, file, callback);
        },
        shareFile: function(info, callback)
        {
            var path = "/meeting/shareFile";
            var file = info.file;
            http.uploadRequest(path, file, callback);
        }
    };
});