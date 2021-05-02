module.exports.SocketClass = {
    Construct: function () {
        this.timeouts = [];
        this.intervals = [];
        this.customIntervalsHandlers = {};
        this.dataSender = [];
        this.waiter = false;
        this.watchdogs_setted = false;
        this.type = null;
        this.obj_id = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        this.name = this.remoteAddress + ":" + this.remotePort;
        this.params = [];
        this.l_params = [];
        this.tempparams = [];
        this.connected = getDateTime();
        this.active = false;
        this.mac = false;
        this.uniq_mac = false;
        this.type = false;
        this.params_finalized = false;

        console.ipLog('Connected: ', this.name);

        this.lastdata = Date.now();
        this.lastin = Date.now();
        this.forceUpdate = false;
        this.okVer = false;

        try {
            this.write("AT+NDBGL=0,0\r\n");
            this.write("AT+APPVER\r\n");

            this.setUpdate = false;
            this.update_started = false;
            this.setUpdate2 = 0;
            this.lastStrings = 0;
            this.timeUpdate = Date.now();

            this.errors_count = false;
            mixin(this, BaseDevice);
            mixin(this, updateDevice);

            for (eventName in this.events) {
                this.on(eventName, this.events[eventName]);
            }

        } catch (e) {
            DATA_HANDLERS.err_count++;
            this.drop(this, 'construct');
        }
    },

    events: {
        'data': function (data) {

            if (this.okVer) {
                this.lastdata = Date.now();
                this.lastin = Date.now();
                if (this.okVer) {
                    if (!this.watchdogs_setted) {
                        this.watchdogs_setted = true;
                        this.removeAllListeners('data');
                        this.off('data', this.events['data']);
                        this.on('data', this.onData);
                        this.onData(data);
                        this.setWatchDogs(this);
                    }

                } else {
                    console.myLog(' eerr data:', strData);
                }
                return;
            }

            var strData = data.toString('ascii');

            if (MyConfig.ipToWatch && this.remoteAddress.search(MyConfig.ipToWatch) !== -1) {
                console.ipLog(this.remoteAddress, '-------->>>>>>>>>>> strData--', strData);
                console.ipLog(this.remoteAddress, '-------->>>>>>>>>>> data.toString(\'hex\')--', data.toString('hex'));
                console.ipLog(this.remoteAddress, '-------->>>>>>>>>>> data.toString()--', data.toString());
                console.ipLog(this.remoteAddress, '-------->>>>>>>>>>> data--', data);

                console.ipLog(this.remoteAddress, '-------->>>>>>>>>>> SEND_WAIT--', SEND_WAIT);
                console.ipLog(this.remoteAddress, '-------->>>>>>>>>>> this.mac--', this.mac);
            }

            this.lastStrings = strData;

            if (this.mac && SEND_WAIT[this.mac]) {
                if (SEND_WAIT[this.mac] === 'KILL') {
                    delete (SEND_WAIT[this.mac]);
                    this.drop(this, 'kill comm');
                    return;
                }
                console.ipLog(this.remoteAddress, 'SEND TO ', this.mac, SEND_WAIT[this.mac]);
                this.write(SEND_WAIT[this.mac] + "\r\n");
                delete (SEND_WAIT[this.mac]);
            } else if (SEND_WAIT[this.remoteAddress]) {
                console.ipLog(this.remoteAddress, 'SEND TO ', this.remoteAddress, SEND_WAIT[this.remoteAddress]);
                this.write(SEND_WAIT[this.remoteAddress] + "\r\n");
                delete (SEND_WAIT[this.remoteAddress]);
            }

            if (this.checkVersion()) {
                DATA_HANDLERS.dev_count++;
                return;
            }
            this.lastStrings = '';
            _this.write("AT+APPVER\r\n");
            DATA_HANDLERS.err_count++;

        },
        'error': function (error) {
            console.ipLog(this.remoteAddress, 'error', error);
            this.drop(this, 'error');
        }
    },
    drop: function (_this, from) {
        console.ipLog(_this.remoteAddress, 'disconected on error', this.name, from);
        console.csvLog(_this.uid, 'OFFLINE', _this.paramsMaped);
        console.myLog('dropp ', from);
        _this.errors_count = true;
        this.clearWatchDogs(_this);

        try {
            if (oneclient[_this.mac] && oneclient[_this.mac].obj_id === _this.obj_id) {
                MAC2UID[_this.uid] = false;
                MAC2UID_REAL[_this.uid] = false;

                delete (UID2MAC[_this.mac]);
                delete (MAC2UID[_this.uid]);
                delete (UID2MAC_REAL[_this.uniq_mac ? _this.uniq_mac : _this.mac]);
                delete (MAC2UID_REAL[_this.uid]);

                delete (oneclient[_this.mac]);
                delete (real_oneclient[_this.uniq_mac ? _this.uniq_mac : _this.mac]);
                console.myLog('DELETE FROM INDEX ', _this.uid, _this.mac);
            }
        } catch (e) {
            console.myLog(_this.mac, _this.uid, 'DELETE FROM INDEX ERR', e);
        }

        try {
            for (uidIndex in UIDToToken[_this.uid]) {
                curToken = UIDToToken[_this.uid][uidIndex];
                if (typeof MobileTcpHandlers[curToken] != 'undefined') {
                    for (devIn in MobileTcpHandlers[curToken]) {
                        devCurr = MobileTcpHandlers[curToken][devIn];
                        devCurr.ascServer(_this.uid);
                    }

                }
            }
        } catch (e) {
            console.myLog('DELETE FROM INDEX ERR', e);
        }

        try {
            _this.end();
        } catch (e) {
            console.myLog('DELETE FROM INDEX ERR', e);
        }

        try {
            _this.destroy();
        } catch (e) {
            console.myLog('DELETE FROM INDEX ERR', e);
        }
        console.myLog('dropp END', from);

    },
    setWatchDogs: function (_this) {
        console.myLog(_this.mac, _this.uid, 'set watchdogs');
        console.ipLog(_this.remoteAddress, 'set watchdogs', _this.name);
        for (intervalFunc in timeouts.intervals) {
            if (!_this.intervals[intervalFunc]) {
                totalHandlers++;
            }
            clearInterval(_this.intervals[intervalFunc]);
            timeouts.intervals[intervalFunc]['func'].apply(_this);

            _this.intervals[intervalFunc] = setInterval(timeouts.intervals[intervalFunc]['func'].bind(_this), timeouts.intervals[intervalFunc]['time']);
        }

        for (customIntervalFunc in _this.customIntervals) {
            if (!_this.customIntervalsHandlers[customIntervalFunc]) {
                totalHandlers++;
            }
            console.myLog(_this.mac, _this.uid, 'set watchdog - ' + customIntervalFunc, customIntervalFunc);
            console.myLog(_this.mac, _this.uid, _this.customIntervals[customIntervalFunc]);
            console.myLog(_this.mac, _this.uid, _this.customIntervals[customIntervalFunc]['func']);
            console.myLog(_this.mac, _this.uid, _this.customIntervals[customIntervalFunc]['time']);
            clearInterval(_this.customIntervalsHandlers[customIntervalFunc]);
            _this.customIntervalsHandlers[customIntervalFunc] = setInterval(
                _this.customIntervals[customIntervalFunc]['func'],
                _this.customIntervals[customIntervalFunc]['time'],
                _this);
            console.myLog(_this.mac, _this.uid, 'SETTED ' + customIntervalFunc);
        }

        for (ondataFunc in timeouts.onData) {
            console.myLog(_this.mac, _this.uid, 'ondataFunc', ondataFunc);
            _this.on('data', timeouts.onData[ondataFunc].bind(_this));
        }

    },
    queryWriteInterval: false,
    clearWatchDogs: function (_this) {
        console.myLog('clear watchdogs');
        console.ipLog(_this.remoteAddress, 'clear watchdogs', _this.name);
        for (intervalFunc in _this.intervals) {
            totalHandlers--;
            clearInterval(_this.intervals[intervalFunc]);
            delete (_this.intervals[intervalFunc]);
        }
        for (customIntervalFunc in _this.customIntervalsHandlers) {
            totalHandlers--;
            clearInterval(_this.customIntervalsHandlers[customIntervalFunc]);
            delete (_this.customIntervalsHandlers[customIntervalFunc]);
        }

        if (_this.touchHandler) {
            totalHandlers--;
            clearInterval(_this.touchHandler);
            _this.touchHandler = false;
        }

        if (_this.queryWriteInterval) {
            clearInterval(_this.queryWriteInterval);
        }

    }

};
