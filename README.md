# HouseHub-API

In development API for HouseHub.

1. [Creating Tokens](#creating-tokens)
2. [Verifying Tokens](#verifying-tokens)
3. [Sending Request Tokens](#sending-requests)
4. [Parse Responses](#parse-responses)
5. [User Registration](#user-registration)
5. [User Login](#user-login)
6. [User Information Updating](#user-update)

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
    "fname":"first name of user",
    "lname":"last name of user",
    "email":"email of user",
    "pass":"password of user (unencrypted)",
    "repass":"password re-entered of user (unencrypted)"
}
```

**Response Errors**  
```status = "error"```
message will equal one of the following
- "fields_not_set", one or more of the required payload fields was not set 
- "password_not_equal", the passwords do not match
- "database_not_connected", internal issue with DB connection
- "failed_insert_user", general error for failing to insert a user
- "failed_insert_user_exists", user already exists
- "invalid_request_token", the token couldn't be validated.

**Response Fields**
```status = "success"```
message will be a JWT with a payload of the following fields.

```
{
    "fname": "provided user's first name",
    "lname": "provided user's last name",
    "email": "provided user's email",
    "uid":   "newly inserted user's ID number"
}
```

## User Login
**Request**  
Send POST requests to: http://u747950311.hostingerapp.com/househub/api/user/login.php

**Request Fields**  
The following fields are required in the payload when making a request to login a user.
```
{
    "email":"email of user",
    "pass":"password of user (unencrypted)",
}
```

**Response Errors**  
```status = "error"```
message will equal one of the following
- "fields_not_set", one or more of the required payload fields was not set 
- "password_not_equal", the passwords do not match
- "database_not_connected", internal issue with DB connection
- "user_does_not_exist", a user with that email couldn't be found
- "invalid_request_token", the token couldn't be validated

**Response Fields**
```status = "success"```
message will be a JWT with a payload of the following fields.

```
{
    "fname": "user's first name",
    "lname": "user's last name",
    "email": "user's email",
    "admin": 0 or 1 (0 = not admin, 1 = admin),
    "created": datetime user registered
    "uid":   "user's ID number"
}
```

## User Update
**Request**  
Send POST requests to: http://u747950311.hostingerapp.com/househub/api/user/update.php


**Request Fields**  
The following fields are required in the payload when making a request to update a user.
```
{
    "uid": acting user's id,
    "pass": "current user's pass",
    "fields": { // all fields below are optional (at least one must be specified)
        "lname": "new last name",
        "fname": "new first name",
        "email": "new email",
        "pass": "new pass", // if you send pass or repass, the other is REQUIRED
        "repass": "new pass re-entered"
    }
}
```

**Response Errors**  
```status = "error"```
message will equal one of the following
- "fields_not_set", one or more of the required payload fields was not set 
- "password_not_equal", the current passwords do not match
- "database_not_connected", internal issue with DB connection
- "user_does_not_exist", a user with that email couldn't be found
- "invalid_request_token", the token couldn't be validated
- "update_user_new_pass_not_equal", the password to be changed is not equal or both pass and repass weren't


**Response Fields**
```status = "success"```
message will be a JWT with a payload of the following fields.

```
{
    "fname": "user's new first name",
    "lname": "user's new last name",
    "email": "user's new email",
    "uid":   "user's ID number"
}
```

## Get Listing Info
**Request**  
Send POST requests to: http://u747950311.hostingerapp.com/househub/api/listings/retrieve.php


**Request Fields**  
The following fields are all option in the payload when making a request to get listing info.
```
{
    "pid": "used to get information for one specific listing",
    "uid": "used to get all listings which a user has created",
    "saved": "this can be set to any value; when set uid is requried. This will get all the listings a user has saved",
    "page": "if you get the first set of listings and there are more than 1 page, you can use this to get the next set, etc."
    "search_criteria": "will return any listings with text matching based on title, description, location",
    "price_min": "will return any listings with base price greater than or equal to this",
    "price_max": "will return any listings with base price less than or equal to this"
}
```


**Response Errors**  
```status = "error"```
message will equal one of the following
- "fields_not_set", request for saved listings without a user id


**Response Fields**
```status = "success"```
message will be a JWT with a payload of the following fields.

```
{
    "page": the current set (page) of listings which is returned,
    "total_pages": the number of pages based on the total listings matching the criteria and the maximum to be returned at once
    "listing_count": the the first set of listings found with the parameters passed,
    "max_listing_count": the total number of possible listings that could possibly be found,
    "listings": [
        {
            "pid": "Listing id number",
            "title": "Listing title",
            "desc": "Listing description",
            "loc": "Listing address",
            "base_price": "Listings base price",
            "add_price": "Listing additional price",
            "creator_uid": "Listing creator",
            "num_pictures": "Number of pictures associated with this listing"
        },
        .
        .
        .
    ]
}
```
