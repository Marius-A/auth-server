'use strict';

angular.module('Authentication');
angular.module('Home');
angular.module('Url');
angular.module('Client');
angular.module('Role');
angular.module('User');
angular.module('config');
angular.module('Base64');

angular.module('AuthManager', [
        'Authentication',
        'Home',
        'Url',
        'Client',
        'Role',
        'User',
        'ngRoute',
        'ngCookies',
        'config'
    ])
    .run(['$rootScope', '$location', '$cookieStore', '$http', 'Base64', 'UrlService', 'SERVICES', 'AuthenticationService',
        function ($rootScope, $location, $cookieStore, $http, Base64, UrlService, SERVICES, AuthenticationService) {
            UrlService.defaults.registredServices = SERVICES;

            AuthenticationService.SessionStart();

            $rootScope.$on('$locationChangeStart', function (event, next, current) {
                // redirect to login page if not logged in
                if ($location.path() !== '/login' && !$rootScope.globals.currentUser) {
                    $location.path('/login');
                }
            });

            $http.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
            $http.defaults.headers.put['Content-Type'] = 'application/x-www-form-urlencoded';
            $http.defaults.transformRequest = function(obj) {
                return decodeURIComponent($.param(UrlService.encodeObject(obj || "")))
            };
        }]);