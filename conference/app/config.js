'use strict';
angular.module('config', [])
  .constant('apiUrl', "https://training.demo.vietinterview.com:9444/api")
  .constant('trainingSocketUrl', "wss://training.demo.vietinterview.com:9445/one2many")
  .constant('conferenceSocketUrl', "wss://training.demo.vietinterview.com:9446/many2many")
  .constant('iceServers', [{"url":"turn:125.212.233.5:3478?transport=udp","credential":"123456","username":"quang"}])
  .constant('enabledLog', true);
