/* 
 * Group: api
 * 
 * Package: sound
 * 
 * Summary:
 * 		An API that allows an app to play audio content.
 */
dojo.require("dijit._Widget");
dojo.provide("api.sound");
dojo.declare("api.sound", dijit._Widget, {
	src: "",
	loop: false,
	autoStart: false,
	backend: null,
	postCreate: function() {
		this.domNode.style.position="absolute";
		this.domNode.style.left="-999px";
		this.domNode.style.top="-999px";
		document.body.appendChild(this.domNode);
		var backends = ["html", /*"flash",*/ "embed"];
		for(k in backends) {
			var i = backends[k];
			var backend = new api.sound[i]({
				id: this.id,
				src: this.src,
				loop: this.loop,
				autoStart: this.autoStart
			});
			if(backend.testCompat() === true) {
				this.backend = backend;
				this.capabilities = backend.capabilities;
				backend.domNode = this.domNode;
				this.backend.startup();
				break;
			}
		}
		dojo.forEach([
			"id3",
			"volume",
			"play",
			"pause",
			"stop",
			"position",
			"duration"
		], function(i) {
			this[i] = dojo.hitch(this.backend, i);
		}, this)
	},
	uninitialize: function() {
		this.backend.uninitialize();
		document.body.removeChild(this.domNode);
	}
});

dojo.declare("api.sound._backend", null, {
	domNode: null,
	src: "",
	loop: false,
	autoStart: false,
	capabilities: {
		play: true,
		pause: true,
		stop: true,
		duration: true,
		position: true,
		volume: true,
		id3: true
	},
	startup: function() {
		//do startup tasks here such as embedding an applet
	},
	constructor: function(args) {
		this.src = args.src;
		this.loop = args.loop || false;
		this.autoStart = args.autoStart || false;
	},
	play: function() {},
	pause: function() {},
	stop: function() {},
	uninitialize: function() {},
	volume: function() {},
	position: function() {},
	duration: function() {},
	id3: function() {},
	testCompat: function() {
		//test for compatiblility
		return true;
	}
});
	
dojo.declare("api.sound.html", api.sound._backend, {
	htmlSound: null,
	testCompat: function() {
			return typeof Audio != "undefined";
	},
	startup: function() {
		this.htmlSound = new Audio(this.src);
		if(this.autoStart) this.htmlSound.autoPlay = true;
	},
	play: function() {
		this.htmlSound.play();
	},
	pause: function() {
		this.htmlSound.pause();
	},
	stop: function() {
		this.htmlSound.stop();
	},
	position: function(v) {
		if(v) this.htmlSound.currentTime = v;
		return this.htmlSound.currentTime;
	},
	duration: function() {
		return this.htmlSound.duration;
	},
	volume: function(l) {
		if(l) this.htmlSound.volume = l;
		return this.htmlSound.volume;
	}
});
	
dojo.declare("api.sound.flash", api.sound._backend, {
	_startPos: 0,
	id: 0,
	play: function() {
		dojox.flash.comm.callFunction(this.id, "start", [this._startPos]);
	},
	pause: function() {
		dojox.flash.comm.callFunction(this.id, "stop");
		this._startPos = this.position() / 1000;
	},
	stop: function() {
		this._startPos = 0;
		dojox.flash.comm.callFunction(this.id, "stop");
	},
	position: function(v) {
		if(v) dojox.flash.comm.setValue(this.id, "position", v);
		else {
			var ret;
			dojox.flash.comm.getValue(this.id, "position", function(a) {
				ret = a;
			})
			return ret;
		}
	},
	duration: function(v) {
		if(v) dojox.flash.comm.setValue(this.id, "duration", v);
		else {
			var ret;
			dojox.flash.comm.getValue(this.id, "duration", function(a) {
				ret = a;
			})
			return ret;
		}
	},
	id3: function() {
		var ret;
		dojox.flash.comm.getValue(this.id, "id3", function(a) {
			ret = a;
		});
		return ret;
	},
	volume: function(val) {
		if(val) 
			dojox.flash.comm.setValue(this.id, "volume", val);
		else {
			var ret;
			dojox.flash.comm.getValue(this.id, "volume", function(getVal){
				ret = getVal;
			});
			return ret;
		}
	},
	checkCompat: function() {
		return dojox.flash.info.commVersion != -1;
	},
	startup: function() {
		dojox.flash.comm.makeObj(this.id, "sound");
		//dojox.flash.comm.attachEvent(this.id, "onLoad");
	}
});
	
dojo.declare("api.sound.embed", api.sound._backend, {
	capabilities: {
		play: true,
		pause: false,
		stop: true,
		duration: false,
		position: true,
		volume: false,
		id3: false
	},
	timeInterval: 1000,
	timer: null,
	play: function() {
		if (this.domNode.innerHTML != "") 
			this.stop();
		this.position = 0;
		this.domNode.innerHTML = "<embed src=\"" + this.src + "\" hidden=\"true\" autoplay=\"true\" loop=\"" + (this.loop ? "true" : "false") + "\">";
		this.timer = setInterval(dojo.hitch(this, this.fixtime), this.timeInterval);
	},
	fixtime: function() {
		this.position += this.timeInterval;
	},
	stop: function() {
		clearInterval(this.timer);
		this.domNode.innerHTML = "";
	},
	uninitialize: function() {
		clearInterval(this.timer);
	}
});
