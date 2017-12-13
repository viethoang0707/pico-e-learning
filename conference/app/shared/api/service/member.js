"use strict";
angular.module('api').factory('$member', function(http)
{
    return {
       
        addMember: function(info, callback)
        {
            var path = "/member";
            var param = {
                token: info.token,
                meetingId: info.meetingId,
                member: angular.toJson(info.member)
            };
            http.postRequest(path, param, callback);
        },
        updateMember: function(info, callback)
        {
            var path = "/member";
            var param = {
                token: info.token,
                meetingId: info.meetingId,
                member: angular.toJson(info.member)
            };
            http.putRequest(path, param, callback);
        },
        removeMember: function(info, callback)
        {
            var path = "/member";
            var param = {
                token: info.token,
                id: info.id
            };
            http.deleteRequest(path, param, callback);
        },
        listMember: function(info, callback)
        {
            var path = "/member";
            var param = {
                token: info.token,
                meetingId: info.meetingId
            };
            http.getRequest(path, param, callback);
        },
        login: function(info, callback)
        {
            var path = "/member/login";
            var param = {
                meetingId: info.meetingId,
                email: info.email,
                password: info.password
            };
            http.postRequest(path, param, callback);
        },
        trustedLogin: function(info, callback)
        {
            var path = "/trusted/login";
            var param = {
                payload: info.payload,
                checksum: info.checksum
            };
            http.postRequest(path, param, callback);
        }
    };
});