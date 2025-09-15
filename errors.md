## Task
Fix the following errors

1. http://localhost/GSCMS/public/scoreboard : 
```
Fatal error: Access level to App\Controllers\ScoreboardController::isAdmin() must be protected (as in class App\Controllers\BaseController) or weaker in /var/www/html/GSCMS/app/Controllers/ScoreboardController.php on line 251
ErrorException

HTTP 500 Error
Access level to App\Controllers\ScoreboardController::isAdmin() must be protected (as in class App\Controllers\BaseController) or weaker

File:
    /var/www/html/GSCMS/app/Controllers/ScoreboardController.php
Line:
    251

Stack Trace:

#0 [internal function]: App\Core\ErrorHandler->handleShutdown()
#1 {main}

Request Information:
URL	/GSCMS/public/scoreboard
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```
2. http://localhost/GSCMS/public/admin/rubrics :
```
Issue - 
> Redirects to http://localhost/GSCMS/public/dashboard
Even though the user is logged as admin, withe the following credentials user: admin password: admin123!@#
```
3. http://localhost/GSCMS/public/admin/live-scoring
```
> Redirects to http://localhost/GSCMS/public/dashboard
Even though the user is logged as admin, withe the following credentials user: admin password: admin123!@#
```
4. http://localhost/GSCMS/public/admin/live-scoring/websocket
```
> Redirects to http://localhost/GSCMS/public/dashboard
Even though the user is logged as admin, withe the following credentials user: admin password: admin123!@#
```

5. http://localhost/GSCMS/judging/dashboard
```

Exception

HTTP 404 Error
Route not found: GET /judging/dashboard

File:
    /var/www/html/GSCMS/app/Core/Router.php
Line:
    119

Stack Trace:

#0 /var/www/html/GSCMS/public/index.php(53): App\Core\Router->dispatch()
#1 {main}

Request Information:
URL	/GSCMS/judging/dashboard
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```
8. http://localhost/GSCMS/public/judging :
```

Exception

HTTP 404 Error
Route not found: GET /judging

File:
    /var/www/html/GSCMS/app/Core/Router.php
Line:
    119

Stack Trace:

#0 /var/www/html/GSCMS/public/index.php(53): App\Core\Router->dispatch()
#1 {main}

Request Information:
URL	/GSCMS/public/judging
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```
9. http://localhost/GSCMS/public/scorecards :
```

Exception

HTTP 404 Error
Route not found: GET /scorecards

File:
    /var/www/html/GSCMS/app/Core/Router.php
Line:
    119

Stack Trace:

#0 /var/www/html/GSCMS/public/index.php(53): App\Core\Router->dispatch()
#1 {main}

Request Information:
URL	/GSCMS/public/scorecards
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```
10. http://localhost/GSCMS/teams/manage:
```
Exception

HTTP 404 Error
Route not found: GET /teams/manage

File:
    /var/www/html/GSCMS/app/Core/Router.php
Line:
    119

Stack Trace:

#0 /var/www/html/GSCMS/public/index.php(53): App\Core\Router->dispatch()
#1 {main}

Request Information:
URL	/GSCMS/teams/manage
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```
11. http://localhost/GSCMS/public/team-management :
```

Exception

HTTP 404 Error
Route not found: GET /team-management

File:
    /var/www/html/GSCMS/app/Core/Router.php
Line:
    119

Stack Trace:

#0 /var/www/html/GSCMS/public/index.php(53): App\Core\Router->dispatch()
#1 {main}

Request Information:
URL	/GSCMS/public/team-management
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```
12. http://localhost/GSCMS/public/school-management :
```

Exception

HTTP 404 Error
Route not found: GET /school-management

File:
    /var/www/html/GSCMS/app/Core/Router.php
Line:
    119

Stack Trace:

#0 /var/www/html/GSCMS/public/index.php(53): App\Core\Router->dispatch()
#1 {main}

Request Information:
URL	/GSCMS/public/school-management
Method	GET
IP Address	::1
User Agent	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0
```


