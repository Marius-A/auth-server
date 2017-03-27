'use strict';

angular.module('Base64');
angular.module('Authentication');

angular.module('Url', [])
    .factory('UrlService',
        [
            '$http', '$rootScope', 'Base64', 'AuthenticationService',
            function ($http, $rootScope, Base64, AuthenticationService) {
                var service = {};
                function encodeObject (obj) {
                    if (!(obj instanceof Array) && !(obj instanceof Object)) {
                        return encodeURIComponent(obj);
                    }

                    var rspObj = (obj instanceof Array) ? [] : {};
                    for (var key in obj) {
                        rspObj[key] = encodeObject(obj[key]);
                    }
                    return rspObj;
                };

                service.encodeObject = encodeObject;

                var calculateUrl = function (data) {
                    data.query = data.query || {};
                    var returnUrl = data.url || service.defaults.registredServices[data.service] + data.path;
                    returnUrl += "?" + decodeURIComponent($.param(encodeObject(data.query || "")));
                    returnUrl  = (service.defaults.proxy) ? service.defaults.proxy + encodeURIComponent(returnUrl) : returnUrl;

                    return returnUrl;
                };

                service.defaults = {};
                service.defaults.processUrl = function(url) {
                    return url;
                };

                service.defaults.proxy = false;
                service.defaults.registredServices = {};
                service.request = function (data) {
                    AuthenticationService.RefreshToken(function(){
                        var request = {};

                        data.processUrl = data.processUrl || service.defaults.processUrl;
                        data.success = data.success || function() {};
                        data.error = data.error || function() {};

                        request.url = data.processUrl(calculateUrl(data));
                        request.method = data.method;
                        request.data = data.data;

                        $http(request).then(data.success, data.error);
                    });
                };

                service.getLocation = function(href) {
                    var l = document.createElement('a');
                    l.href = href;
                    return l;
                };

                return service;
            }
        ]
    );