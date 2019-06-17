# HouseHub-API

In development API for HouseHub.

## Create API
** Request **
Send POST requests to: http://u747950311.hostingerapp.com/househub/api/user/create.php

The requests should be sent as JSON Web Tokens, with payloads base64 url encoded and then encrypted.
Responses to successfull queries will send JSON Web Tokens as messages, with payloads base64 url encoded and then encrypted.
The fields below should be base64url encoded and then encrypted as a payload for a JSON Web Token (JWT).

The JWT should be passed as ```token```

** Request Fields **
The following fields are required when making a request to create a user.
```
{
    "fname":"",
    "lname":"",
    "email":"",
    "pass":"",
    "repass":""
}
```

** Response **
The following response will be provided as a JSON string.

```
{
    "status": "",
    "message": ""
}
```

You should first check the content of status for whether it is "success" or "error".

```status = "error"```
message will equal one of the following
- "invalid_request_token", token request was invalid
- "fields_not_set", one or more of the required payload fields was not set 
- "password_not_equal", the passwords do not match
- "database_not_connected", internal issue with DB connection
- "failed_insert_user", general error for failing to insert a user
- "failed_insert_user_exists", user already exists

```status = "success"```
message will equal a JWT with an encrypted payload equal to the following

```
{
    "fname": "provided user's first name",
    "lname": "provided user's last name",
    "email": "provided user's email",
    "uid":   "newly inserted user's ID number"
    
}
```
