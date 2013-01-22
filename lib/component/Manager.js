var _ = require("underscore/underscore");
var EventEmitter = require('events').EventEmitter;
var util = require("util");
var path = require("path");
var ComponentLoader = require("./Loader");

var option_defaults = {
    path: "lib/components",
    whitelist: ["*"],
    blacklist: [],
    arguments: {}
};

var ComponentManager = module.exports = function(options){
    EventEmitter.call(this);

    this._options = _.defaults(options, option_defaults);
    this._loadedComponents = {};
    this._apis = {};
    this._environment = options.environment;
    
    //fix the path so that if it's relative, that it's relative to the root given in the environment var
    this._options.path = path.resolve(options.environment.rootPath, this._options.path);

    //ignore any components starting with a '_'
    this._options.blacklist.splice(0, 0, "^_*");

    var loader = this._loader = new ComponentLoader(this._options);
    var self = this;
    loader.getComponentList(function(list){
        //note that `components` is sorted in a way such that dependencies will be resolved
        var components = loader.loadComponents(list);
        for(var i in components){
            //construct the component
            self._loadedComponents[i] = new components[i](self, self._options.arguments[i] || {}, self._environment);
        }
        //TODO: this should be emitted once all the components emit "ready"!
        self.emit("ready");
    });
}

util.inherits(ComponentManager, EventEmitter);

ComponentManager.prototype.get = function(name){
    return this._loadedComponents[name];
}

ComponentManager.prototype.exposeApi = function(name, obj){
    this._apis[name] = obj;
}

ComponentManager.prototype.getApi = function(name, options){
    //TODO: pass `options` to some sort of security wrapper and wrap the API with it
    if(!this._apis[name])
        throw new Error("API `"+name+"` not found!");
    return this._apis[name];
}

ComponentManager.prototype.addMessageHandler = function(compName, handler, source, filters){
    if(!this._loadedComponents[compName])
        throw new Error("Problem adding message handler for `"+source+"`: Component `"+compName+"` not loaded! Maybe it was blacklisted/missing from whitelist?");
    else if(!this._loadedComponents[compName].dispatcher)
        throw new Error("Problem adding message handler for `"+source+"`: Component `"+compName+"` doesn't have a message dispatcher exposed!");
    else
        return {
            component: compName,
            handle: this._loadedComponents[compName].dispatcher.addHandler(handler, source, filters)
        }
}

ComponentManager.prototype.removeMessageHandler = function(handle){
    return this._loadedComponents[handle.component].dispatcher.removeHandler(handle.handle);
}