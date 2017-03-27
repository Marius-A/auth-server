'use strict';

angular.module('Authentication')
    .config(['$routeProvider', function ($routeProvider) {

        $routeProvider
            .when('/login', {
                controller: 'LoginController',
                templateUrl: '/bundles/authmanager/modules/authentication/views/login.html',
                hideMenus: true
            });
    }]);