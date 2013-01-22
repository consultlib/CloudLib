var moduleSelector = require("../moduleSelector");

var ComponentLoader = module.exports = function(options){
    this._options = options;
}

ComponentLoader.prototype.getComponentList = function(cb){
    var self = this;
    var path = this._options.path;
    moduleSelector.getCandidates(path, "component", function(list){
        cb(self._filterComponents(list));
    });
}

ComponentLoader.prototype._filterComponents = function(list){
    var whitelist = this._options.whitelist;
    var blacklist = this._options.blacklist;

    return moduleSelector.applyFiltering(list, whitelist, blacklist);
}


ComponentLoader.prototype.loadComponents = function(list){
    //here we do the heavy lifting and figure out what order to load components in based on their dependencies
    var manifests = this._loadManifests(list);

    var loadOrder = this._getComponentLoadOrder(list, manifests);
    var components = {};
    var self = this;
    loadOrder.forEach(function(compName){
        var constructorName = self._getConstructorName(compName);
        components[compName] = require(self._options.path+"/"+compName+"/"+constructorName);
    });

    return components;
}

ComponentLoader.prototype._getConstructorName = function(compName){
    //this basically converts from underscore naming convention to camel case

    //capitalize first letter
    compName = compName.charAt(0).toUpperCase() + compName.substr(1);

    //now replace _[a-z] with [A-Z]
    return compName.replace(/(_[a-z])/g, function(str){ return str.toUpperCase().replace("_", "")});
};

ComponentLoader.prototype._getComponentLoadOrder = function(list, manifests){
    //quickly check to make sure we can satisfy all dependencies (no dependencies on non-existant components)
    list.forEach(function(i){
        manifests[i].depends.forEach(function(j){
            if(!manifests[j]){
                throw new Error("Component '"+i+"' depends on component '"+j+"', but we couldn't find it! Maybe it was blacklisted or excluded from the whitelist?");
            }
        });
    });


    var loadOrder = [];

    //do topological sorting of the components so we know our load order

    //gather a non-distinct list of components required by other components
    var requiredComponents = [];
    for(var c in manifests){
        requiredComponents = requiredComponents.concat(manifests[c].depends);
    }
    //now remove any of these from `list` so we're left with orphan components
    //that aren't required by anything
    requiredComponents.forEach(function(c){
        var index = list.indexOf(c);
        if(index != -1)
            list.splice(index, 1);
    });

    //finally, we use an algorithm from wikipedia to sort topologically.
    //note that this will cause a crash if there's a cyclical dependency.
    var visitedComponents = {};
    var visit = function(n){
        if(!visitedComponents[n]){
            visitedComponents[n] = true;
            manifests[n].depends.forEach(visit);
            loadOrder.unshift(n);
        }
    };
    list.forEach(visit);

    return loadOrder;
}

ComponentLoader.prototype._loadManifests = function(list){
    var path = this._options.path;
    var manifests = {};
    list.forEach(function(component){
        manifests[component] = require(path+"/"+component+"/manifest");
    });
    return manifests;
}