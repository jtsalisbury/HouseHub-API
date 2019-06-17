# HouseHub-API

In development API for HouseHub.

1. [Creating Tokens](#creating-tokens)
2. [Verifying Tokens](#verifying-tokens)
3. [Sending Request Tokens](#sending-requests)
4. [Parse Responses](#parse-responses)

5. [User Registration API](#user-registration)

## Creating Tokens
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

## Verifying Tokens
See the section before this for the general structure of the token.  
To verify a token is valid, separate parts 1 and 2 (the header and the payload) from the pre-existing signature (part 3).  
Then, decode the existing signature and concatenate part1 and part2 (with a period delimiter) and verify the pre-existing hash equals the new hash.  
If they do, then we are golden! If not, something has changed and the token should be discarded.

## Sending Requests
When making requests to the API, send a POST request with a string encoded JSON object.  
The JSON object should contain one field "token" (see the following structure).
```
{
    "token": "my-JWT-created-token"
}
```

## Parse Responses
Responses will be sent in the following format.
```
{
    "status": "",
    "message": ""
}
```

The status will be either "error" or "success".

```status = error```: the message will be a human readable error message. See each API for specific messages to handle.

```status = sucess```: the message will be a JWT with the response fields (see each API for specific fields provided) encrypted in the payload.  

To parse the payload:  
First, ensure the token is valid (see above).  
Next, separate the header, payload and signature. Then, decrypt the payload using the encryption algorithm and secret.  
Finally, base 64 url decode the the decrypted string. This will be a string encoded JSON object. To get the JSON object, simply decode it.
  
## User Registration
**Request**  
Send POST requests to: http://u747950311.hostingerapp.com/househub/api/user/create.php

**Request Fields**  
The following fields are required in the payload when making a request to create a user.
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
The following response will be provided.

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
