module.exports.convector = {
    dtype:'convector',
	onCommandCustom: {
		136: 'handleParams',
		138: 'handleParams',
		9: 'handleParams',
	},
	commandsExtend: {
		getParams: Buffer.from([0xAA, 0x03, 0x08, 0x10, 0x04, 0xC9])
	},

	getParamsMap: function () {
		return {
			'state': '0',
			'temp_goal': '0',
			'mode': '0',
			'power': '0',
			'hours': '0',
			'minutes': '0',
			'timer': '0',
			'current_temp': '0',
			'current_power': '0',
			'code': '0',
			'led': '0'
		}
	},

	handleParams: function () {
		console.myLog(this.mac, this.uid, 'handle params of CONV');

		if ((this.params.join('') !== this.tempparams.join('')) || this.params.join('') === '') {

			console.myLog(this.mac, this.uid, 'OLD PARAMS: ' + this.params.join(' '));
			this.params = this.tempparams;
			this.paramsMaped = this.getParamsMap();

			var cn = 3;
			for (key in this.paramsMaped) {
				this.paramsMaped[key] = this.params[cn++];
			}

			console.myLog(this.mac, this.uid, 'NEW PARAMS: ' + this.params.join(' '));
			this.tempparams = [];
			this.params_finalized = true;
			console.csvLog(this.uid,'FROM',this.paramsMaped);

		}

		if (this.waiter) {
			console.myLog(this.mac, this.uid, '->>> send to php');
			clearTimeout(this.waiter.timeout);
            clearTimeout(this.waiter.timeout2);
			this.waiter.write(JSON.stringify({'status': true}));
			this.waiter.write("\n");
			this.waiter = false;
		}
	},
	setParams: function (params) {

		var arParams = Object.keys(params).map(function (key) {
			return params[key] * 1;
		});

		console.csvLog(this.uid, 'TO', arParams);

		this.sendCommand(0x0A, arParams);

		return true;
	}

}