'use strict';

angular.module('User')
    .config(['$routeProvider', function ($routeProvider) {

        $routeProvider

            .when('/users', {
                controller: 'UserController',
                templateUrl: '/bundles/authmanager/modules/user/views/user.html'
            });
    }]);