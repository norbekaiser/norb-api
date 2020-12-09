# Endpoint: {api_url}/me/ldap/
This Endpoint is Responsible for returning Additional User Data from LDAP

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

#### Example

```http request
Authorization: 1234567890
```

### Results

#### Successful Request
It will return the Distinguished Name of the User

```http request
'HTTP/1.1 200 OK'
```
```json
{
    "dn": "cn=dave,ou=users,dc=ldap,dc=example,dc=com",
    "member_since": "2020-02-02 02:02:02"
}
```

#### Faulty Authorization
If the User does not have a Valid Session or if he is not a LDAP User, the Request is unauthorized
```http request
'HTTP/1.1 401 Unauthorized'
```
```json
```

## PATCH

### Parameters

#### Required HTTP Headers

```http request
Authorization: session_id
```

#### JSON Payload

| Parameter | Type | Description |
| :--- |:--- | :--- |
| password | (Optional) String | The Desired new Password |
| email | (Optional) String | The Desired new Email Address |

#### Example

```http request
Authorization: 1234567890
```

### Results

#### On Success
It will return if the value was successfully changed

```http request
'HTTP/1.1 200 OK'
```
```json
{
    "password": "modified",
    "email": "modified"
}
```

#### Faulty Authorization
If the User does not have a Valid Session or if he is not a LDAP User, the Request is unauthorized
```http request
'HTTP/1.1 401 Unauthorized'
```
```json
```

#### Faulty Input Data

```http request
'HTTP/1.1 400 Bad Request'
```
```json
```
