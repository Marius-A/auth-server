'use strict';

angular.module('Role')
    .config(['$routeProvider', function ($routeProvider) {

        $routeProvider

            .when('/roles', {
                controller: 'RoleController',
                templateUrl: '/bundles/authmanager/modules/role/views/role.html'
            });
    }]);