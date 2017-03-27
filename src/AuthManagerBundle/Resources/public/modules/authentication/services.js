'use strict';

angular.module('Base64');
 
angular.module('Authentication')
 
.factory('AuthenticationService',
    ['Base64', '$http', '$cookieStore', '$rootScope', '$location',
    function (Base64, $http, $cookieStore, $rootScope, $location) {
        var service = {};

        service.Login = function (username, password, callback) {
            $http.post('/manager-login', {username: username, password: password})
                .success(function (response){
                    console.log('Success', response);
                    callback(response);
                })
                .error(function (response){
                    console.log('Error');
                    callback(response);
                });
        };
 
        service.SetCredentials = function (data) {
            data.expires_at = new Date().getTime() + data.expires_in * 1000;
            data.created = new Date().getTime();
            var authdata = Base64.encode(JSON.stringify(data));
 
            $rootScope.globals = {
                currentUser: {
                    user: data.user,
                    authdata: authdata
                }
            };
 
            $http.defaults.headers.common['Authorization'] = 'Bearer ' + data.access_token;
            $cookieStore.put('globals', $rootScope.globals);
        };
 
        service.ClearCredentials = function () {
            $rootScope.globals = {};
            $cookieStore.remove('globals');
            $http.defaults.headers.common.Authorization = 'Bearer ';
        };

        /**
         * @returns {boolean}
         * @constructor
         */
        service.IsAuthenticated = function () {
            if ($rootScope.globals.currentUser) {
                var authData = JSON.parse(Base64.decode($rootScope.globals.currentUser.authdata));
                return authData.expires_at > new Date().getTime();
            }

            return false;
        };

        service.SessionStart = function () {
            $rootScope.globals = $cookieStore.get('globals') || {};
            if ($rootScope.globals.currentUser) {
                var authData = JSON.parse(Base64.decode($rootScope.globals.currentUser.authdata));
                $http.defaults.headers.common['Authorization'] = 'Bearer ' + authData.access_token; // jshint ignore:line
            }
        };

        service.RefreshToken = function (callback) {
            if (service.IsAuthenticated()) {
                callback();
            } else {
                var authData = JSON.parse(Base64.decode($rootScope.globals.currentUser.authdata));
                $http.post('/manager-login', {token: authData.refresh_token})
                    .success(function (response){
                        response.user = authData.user;
                        service.SetCredentials(response);
                        callback();
                    })
                    .error(function (){
                        $location.path('/login');
                    });
            }
        };
 
        return service;
    }]);