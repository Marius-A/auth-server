'use strict';

angular.module('Client')
    .config(['$routeProvider', function ($routeProvider) {

        $routeProvider
            .when('/clients', {
                controller: 'ClientController',
                templateUrl: '/bundles/authmanager/modules/client/views/client.html'
            })
    }]);