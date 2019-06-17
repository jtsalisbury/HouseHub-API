# HouseHub-API

In development API for HouseHub.

1. [Creating Tokens](#create-requests)
2. Verify Request Tokens
3. Sending Request Tokens
4. Parse Response Tokens

5. [User Registration API](#user-registration)

## Create Tokens
The tokens follow a simple format. Each part is separated by a period.
part1.part2.part3

**part1** The header for the token.  
This contains a JSON string which is base 64 url encoded.  
The typical structure for this should be
```
{
    "typ": "JWT",
    "alg": "my-hashing-algorithm",
}
```
  
**part2** The payload for the token.  
This contains a string representation of a JSON object with the required fields for that specific API.  
This should be encoded as a JSON string, then base 64 url encoded, then encrypted using a specific algorithm and secret.  
  
**part3** The signature for the token.
This contains no information in particular, but rather a verification that the token is valid.  
This is a concatentation of part1 to part2 with a period, which is then hashed using the algorithm specified in part1 and a key. This is then base 64 url encoded.
  
## User Registration
**Request**  
Send POST requests to: http://u747950311.hostingerapp.com/househub/api/user/create.php

The requests should be sent as JSON Web Tokens, with payloads base64 url encoded and then encrypted.
Responses to successfull queries will send JSON Web Tokens as messages, with payloads base64 url encoded and then encrypted.
The fields below should be base64url encoded and then encrypted as a payload for a JSON Web Token (JWT).

The JWT should be passed as ```token```

**Request Fields**  
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

**Response**  
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
