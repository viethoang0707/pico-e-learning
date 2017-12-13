'use strict';
angular.module('conferenceModule').controller('RoomController', function($scope, $rootScope, $location, $routeParams, _, localStorageService, $log, $interval, $sce, $conferenceSocket, $window, $mdDialog, deviceDetector, iceServers, $recorder,  $mdSidenav,$base64,$timeout,ngAudio,$activityIndicator,toastr,$route) {
    // publication
    var PUB_INITIAL = 0;
    var PUB_INPROGRESS = 1;
    var PUB_CONNECTING = 2;
    var PUB_CONNECTED = 3;
    
    // subscription
    var SUB_INITIAL = 0;
    var SUB_INPROGRESS = 1;
    var SUB_CONNECTING = 2;
    var SUB_CONNECTED = 3;
    
    var GATHER_TIMEOUT = 30*1000;
    var CALLING_TIMEOUT = 30*1000;
    
    function RoomMember(id,profile)
    {
        this.id = id;
        this.avail = false;
        this.profile = profile;
        this.invited = false;
        this.pubWebRtcEndpoint = null;
        this.pubOfferSdp = null;
        this.pubState = PUB_INITIAL;
        this.pubGatherToken = null;
        this.pubTimeoutToken = null;
        this.pubCandidateSendQueue =  [];
        this.subWebRtcEndpoint = null;
        this.subOfferSdp = null;
        this.subState = SUB_INITIAL;
        this.subGatherToken = null;
        this.subCandidateSendQueue =  [];
        this.pubCamera = null;        
        this.subVideoSlot = null;
        this.markedForSubscription = false;
    }

    function init() {
        $scope.audio = true;
        $scope.video = true;
        $scope.handUp = true;
        $scope.notiHandUp = true;
        $scope.sound = ngAudio.load("assets/sounds/ring.mp3")
        
        $scope.meeting = localStorageService.get("meeting");
        $scope.myProfile = localStorageService.get("member");
        $scope.memberProfiles = localStorageService.get("memberList");
        $scope.memberList = [];
        $scope.videoSlots = [];
        $scope.chatMessage = [];
        var count = 0;
        _.each($scope.memberProfiles,function(profile) {
            $scope.memberList.push(new RoomMember(profile._id,profile));
            $scope.videoSlots.push({
                allocated: false,
                videoId: 'remoteCamera' + count,
                index: count,
                member:null,
                width: 0
            });
            count++;
        });        
        $scope.me = _.find($scope.memberList, function(m) {
            return m.id == $scope.myProfile._id;
        });
        $scope.me.pubCamera = document.getElementById('localCamera');
        $scope.presenter = _.find($scope.memberList, function(m) {
            return m.profile.role == 'presenter';
        });
        $scope.presenter.subVideoSlot = $scope.videoSlots[0]; 
        $scope.viewerList = _.filter($scope.memberList, function(m) {
            return m.profile.role != 'presenter';
        });      
    };
    
    $scope.connect = function() {
        if ($conferenceSocket.readyState == 1)  {
            sendMessage({ id: 'join'});
            $scope.sound.loop = true;
            $scope.sound.play();
            $scope.me.pubState = PUB_INPROGRESS;
        } else
            $window.location.reload();
    }
    
    $scope.disconnect = function() {
        $scope.me.pubState = PUB_INITIAL;
        $scope.sound.stop();
        try {
            if ($scope.me.pubWebRtcEndpoint)
                $scope.me.pubWebRtcEndpoint['peerConnection'].close();
            _.each($scope.memberList ,function(member) {
                if (member.subState != SUB_INITIAL)
                    unsubscribe(member);
            });
            sendMessage({
                id: 'leave'
            });
        } catch (e) {
            $log.error(e);
        }
    }
    
    $conferenceSocket.onmessage = function(message) {
        var parsedMessage = JSON.parse(message.data);
        console.info('Received message: ' + message.data);
        switch (parsedMessage.id) {
            case 'joinResponse':                
                joinResponse(parsedMessage);
                break;
            case 'end':             
                endConference();
                break;
            case 'memberStatus':
                memberStatus(parsedMessage);
                break;
            case 'handUp':
                handUp(parsedMessage);
                break;
            case 'handDown':
                handDown(parsedMessage);
                break;
            case 'publishResponse':
                publishResponse(parsedMessage);
                break;
            case 'subscribeResponse':
                subscribeResponse(parsedMessage);
                break;
            case 'chat':
                receiveChat(parsedMessage);
                break;
            default:
                console.error('Unrecognized message', parsedMessage);
            }
        $scope.$apply();
    };
        
    function sendMessage(message) {
        message.memberId = $scope.me.id;
        message.meetingId = $scope.meeting._id;
        var jsonMessage = JSON.stringify(message);
        $log.info('Senging message: ' + jsonMessage);
        try {
            $conferenceSocket.send(jsonMessage);
        } catch (exc) {
            $log.info('Error sending message: ' );
            $window.location.reload();
        }
    };
     
    function joinResponse(message) {
        if (message.response == 'accepted') {
            publish();
        } else {
            $location.path('/');
        }
    };

    function publish() {  
        var options = {
            localVideo: localCamera,
            onicecandidate: onPublishIceCandidate,
            iceServers:iceServers,
            oncandidategatheringdone: onPublishComplete
        };
        $scope.me.pubWebRtcEndpoint = kurentoUtils.WebRtcPeer.WebRtcPeerSendonly(options, function(error) {
            if (error) {
                $activityIndicator.stopAnimating();
                $log.error(error);
                return;
            }
            this.generateOffer(onPublish);
        });
        $activityIndicator.startAnimating();
        $scope.me.pubGatherToken = $timeout(function() {
            $log.info('Time out gather publishing data');
            if ($scope.me.pubState != PUB_CONNECTING) {
                $scope.me.pubState = PUB_CONNECTING;
                sendPublishData();
            }
        },GATHER_TIMEOUT);
    }

    function onPublishIceCandidate(candidate) {
        $scope.me.pubCandidateSendQueue.push(candidate);
    }

    function onPublish(error, offerSdp) {
        if (error) {
            $activityIndicator.stopAnimating();
            $log.info(error);
            return;
        }
        $scope.me.pubOfferSdp = offerSdp;
    }

    function publishResponse(message) {
        $activityIndicator.stopAnimating();
        if (message.response != 'accepted') {
            $log.info('Disconnect due to reject from server');
            $scope.disconnect();
        } else {
            $scope.me.pubWebRtcEndpoint.processAnswer(message.sdpAnswer);
            _.each(message.candidateList,function(candidate) {
                $scope.me.pubWebRtcEndpoint.addIceCandidate(candidate);
            })
            sendMessage({
                id: 'publishAvail'
            });
            $scope.me.pubState = PUB_CONNECTED;
            $scope.sound.stop();
        }
    }
    
    function onPublishComplete() {
        $timeout.cancel($scope.me.pubGatherToken);
        if ($scope.me.pubState != PUB_CONNECTING) {
            $scope.me.pubState = PUB_CONNECTING;
            sendPublishData();
        }
    }
    
    function sendPublishData() {
        if (!$scope.me.pubOfferSdp || !$scope.me.pubCandidateSendQueue.length) {
            $log.info('Disconnect due to device error');
            $scope.disconnect();
            return;
        }
        var message = {
                id: 'publish',
                sdpOffer: $scope.me.pubOfferSdp,
                candidateList: $scope.me.pubCandidateSendQueue
         };
        sendMessage(message);
        $scope.me.pubOfferSdp = null;
        $scope.me.pubCandidateSendQueue = [];
        $scope.me.pubTimeoutToken = $timeout(function() {
            if ($scope.me.pubState != PUB_CONNECTED) {
                $log.info('Disconnect due to calling timeout');
                $scope.disconnect();
            }
        },CALLING_TIMEOUT);
    }

    function unsubscribe(member) {
        member.markedForSubscription =  false;
        if (member.subVideoSlot) {
            member.subState = SUB_INITIAL;
            if (member.id!= $scope.presenter.id) {
                member.subVideoSlot.allocated = false;
                member.subVideoSlot.member = null;
                member.subVideoSlot = null;    
            }            
            if (member.subGatherToken)
                $timeout.cancel(member.subGatherToken);
            try {
                if (member.subWebRtcEndpoint)
                    member.subWebRtcEndpoint['peerConnection'].close();
            } catch (e) {
                $log.error(e);
            }
        }
    }
    
    $scope.resubscribe = function(pubId) {
        $scope.disableState = true;
        var publisher = _.find($scope.memberList, function(m) {
            return m.id == pubId;
        });

        if (publisher.invited && publisher.avail) {
            unsubscribe(publisher);
            subscribe(publisher);
        }

        $timeout(function() {
            $scope.disableState = false;
        }, 30000)
    }

    function subscribe(member) {
        if (!$scope.me.avail) {
            return;
        }
        var slot = null;
        if (member.subVideoSlot) {
            slot = member.subVideoSlot;
        } else {
            slot = _.find($scope.videoSlots, function(s) {
                return !s.allocated  && s.index > 0;
            });
        }
        if (!slot) return;
        member.subVideoSlot = slot;
        slot.member = member;
        slot.allocated = true;
        member.subState = SUB_INPROGRESS;
        $scope.numOfAllocated = getAllocatedSlots($scope.videoSlots).length;
        calcGridSlots(3, $scope.numOfAllocated, $scope.videoSlots);
        var remoteCamera = document.getElementById(slot.videoId);
        var options = {
            remoteVideo: remoteCamera,
            onicecandidate: onSubscribeIceCandidate.bind({member:member }),
            iceServers: iceServers,
            oncandidategatheringdone: onSubscribeComplete.bind({member:member})
        }
        member.subGatherToken = $timeout(function(m) {
            $log.info('Time out gather subscribing data');
            if (m.subState != SUB_CONNECTING) {
                sendSubscribeData(m);
                m.subState = SUB_CONNECTING;
            }
        },GATHER_TIMEOUT,true,member);
        
        member.subWebRtcEndpoint = kurentoUtils.WebRtcPeer.WebRtcPeerRecvonly(options, function(error) {
            if (error) {
                $log.error(error);
                return;
            }
            this.generateOffer(onSubscribe.bind({
                member: member
            }));
        });
    }

    function calcGridSlots(slotsPerRow, numOfAllocated, slotsList) {
        var rows = Math.floor(numOfAllocated / slotsPerRow);
        var itemsModulus = numOfAllocated % slotsPerRow;
        if (rows === 0) {
            if (numOfAllocated === 1) {
                $scope.firstSlot = 100;
            } else if (numOfAllocated === 2) {
                $scope.firstSlot = Math.floor(100 / 2);
                slotsList.map(function(slot) {
                    if (slot.allocated) {
                        slot.width = Math.floor(100 / 2);
                    }
                })
            }
        } else {
            slotsList.map(function(slot) {
                if (slot.allocated) {
                    slot.width = Math.floor(100 / slotsPerRow);
                    $scope.firstSlot = slot.width;
                }
            });
        }
    }

    function getAllocatedSlots(memberList) {
        var allocatedSlotArr = _.filter(memberList, function(member) {
            return member.allocated;
        })

        return allocatedSlotArr;
    }

    function onSubscribeIceCandidate(candidate) {
        this.member.subCandidateSendQueue.push(candidate);
    }

    function onSubscribe(error, offerSdp) {
        if (error) {
            $log.error(error);
            return;
        }
        this.member.subSdpOffer = offerSdp;
    }

    function subscribeResponse(message) {
        var member = _.find($scope.memberList, function(m) {
            return m.id == message.pubId;
        });
        if (message.response != 'accepted') {
            member.subState = SUB_INITIAL;
            toastr.error('Server error, please try again later');
        } 
        else {            
            member.subWebRtcEndpoint.processAnswer(message.sdpAnswer);
            if (member.id == $scope.me.id) {
                member.subWebRtcEndpoint.remoteVideo.muted = true;
            }
            if (member.subVideoSlot) {
                member.subState = SUB_CONNECTED;
                _.each(message.candidateList,function(candidate) {
                    member.subWebRtcEndpoint.addIceCandidate(candidate);
                });
                sendMessage({
                    id: 'subscribeAvail',
                    pubId:message.pubId
                });
            }
            nextSubscriber();
        }
    }
    
    function onSubscribeComplete() {
        $timeout.cancel(this.member.subGatherToken);
        if (this.member.subState != SUB_CONNECTING) {
            sendSubscribeData(this.member);
            this.member.subState = SUB_CONNECTING;
        }
        
    }
    
    function sendSubscribeData(member) {
        if (!member.subSdpOffer || !member.subCandidateSendQueue.length) {
            toastr.error("Device error");
            return;
        }
        var message = {
                id: 'subscribe',
                pubId: member.id,
                sdpOffer: member.subSdpOffer,
                candidateList:member.subCandidateSendQueue
            }
        sendMessage(message);
        member.subSdpOffer = null;
        member.subCandidateSendQueue = [];
    }

    function endConference(message) {
        var url = '/';
        
        if ($scope.meeting.logoutUrl) {
            var base64EncodedString = decodeURIComponent($scope.meeting.logoutUrl);
            url = $base64.decode(base64EncodedString);
        }

        $window.location.href = url;
    };

    function memberStatus(message) {
        updateMemberList(message.onlineList);
    };
    
    function nextSubscriber() {
        var member = _.find($scope.memberList, function(m) {
            return m.markedForSubscription;
        });
        if (member) {
            member.markedForSubscription =  false;
            subscribe(member);
        }
    }

    function updateMemberList(onlineList) {
        var unSubList = [];
        _.each($scope.memberList, function(curVal) {
            var newVal = _.find(onlineList, function(m) {
                return curVal.id == m._id;
            });
            if (newVal) {
                curVal.avail = newVal.avail;
                curVal.invited = newVal.invited;
                curVal.online = true;
                if (curVal.invited) {
                    if (curVal.avail) {
                        if (!curVal.subVideoSlot)
                            curVal.markedForSubscription =  true;
                        if (curVal.subVideoSlot && curVal.subState==SUB_INITIAL)
                            curVal.markedForSubscription =  true;
                    } else {
                        if (curVal.subVideoSlot )
                            unSubList.push(curVal);
                    }
                } else {
                    unSubList.push(curVal);
                }
            } else {
                if (curVal.invited) 
                    unSubList.push(curVal);
                curVal.invited = false;
                curVal.avail = false;
                curVal.online = false;
            }
        });        
        _.each(unSubList, function(member) {
            unsubscribe(member);
        });
        nextSubscriber();
    }
    $scope.leave = function() {
        var url = '/';
        
        if ($scope.meeting.logoutUrl) {
            var base64EncodedString = decodeURIComponent($scope.meeting.logoutUrl);
            url = $base64.decode(base64EncodedString);
        }

        sendMessage({
            id: 'leave'
        });

        $window.location.href = url;        
    }
    $scope.endLesson = function() {
        sendMessage({
            id: 'end'
        });
    }
    $scope.toggleAudio = function() {
        var localStream = $scope.me.pubWebRtcEndpoint.getLocalStream();
        if (localStream) {
            var audioTrack = localStream.getAudioTracks()[0];
            $scope.audio = !$scope.audio;
            audioTrack.enabled = $scope.audio;
        }
    };
    $scope.toggleVideo = function() {
        var localStream = $scope.me.pubWebRtcEndpoint.getLocalStream();
        if (localStream) {
            var videoTrack = localStream.getVideoTracks()[0];
            $scope.video = !$scope.video;
            videoTrack.enabled = $scope.video;
        }
    };
    $scope.toggleHand = function() {
        $scope.handUp = !$scope.handUp;
        if ($scope.handUp) {
            sendMessage({id: 'handUp'});
        } else {
            sendMessage({id: 'handDown'});
        }
    };
    $scope.toggleSidenav = function(eleId) {
        $mdSidenav(eleId).toggle();
    };
    $scope.memberInvite = function(memberId) {
        var member = _.find($scope.memberList, function(m) {
            return m.id == memberId;
        });
        member.handUp = false;
        if (!member.invited && member.online) {
            sendMessage({
                id: 'invite',
                'inviteeId': member.id
            });
        }
    }
    $scope.memberDiscard = function(slot) {
        var member = slot.member;
        if (member) {
            member.handUp = false;
            sendMessage({
                id: 'discard',
                'inviteeId': member.id
            });
        }
    }
    var handUp = function(message) {
        var member = _.find($scope.memberList, function(m) {
            return m.id == message.memberId;
        });
        member.handUp = true;
    }
    var handDown = function(message) {
        var member = _.find($scope.memberList, function(m) {
            return m.id == message.memberId;
        });
        member.handUp = false;
    }
    $scope.chat = function() {
        var message = {
            id: 'chat',
            text: $scope.chatInput
        }
        sendMessage(message);
        $scope.chatInput = "";
    }

    $scope.sendChatMessage = function($event) {
        if (event.code === 'Enter') {
            $scope.chat();
        }
    };

    function receiveChat(message) {
        var idx = $scope.chatMessage.length; 

        message.idx = 'message_' + idx;
        $scope.chatMessage.push({
            'user': message.user,
            'text': message.text,
            'idx': message.idx
        });
        
        var newMsg = angular.element(document.querySelector('#message_' + (idx - 1)));
        var chatContent = angular.element(document.querySelector('#chat-content'));
        if (!(_.isEmpty(newMsg))) {
            chatContent.scrollTo(newMsg, 0, 500);
        }   
    }
    $scope.byIndex = function(slot) {
        return slot.index > 0;
    };

    $rootScope.$on('$routeChangeStart', function(event, next, current) {
        if (!current) {
            $conferenceSocket.close();
        }
    });
    $window.onbeforeunload = function() {
        $conferenceSocket.close();
        $interval.cancel(recordToken);
    };

    $scope.showSettingDialog = function(ev) {
        $mdDialog.show({
            templateUrl: 'app/modules/conference/views/settingDialog.html',
            parent: angular.element(document.body),
            targetEvent: ev,
            clickOutsideToClose:true,
            fullscreen: $scope.customFullscreen, 
            controller: DialogController
        })
        .then(function(answer) {
          $scope.status = 'You said the information was "' + answer + '".';
        }, function() {
          $scope.status = 'You cancelled the dialog.';
        });
    }

    function DialogController($scope, $mdDialog) {
        
        $scope.hide = function() {
            $mdDialog.hide();
        };

        $scope.cancel = function() {
            $mdDialog.cancel();
        };

        $scope.answer = function(answer) {
            $mdDialog.hide(answer);
        };
    }

    init();
});