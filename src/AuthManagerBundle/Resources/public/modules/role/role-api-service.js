'use strict';

angular.module('Url');

angular.module('Role')
    .factory(
        'RoleApi',
        [
            'UrlService',
            function (UrlService) {
                var service = {};

                service.getRoles = function (data, successCallback, errorCallback) {
                    data = data || {};
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/roles",
                        "method": "GET",
                        "query": {
                            "limit": data.limit || 10,
                            "offset": data.offset || 0
                        },
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.getRolesAll = function (callback, limit, offset, roles) {
                    roles = roles || [];
                    limit = limit || 100;
                    offset = offset || 0;
                    service.getRoles({
                        limit: limit,
                        offset: offset
                    }, function(result) {
                        if (result.data.length < limit) {
                            return callback(roles.concat(result.data));
                        }
                        return service.getRolesAll(callback, limit, offset + limit, roles.concat(result.data));
                    }, function (response) {
                        callback(roles);
                    });
                };

                return service;
            }
        ]
    );
