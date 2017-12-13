'use strict';
angular.module('trainingModule').controller('TrainingRoomController', function($scope, $rootScope, $location, $routeParams, _, localStorageService, $log, $interval, $sce, $trainingSocket, $window, $document, deviceDetector, iceServers, $recorder,
    $mdSidenav, $base64, $timeout, ngAudio, toastr, $route, pdfDelegate,$meeting,$screenShare) {
    // publication
    var PUB_INITIAL = 0;
    var PUB_INPROGRESS = 1;
    var PUB_CONNECTING = 2;
    var PUB_CONNECTED = 3;
    
    // subscription
    var SUB_INITIAL = 10;
    var SUB_INPROGRESS = 11;
    var SUB_CONNECTING = 12;
    var SUB_CONNECTED = 13;
    
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
    }
    
    function ScreenShare()
    {
        this.webRtcEndpoint = null;
        this.offerSdp = null;
        this.state = PUB_INITIAL;
        this.gatherToken = null;
        this.timeoutToken = null;
        this.candidateSendQueue =  [];
        this.camera = null;
        this.avail = false;
    }

    function init() {
        $scope.audio = true;
        $scope.video = true;
        $scope.handUp = true;
        $scope.notiHandUp = true;
        $scope.toggleVideoFlag = false;
        $scope.sound = ngAudio.load("assets/sounds/ring.mp3");
        $scope.fullScreen = false;
        $scope.pdfUrl = 'assets/images/Intro.pdf';
        $scope.zoom = 90;
        $scope.countAllocated = 0;
        
        $scope.meeting = localStorageService.get("meeting");
        $scope.myProfile = localStorageService.get("member");
        $scope.memberProfiles = localStorageService.get("memberList");
        $scope.memberList = [];
        $scope.videoSlots = [];
        $scope.chatMessage = [];
        $scope.fileList = [];
        var count = 0;
        _.each($scope.memberProfiles,function(profile) {
            $scope.memberList.push(new RoomMember(profile._id,profile));
            $scope.videoSlots.push({
                allocated: false,
                videoId: 'remoteCamera' + count,
                index: count,
                member:null
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
        $scope.screenShare = new ScreenShare();
        
    };
    
    $scope.connect = function() {
        if ($trainingSocket.readyState == 1)  {
            sendMessage({ id: 'join'});
            $scope.sound.loop = true;
            $scope.sound.play();
            $scope.me.pubState = PUB_INPROGRESS;
        } else {
            $window.location.reload();
        }
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
            if ($scope.screenShare.webRtcEndpoint)
                $scope.screenShare.webRtcEndpoint['peerConnection'].close();
            sendMessage({
                id: 'leave'
            });
        } catch (e) {
            $log.error(e);
        }
    }
    
    $trainingSocket.onmessage = function(message) {
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
            case 'publishScreenResponse':
                publishScreenResponse(parsedMessage);
                break;
            case 'subscribeResponse':
                subscribeResponse(parsedMessage);
                break;
            case 'subscribeScreenResponse':
                subscribeScreenResponse(parsedMessage);
                break;
            case 'chat':
                receiveChat(parsedMessage);
                break;
            case 'presentation':
                processPresentationEvent(parsedMessage.event,parsedMessage.object);
                break;
            case 'fileShare':
                processFileShareEvent(parsedMessage.event,parsedMessage.object);
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
            $trainingSocket.send(jsonMessage);
        } catch (exc) {
            $log.info('Error sending message: ' );
            $window.location.reload();
        }
    };
     
    function joinResponse(message) {
        if (message.response == 'accepted') {
            publish();
            processPresentation(message.presentation);
            processFileShare(message.fileShare);
        } else {
            $location.path('/');
        }
    };

    function publish() {  
        var options = {
            localVideo: $scope.me.pubCamera,
            onicecandidate: onPublishIceCandidate,
            iceServers:iceServers,
            oncandidategatheringdone: onPublishComplete
        };
        $scope.me.pubWebRtcEndpoint = kurentoUtils.WebRtcPeer.WebRtcPeerSendonly(options, function(error) {
            if (error) {
                $log.error(error);
                return;
            }
            this.generateOffer(onPublish);
        });
        $scope.me.pubGatherToken = $timeout(function() {
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
            $log.info(error);
            return;
        }
        $scope.me.pubOfferSdp = offerSdp;        
    }

    function publishResponse(message) {
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
    
    $scope.resubscribe = function(pubId) {
        var publisher = _.find($scope.memberList, function(m) {
            return m.id == pubId;
        });
        if (publisher.invited && publisher.avail) {
            unsubscribe(publisher);
            subscribe(publisher);
        }
    }

    function unsubscribe(member) {
        if (member.subVideoSlot) {            
            member.subState = SUB_INITIAL;
            if (member.id!= $scope.presenter.id) {
                if (member.subVideoSlot.allocated) {
                    $scope.countAllocated--;
                }                
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
        if (member.id !== $scope.presenter.id) {
            $scope.countAllocated++;
        }
        member.subVideoSlot = slot;
        slot.member = member;
        slot.allocated = true;
        member.subState = SUB_INPROGRESS;
        var remoteCamera = document.getElementById(slot.videoId);
        var options = {
            remoteVideo: remoteCamera,
            onicecandidate: onSubscribeIceCandidate.bind({member:member }),
            iceServers: iceServers,
            oncandidategatheringdone: onSubscribeComplete.bind({member:member})
        }
        member.subGatherToken = $timeout(function(m) {
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
    

    function updateMemberList(onlineList) {
        var unSubList = [];
        var subList = [];
        var shareScreen = false;
        _.each($scope.memberList, function(curVal) {
            var newVal = _.find(onlineList, function(m) {
                return curVal.id == m._id;
            });
            if (newVal) {
                curVal.avail = newVal.avail;
                curVal.screenAvail = newVal.screenAvail;
                curVal.invited = newVal.invited;
                curVal.online = true;
                if (curVal.invited) {
                    if (curVal.avail) {
                        if (!curVal.subVideoSlot)
                            subList.push(curVal);
                        if (curVal.subVideoSlot && curVal.subState==SUB_INITIAL)
                            subList.push(curVal);
                    } else {
                        if (curVal.subVideoSlot )
                            unSubList.push(curVal);
                    }
                    if (curVal.screenAvail) {
                        if ( $scope.screenShare.state==SUB_INITIAL)
                            shareScreen =  true;
                    } 
                } else {
                    unSubList.push(curVal);
                }
            } else {
                if (curVal.invited) 
                    unSubList.push(curVal);
                curVal.invited = false;
                curVal.avail = false;
                curVal.screenAvail = false;
                curVal.online = false;
            }
        });        
        _.each(unSubList, function(member) {
            unsubscribe(member);
        });
        _.each(subList, function(member) {
            if (member.id != $scope.me.id || $scope.me.profile.role !='presenter')
                subscribe(member);
        });
        if ( $scope.me.profile.role!='presenter'  ) {
            if (shareScreen) 
                subscribeScreen();
            else
                unsubscribeScreen();
        }
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
        $scope.notiHandUp = false;
        if (!$scope.handUp) {
            sendMessage({id: 'handUp'});
        } else {
            sendMessage({id: 'handDown'});
        }
    };
    $scope.toggleSidenav = function(eleId) {
        $mdSidenav(eleId).toggle();
    };
    $scope.toggleMainVideo = function() {
        $scope.toggleVideoFlag = !$scope.toggleVideoFlag;
    }
    $scope.changeSizeSlide = function() {
        $scope.fullScreen = !$scope.fullScreen;
    }
    $scope.memberInvite = function(memberId) {
        if ($scope.countAllocated > 3) {
            return;
        }
        $scope.notiHandUp = true;

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
            $scope.countAllocated--;
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
            $trainingSocket.close();
        }
    });
    $window.onbeforeunload = function() {
        $trainingSocket.close();
        $interval.cancel(recordToken);
    };

    init();
    
    function processPresentation(presentation) {
        var lastIndexInsert = _.findLastIndex(presentation, {
            event:'insertSlide'
        });
        var presentationLast = [];
        if(lastIndexInsert && presentation) {
            presentationLast = presentation.slice(lastIndexInsert);
            _.each(presentationLast,function(msg){
                onWhiteboardEvent(msg.event, msg.object);
                getPageInfo();
            });
        }                
    }

    function receiveWhiteboardEvent(event,object) {
        onWhiteboardEvent(event,object);

        _.each(presentation.slice(lastIndexInsert),function(msg){
            onPresentationEvent(msg.event,msg.object);
            getPageInfo();
        });        
    }

    function processPresentationEvent(event,object) {
        onPresentationEvent(event,object);
        getPageInfo();
    }
    
    function onPresentationEvent(event,object) {
        if (event=='insertSlide') {
            pdfDelegate
                    .$getByHandle('my-pdf-container')
                    .load(object);
        }
        if (event=='switchPage') {
            pdfDelegate.$getByHandle('my-pdf-container').goToPage(object.currPage);
        }
        if (event=='zoomOutSlide') {
            pdfDelegate.$getByHandle('my-pdf-container').zoomTo(object.zoomAmount);
        }
        if (event=='zoomInSlide') {
            pdfDelegate.$getByHandle('my-pdf-container').zoomTo(object.zoomAmount);
        }
    }

    // Pdf viewer
    function getPageInfo() {
        $scope.currPage = pdfDelegate.$getByHandle('my-pdf-container').getCurrentPage();
        $scope.totalPages = pdfDelegate.$getByHandle('my-pdf-container').getPageCount();
    }

    $scope.pdfSwitchPage = function(offset) {
        var info = {
            currPage: 0,
            totalPages: pdfDelegate.$getByHandle('my-pdf-container').getPageCount()
        };       
        if (offset == 1) {
            pdfDelegate.$getByHandle('my-pdf-container').next();
        } else {
            pdfDelegate.$getByHandle('my-pdf-container').prev();
        }        
        getPageInfo();
        info.currPage = pdfDelegate.$getByHandle('my-pdf-container').getCurrentPage();
        sendMessage({id:'presentation',event:'switchPage',object:info});
    }


    $scope.zoomOut = function() {
        pdfDelegate.$getByHandle('my-pdf-container').zoomOut();
        $scope.zoom = ($scope.zoom === 50) ? 50 : ($scope.zoom - 10);
    }

    $scope.zoomIn = function() {
        pdfDelegate.$getByHandle('my-pdf-container').zoomIn();
        $scope.zoom += 10;
    }

    // Upload file
    // upload on file select or drop
    $scope.uploadPresentation = function (file) {
        if (!file) {
            return;
        }
        $meeting.uploadPresentation({file:file},function(result) {
            if (result.status && result.data.status) {
                console.log('url', result.data.url);
                 $scope.pdfUrl = result.data.url;
                 $scope.$apply();
                 pdfDelegate
                    .$getByHandle('my-pdf-container')
                    .load(result.data.url);
                sendMessage({id:'presentation',event:'insertSlide',object:result.data.url});
            }
        })
    };
    
    function processFileShare(fileShare) {
        _.each(fileShare,function(msg){
            onFileShareEvent(msg.event,msg.object)
        });        
    }
    
    function onFileShareEvent(event,object) {
        if (event=='addFile') {
            $scope.fileList.push(object);
        }
        if (event=='removeFile') {
            $scope.fileList = _.reject($scope.fileList,function(item) {
                return item.url == object.url
            });
        }

    }
    
    $scope.shareFile = function (file) {
        if (!file) {
            return;
        }
        console.log(file);
        $meeting.shareFile({file:file},function(result) {
            if (result.status && result.data.status) {
                console.log('url', result.data.url);
                 $scope.fileList.push({name:file.name,url:result.data.url});
                 $scope.$apply();
                sendMessage({id:'fileShare',event:'addFile',object:{name:file.name,url:result.data.url}});
            }
        })
    }
    
    $scope.removeFile = function (file) {
        if (!file) {
            return;
        }
        $scope.fileList = _.reject($scope.fileList,function(item) {
            return item.url == file.url
        });
        sendMessage({id:'fileShare',event:'removeFile',object:file});
           
    }
    
    $scope.shareScreen =  function() {
        if ($scope.screenShare.state != PUB_INITIAL)
            onUnshareScreen();
        $scope.screenShare.camera = document.getElementById('screenCamera');
        $screenShare.start(function (success, stream, source) {
            $scope.screenShare.camera.src = URL.createObjectURL(stream);
            var options = {
                            localVideo: $scope.screenShare.camera,
                            iceServers:iceServers,
                            videoStream: stream,
                            onicecandidate: onPublishScreenIceCandidate,
                            oncandidategatheringdone: onPublishScreenComplete
                          };
            stream.getVideoTracks()[0].onended = function () {
                onUnshareScreen();
              };
            $scope.screenShare.webRtcEndpoint = kurentoUtils.WebRtcPeer.WebRtcPeerSendonly(options, function(error) {
                if (error) {
                    $log.error(error);
                    return;
                }
                this.generateOffer(onPublishScreen);
            });
            $scope.screenShare.gatherToken = $timeout(function() {
                if ($scope.screenShare.state != PUB_CONNECTING) {
                    $scope.screenShare.state = PUB_CONNECTING;
                    sendPublishScreenData();
                }
            },GATHER_TIMEOUT);
        });
    }
    
    function onPublishScreenIceCandidate(candidate) {
        $scope.screenShare.candidateSendQueue.push(candidate);
    }

    function onPublishScreen(error, offerSdp) {
        if (error) {
            $log.info(error);
            return;
        }
        $scope.screenShare.offerSdp = offerSdp;
    }

    function publishScreenResponse(message) {
        if (message.response != 'accepted') {
            $log.info('Disconnect due to reject from server');
            $scope.disconnect();
        } else {
            $scope.screenShare.webRtcEndpoint.processAnswer(message.sdpAnswer);
            _.each(message.candidateList,function(candidate) {
                $scope.screenShare.webRtcEndpoint.addIceCandidate(candidate);
            })
            sendMessage({
                id: 'publishScreenAvail'
            });
            $scope.screenShare.state = PUB_CONNECTED;
        }
    }
    
    function onUnshareScreen() {
        console.log('Unshare screen');
        if ($scope.screenShare.gatherToken)
            $timeout.cancel($scope.screenShare.gatherToken);
        if ($scope.screenShare.timeoutToken)
            $timeout.cancel($scope.screenShare.timeoutToken);
        sendMessage({
            id: 'publishScreenUnavail'
        });
        $scope.screenShare.state = PUB_INITIAL;
        
    }
    
    function onPublishScreenComplete() {
        $timeout.cancel($scope.screenShare.gatherToken);
        if ($scope.screenShare.state != PUB_CONNECTING) {
            $scope.screenShare.state = PUB_CONNECTING;
            sendPublishScreenData();
        }
    }
    
    function sendPublishScreenData() {
        if (!$scope.screenShare.offerSdp || !$scope.screenShare.candidateSendQueue.length) {
            $log.info('Disconnect due to device error');
            $scope.disconnect();
            return;
        }
        var message = {
                id: 'publishScreen',
                sdpOffer: $scope.screenShare.offerSdp,
                candidateList: $scope.screenShare.candidateSendQueue
         };
        sendMessage(message);
        $scope.screenShare.offerSdp = null;
        $scope.screenShare.candidateSendQueue = [];
        $scope.screenShare.timeoutToken = $timeout(function() {
            if ($scope.screenShare.state != PUB_CONNECTED) {
                $log.info('Disconnect due to calling timeout');
                $scope.disconnect();
            }
        },CALLING_TIMEOUT);
    }
    
    function unsubscribeScreen() {
        $scope.screenShare.state = SUB_INITIAL;          
        if ($scope.screenShare.gatherToken)
            $timeout.cancel($scope.screenShare.gatherToken);
        try {
            if ($scope.screenShare.webRtcEndpoint)
                $scope.screenShare.wWebRtcEndpoint['peerConnection'].close();
        } catch (e) {
            $log.error(e);
        }
    }

    function subscribeScreen() {        
        if (!$scope.me.avail) {
            return;
        }
        $scope.screenShare.state = SUB_INPROGRESS;
        $scope.screenShare.camera = document.getElementById('screenCamera');
        var options = {
            remoteVideo: $scope.screenShare.camera,
            onicecandidate: onSubscribeScreenIceCandidate,
            iceServers: iceServers,
            oncandidategatheringdone: onSubscribeScreenComplete
        }
        $scope.screenShare.gatherToken = $timeout(function() {
            if ($scope.screenShare.state != SUB_CONNECTING) {
                sendSubscribeScreenData();
                $scope.screenShare.state = SUB_CONNECTING;
            }
        },GATHER_TIMEOUT);
        
        $scope.screenShare.webRtcEndpoint = kurentoUtils.WebRtcPeer.WebRtcPeerRecvonly(options, function(error) {
            if (error) {
                $log.error(error);
                return;
            }
            this.generateOffer(onSubscribeScreen);
        });
    }

    function onSubscribeScreenIceCandidate(candidate) {
        $scope.screenShare.candidateSendQueue.push(candidate);
    }

    function onSubscribeScreen(error, offerSdp) {
        if (error) {
            $log.error(error);
            return;
        }
        $scope.screenShare.sdpOffer = offerSdp;
    }

    function subscribeScreenResponse(message) {
        if (message.response != 'accepted') {
            $scope.screenShare.state = SUB_INITIAL;
            toastr.error('Server error, please try again later');
        } 
        else {            
            $scope.screenShare.webRtcEndpoint.processAnswer(message.sdpAnswer);
            $scope.screenShare.state = SUB_CONNECTED;
            _.each(message.candidateList,function(candidate) {
                $scope.screenShare.webRtcEndpoint.addIceCandidate(candidate);
            });
            sendMessage({
                id: 'subscribeScreenAvail',
                pubId:$scope.presenter.id
            });    
        }
    }
    
    function onSubscribeScreenComplete() {
        $timeout.cancel($scope.screenShare.gatherToken);
        if ($scope.screenShare.state != SUB_CONNECTING) {
            sendSubscribeScreenData();
            $scope.screenShare.state = SUB_CONNECTING;
        }        
    }
    
    function sendSubscribeScreenData() {
        if (!$scope.screenShare.sdpOffer || !$scope.screenShare.candidateSendQueue.length) {
            toastr.error("Device error");
            return;
        }
        var message = {
                id: 'subscribeScreen',
                sdpOffer: $scope.screenShare.sdpOffer,
                candidateList:$scope.screenShare.candidateSendQueue,
                pubId:$scope.presenter.id
            }
        sendMessage(message);
        $scope.screenShare.sdpOffer = null;
        $scope.screenShare.candidateSendQueue = [];
    }
});