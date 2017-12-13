'use strict';
angular.module('api').factory('$trainingSocket', function($log, trainingSocketUrl)
{
    try {
        var ws = new WebSocket(trainingSocketUrl);
        return ws;
    } catch (exc) {
        $log.error('Cannot connect to server');
        return null;
    }
})
.factory('$conferenceSocket', function($log, conferenceSocketUrl)
{
    try {
        var ws = new WebSocket(conferenceSocketUrl);
        return ws;
    } catch (exc) {
        $log.error('Cannot connect to server');
        return null;
    }
});