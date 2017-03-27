'use strict';

angular.module('Url');

angular.module('Client')
    .factory(
        'ClientApi',
        [
            'UrlService',
            function (UrlService) {
                var service = {};

                service.getClients = function (data, successCallback, errorCallback) {
                    data = data || {};
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients",
                        "method": "GET",
                        "query": {
                            "order": data.order || "id-",
                            "limit": data.limit || 10,
                            "offset": data.offset || 0,
                            "filters": data.filters || {}
                        },
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.getClientById = function (clientId, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;

                    if (!clientId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId,
                        "method": "GET",
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.removeClient = function (clientId, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;

                    if (!clientId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId,
                        "method": "DELETE",
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.saveClient = function (client, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    var clientId = parseInt(client.id) || 0;

                    if (!clientId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId,
                        "method": "PUT",
                        "data": client,
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.addClient = function (client, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients",
                        "method": "POST",
                        "data": client,
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.getClientRoles = function (clientId, data, successCallback, errorCallback) {
                    data = data || {};
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;

                    if (!clientId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId + "/roles",
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

                service.getClientRequiredRoles = function (clientId, data, successCallback, errorCallback) {
                    data = data || {};
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;

                    if (!clientId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId + "/required_roles",
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

                service.addClientRole = function (clientId, roleId, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;
                    roleId = parseInt(roleId) || 0;

                    if (!clientId || !roleId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId + "/roles/" + roleId,
                        "method": "PUT",
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.removeClientRole = function (clientId, roleId, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;
                    roleId = parseInt(roleId) || 0;

                    if (!clientId || !roleId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId + "/roles/" + roleId,
                        "method": "DELETE",
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.addClientRequiredRole = function (clientId, roleId, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;
                    roleId = parseInt(roleId) || 0;

                    if (!clientId || !roleId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId + "/required_roles/" + roleId,
                        "method": "PUT",
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.removeClientRequiredRole = function (clientId, roleId, successCallback, errorCallback) {
                    successCallback = successCallback || function () {};
                    errorCallback = errorCallback || function () {};
                    clientId = parseInt(clientId) || 0;
                    roleId = parseInt(roleId) || 0;

                    if (!clientId || !roleId) {
                        return errorCallback();
                    }

                    UrlService.request({
                        "service": "auth",
                        "path": "/api/clients/" + clientId + "/required_roles/" + roleId,
                        "method": "DELETE",
                        "success": function (result) {
                            successCallback(result);
                        },
                        "error": function (result) {
                            errorCallback(result);
                        }
                    });
                };

                service.getClientRolesAll = function (clientId, callback, limit, offset, roles) {
                    roles = roles || [];
                    limit = limit || 100;
                    offset = offset || 0;
                    service.getClientRoles(clientId, {
                        limit: limit,
                        offset: offset
                    }, function(result) {
                        if (result.data.length < limit) {
                            return callback(roles.concat(result.data));
                        }
                        return service.getClientRolesAll(clientId, callback, limit, offset + limit, roles.concat(result.data));
                    }, function (response) {
                        callback(roles);
                    });
                };

                service.getClientRequiredRolesAll = function (clientId, callback, limit, offset, roles) {
                    roles = roles || [];
                    limit = limit || 100;
                    offset = offset || 0;
                    service.getClientRequiredRoles(clientId, {
                        limit: limit,
                        offset: offset
                    }, function(result) {
                        if (result.data.length < limit) {
                            return callback(roles.concat(result.data));
                        }
                        return service.getClientRequiredRolesAll(clientId, callback, limit, offset + limit, roles.concat(result.data));
                    }, function (response) {
                        callback(roles);
                    });
                };

                return service;
            }
        ]
    );
