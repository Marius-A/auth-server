'use strict';

angular.module('Url');
angular.module('Role');

angular.module('Client')
    .controller(
        'ClientController',
        [
            '$scope', 'ClientApi', 'RoleApi', 'UrlService',
            function ($scope, ClientApi, RoleApi, UrlService) {
                $scope.clients = [];
                $scope.clientsPerPage = 10;
                $scope.clientsPageNo = 1;
                $scope.showMoreClients = true;
                $scope.clientsNameSearch = "";
                $scope.clientNameSearch = "";
                $scope.client = {};

                var allowedGrantTypes = {
                    "Password": "password",
                    "Client Credentials": "client_credentials",
                    "Refresh Token": "refresh_token",
                    "Authorization Code": "authorization_code",
                    "Token": "token"
                };
                var allowedGrantTypesKeys = {
                    "password": "Password",
                    "client_credentials": "Client Credentials",
                    "refresh_token": "Refresh Token",
                    "authorization_code": "Authorization Code",
                    "token": "Token"
                };

                function getAllowedGrantTypes(list) {
                    var grantTypes = [];
                    for (var i = 0; i < list.length; i++) {
                        grantTypes.push(allowedGrantTypes[list[i]]);
                    }
                    return grantTypes;
                }

                function getKeys(obj) {
                    var keys = [];
                    for (var i in obj) {
                        keys.push(i);
                    }
                    return keys;
                }

                $scope.clientSearch = function () {
                    $scope.clientsPageNo = 1;
                    $scope.showMoreClients = true;
                    $scope.clients = [];
                    getClients();
                };

                function getClients() {
                    var limit = $scope.clientsPerPage;
                    var offset = ($scope.clientsPageNo - 1 ) * $scope.clientsPerPage;
                    $scope.clientsPageNo++;
                    ClientApi.getClients(
                        {
                            "limit": limit,
                            "offset": offset,
                            "filters": {
                                "name^": $scope.clientNameSearch
                            }
                        },
                        function (result) {
                            if (result.data.length < $scope.clientsPerPage) {
                                $scope.showMoreClients = false;
                            }
                            $scope.clients = $scope.clients.concat(result.data);
                        },
                        function () {
                            $scope.showMoreClients = false;
                        }
                    );
                }

                function prepareRoles(roles, selected) {
                    var rolesObj = {};
                    var selected = selected || {};
                    var rolesList = [];
                    for (var idx in roles) {
                        var role = roles[idx];
                        if (rolesObj[role.id]) {
                            rolesObj[role.id].text = role.role;
                            rolesObj[role.id].state.selected = (selected[role.role]) ? true : false;
                        } else {
                            rolesObj[role.id] = {
                                id: role.id,
                                text: role.role,
                                state: {
                                    opened: true,
                                    selected: (selected[role.role]) ? true : false
                                },
                                children: []
                            };
                        }
                        if (role.parent_id === null) {
                            rolesList.push(rolesObj[role.id]);
                            continue;
                        }
                        if (rolesObj[role.parent_id]){
                            rolesObj[role.parent_id].children.push(rolesObj[role.id]);
                        } else {
                            rolesObj[role.parent_id] = {
                                id: role.parent_id,
                                state: {
                                    opened: true
                                },
                                children: [
                                    rolesObj[role.id]
                                ]
                            }
                        }
                    }
                    return rolesList;
                }

                function populateForm (clientId, callback) {
                    ClientApi.getClientById(
                        clientId,
                        function (result) {
                            $scope.client = result.data;
                            RoleApi.getRolesAll(function (roles) {
                                async.parallel(
                                    [
                                        function (cbk) {
                                            ClientApi.getClientRolesAll(clientId, function(cr) {
                                                var clientRoles = {};
                                                for (var i in cr) {
                                                    clientRoles[cr[i].role] = true;
                                                }
                                                var croles = prepareRoles(roles, clientRoles);
                                                $('#roles-container').html('<div id="roles"></div>');
                                                $('#roles').jstree({
                                                    "plugins" : [ "wholerow", "checkbox" ],
                                                    "core": {
                                                        "data": croles
                                                    },
                                                    "checkbox" : {
                                                        "three_state" : false,
                                                    }
                                                });
                                                $('#roles').jstree(true).redraw(true);
                                                return cbk();
                                            });
                                        },
                                        function (cbk) {
                                            ClientApi.getClientRequiredRolesAll(clientId, function (crr) {
                                                var clientRequiredRoles = {};
                                                for (var i in crr) {
                                                    clientRequiredRoles[crr[i].role] = true;
                                                }
                                                var requiredRoles = prepareRoles(roles, clientRequiredRoles);
                                                $('#required-roles-container').html('<div id="required-roles"></div>');
                                                $('#required-roles').jstree({
                                                    "plugins" : [ "wholerow", "checkbox" ],
                                                    "core": {
                                                        "data": requiredRoles
                                                    },
                                                    "checkbox" : {
                                                        "three_state" : false,
                                                    }
                                                });
                                                $('#required-roles').jstree(true).redraw(true);
                                                return cbk();
                                            });
                                        }
                                    ],
                                    function () {
                                        $("#allowed-grant-types").tagit({
                                            availableTags: getKeys(allowedGrantTypes),
                                            autocomplete: {delay: 0, minLength: 1},
                                            showAutocompleteOnFocus: true
                                        });
                                        $("#allowed-grant-types").tagit('removeAll');
                                        for (var i in $scope.client.allowed_grant_types) {
                                            $("#allowed-grant-types").tagit("createTag", allowedGrantTypesKeys[$scope.client.allowed_grant_types[i]]);
                                        }
                                        $("#redirect-uris").tagit();
                                        $("#redirect-uris").tagit('removeAll');
                                        for (var i in $scope.client.redirect_uris) {
                                            $("#redirect-uris").tagit("createTag", $scope.client.redirect_uris[i]);
                                        }
                                        $('#name').focus();
                                        callback();
                                    }
                                );
                            });
                        }
                    );
                }

                $scope.editClient = function (clientId) {
                    populateForm(clientId, function () {
                        $scope.clientModal = "Edit Client " + clientId;
                    });
                };

                $scope.cloneClient = function (clientId) {
                    populateForm(clientId, function () {
                        $scope.clientModal = "New Client";
                        delete $scope.client.id;
                    });
                };

                $scope.addClient = function () {
                    $scope.client = {};
                    $scope.clientModal = "New Client";
                    RoleApi.getRolesAll(function (roles) {
                        roles = prepareRoles(roles);
                        $('#required-roles-container').html('<div id="required-roles"></div>');
                        $('#required-roles').jstree({
                            "plugins" : [ "wholerow", "checkbox" ],
                            "core": {
                                "data": roles
                            },
                            "checkbox" : {
                                "three_state" : false,
                            }
                        });
                        $('#roles-container').html('<div id="roles"></div>');
                        $('#roles').jstree({
                            "plugins" : [ "wholerow", "checkbox" ],
                            "core": {
                                "data": roles
                            },
                            "checkbox" : {
                                "three_state" : false,
                            }
                        });

                        $("#allowed-grant-types").tagit({
                            availableTags: getKeys(allowedGrantTypes),
                            autocomplete: {delay: 0, minLength: 0},
                            showAutocompleteOnFocus: true
                        });
                        $("#allowed-grant-types").tagit('removeAll');
                        $("#redirect-uris").tagit();
                        $("#redirect-uris").tagit('removeAll');
                    });
                };

                function saveClientRoles(clientId, roles, oldRoles, callback) {
                    roles = roles || [];
                    oldRoles = oldRoles || [];
                    var addRoles = [];
                    for (var i in roles) {
                        if (oldRoles.indexOf(roles[i]) == -1) {
                            addRoles.push(roles[i]);
                        } else {
                            oldRoles.splice(oldRoles.indexOf(roles[i]), 1);
                        }
                    }
                    async.parallel(
                        [
                            function (cbk) {
                                async.map(
                                    addRoles,
                                    function (roleId, _cbk) {
                                        ClientApi.addClientRole(
                                            clientId,
                                            roleId,
                                            function(){
                                                _cbk();
                                            },
                                            function(){
                                                _cbk();
                                            }
                                        );
                                    },
                                    function(){
                                        cbk();
                                    }
                                );
                            },
                            function (cbk) {
                                async.map(
                                    oldRoles,
                                    function (roleId, _cbk) {
                                        ClientApi.removeClientRole(
                                            clientId,
                                            roleId,
                                            function(){
                                                _cbk();
                                            },
                                            function(){
                                                _cbk();
                                            }
                                        );
                                    },
                                    function(){
                                        cbk();
                                    }
                                );
                            }
                        ],
                        function(){
                            callback();
                        }
                    );
                };

                function saveClientRequiredRoles(clientId, roles, oldRoles, callback) {
                    roles = roles || [];
                    oldRoles = oldRoles || [];
                    var addRoles = [];
                    for (var i in roles) {
                        if (oldRoles.indexOf(roles[i]) == -1) {
                            addRoles.push(roles[i]);
                        } else {
                            oldRoles.splice(oldRoles.indexOf(roles[i]), 1);
                        }
                    }
                    async.parallel(
                        [
                            function (cbk) {
                                async.map(
                                    addRoles,
                                    function (roleId, _cbk) {
                                        ClientApi.addClientRequiredRole(
                                            clientId,
                                            roleId,
                                            function(){
                                                _cbk();
                                            },
                                            function(){
                                                _cbk();
                                            }
                                        );
                                    },
                                    function(){
                                        cbk();
                                    }
                                );
                            },
                            function (cbk) {
                                async.map(
                                    oldRoles,
                                    function (roleId, _cbk) {
                                        ClientApi.removeClientRequiredRole(
                                            clientId,
                                            roleId,
                                            function(){
                                                _cbk();
                                            },
                                            function(){
                                                _cbk();
                                            }
                                        );
                                    },
                                    function(){
                                        cbk();
                                    }
                                );
                            }
                        ],
                        function(){
                            callback();
                        }
                    );
                };

                function saveRoles(client, callback) {
                    async.parallel(
                        [
                            function (cbk) {
                                saveClientRoles(
                                    client.id,
                                    client.roles,
                                    client.old_roles,
                                    function (){
                                        cbk();
                                    }
                                );
                            },
                            function (cbk) {
                                saveClientRequiredRoles(
                                    client.id,
                                    client.required_roles,
                                    client.old_required_roles,
                                    function (){
                                        cbk();
                                    }
                                );
                            }
                        ],
                        function() {
                            callback();
                        }
                    );
                };

                $scope.saveClient = function () {
                    $scope.client.allowed_grant_types = getAllowedGrantTypes($("#allowed-grant-types").tagit('assignedTags'));
                    $scope.client.redirect_uris = $("#redirect-uris").tagit('assignedTags');
                    $scope.client.old_roles = $scope.client.roles;
                    $scope.client.roles = $('#roles').jstree('get_checked');
                    $scope.client.old_required_roles = $scope.client.required_roles;
                    $scope.client.required_roles = $('#required-roles').jstree('get_checked');
                    var clientRequest = {
                        "name": $scope.client.name,
                        "allowed_grant_types": $scope.client.allowed_grant_types,
                        "default_email": $scope.client.default_email,
                        "password_reset_url": $scope.client.password_reset_url,
                        "redirect_uris": $scope.client.redirect_uris
                    };
                    if ($scope.client.encoder) {
                        clientRequest["encoder"] = $scope.client.encoder;
                    }
                    if ($scope.client.id) {
                        clientRequest.id = $scope.client.id;
                        ClientApi.saveClient(
                            clientRequest,
                            function (response) {
                                saveRoles($scope.client, function() {
                                    $('#clientModal').modal('hide');
                                    $scope.clientSearch();
                                });
                            },
                            function (errResponse) {
                                $('#clientModal').modal('hide');
                            }
                        );
                    } else {
                        ClientApi.addClient(
                            clientRequest,
                            function (response) {
                                var headers = response.headers();
                                var clientId = parseInt(UrlService.getLocation(headers.location).pathname.split('/')[3]);
                                $scope.client.id = clientId;
                                saveRoles($scope.client, function() {
                                    $('#clientModal').modal('hide');
                                    $scope.clientSearch();
                                });
                            },
                            function (errResponse) {
                                $scope.client.errors = errResponse.data.errors;
                            }
                        );
                    }
                };

                $scope.removeClient = function (clientId) {
                    ClientApi.getClientById(
                        clientId,
                        function (result) {
                            $scope.client = result.data;
                        },
                        function () {
                            $('#removeClientModal').modal('hide');
                        }
                    );
                };

                $scope.removeClientAction = function (clientId) {
                    ClientApi.removeClient(
                        clientId,
                        function (result) {
                            $scope.clientSearch();
                        },
                        function (result) {

                        }
                    );
                };

                $scope.getClients = getClients;
                getClients();
            }
        ]
    );