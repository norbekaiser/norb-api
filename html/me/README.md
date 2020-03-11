# Endpoint: {api_url}/me/
This Endpoint is Responsible for returning current User Data

# HTTP Requests
The Following HTTP Requests are Possible
___
## GET

### Parameters

#### Required HTTP Headers

```http request
Authorization: session_id
```

#### JSON Payload
* none

### Example

```http request
Authorization: 1234567890
```

### On Success
It will return the userdata, depending on the origin more ore less might be returned

#### Any User

```http request
'HTTP/1.1 200 OK'
```
```json
{
    "usr_id": 1,
    "member_since": "2020-02-02 02:02:02"
}
```

#### Local User
 
```http request
'HTTP/1.1 200 OK'
```
```json
{
    "usr_id": 1,
    "username": "user",
    "member_since": "2020-02-02 02:02:02"
}
```

#### LDAP Posix User

```http request
'HTTP/1.1 200 OK'
```
```json
{
    "usr_id": 1,
    "username": "cn=dave,ou=users,dc=ldap,dc=example,dc=com",
    "member_since": "2020-02-02 02:02:02",
    "cn": "user",
    "uid": "user",
    "uidNumber": "1000",
    "gidNumber": "1000",
    "homeDirectory": "/home/users/user",
    "loginShell": "/bin/bash"
}
```

### On Failure
 
```http request
'HTTP/1.1 401 Unauthorized'
```
```json
```
