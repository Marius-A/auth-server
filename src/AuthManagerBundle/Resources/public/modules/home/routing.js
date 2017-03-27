'use strict';

angular.module('Home')
    .config(['$routeProvider', function ($routeProvider) {

        $routeProvider
            .when('/', {
                controller: 'HomeController',
                templateUrl: '/bundles/authmanager/modules/home/views/home.html'
            })

            .otherwise({ redirectTo: '/' });
    }]);
