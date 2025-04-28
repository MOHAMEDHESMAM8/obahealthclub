# WordPress OBA and Laravel MDCLARA Integration Architecture

## Table of Contents
- [System Overview](#system-overview)
- [Single Sign-On (SSO) Implementation](#single-sign-on-sso-implementation)
- [Data Synchronization Architecture](#data-synchronization-architecture)
- [Authentication & Authorization](#authentication--authorization)
- [API Structure and Endpoints](#api-structure-and-endpoints)
- [Data Models and Mapping](#data-models-and-mapping)
- [Error Handling and Logging](#error-handling-and-logging)
- [Security Considerations](#security-considerations)

## System Overview

The integration connects two independent systems:
1. **WordPress OBA**: Front-end patient portal and doctor management system
2. **Laravel MDCLARA**: Back-end medical management system handling appointments, records, and clinical data

The architecture uses REST APIs for data exchange and JWT-based SSO for authentication across platforms.

![System Architecture Diagram]

## Single Sign-On (SSO) Implementation

### SSO Flow

1. **User Authentication Flow**:
   
   ```
   +---------------+          +----------------+         +---------------+
   |   WordPress   |          |    Identity    |         |    Laravel    |
   |      OBA      |--------->|    Provider    |-------->|    MDCLARA    |
   |   (Client)    |          |    (Server)    |         |    (Client)   |
   +---------------+          +----------------+         +---------------+
   ```

2. **Implementation Steps**:

   a. **Initial Setup**:
      - Configure OAuth 2.0 or OpenID Connect between WordPress and Laravel
      - Register both applications with a shared Identity Provider (IdP)
      - Define user attribute mapping between systems

   b. **Authentication Process**:
      1. User logs into WordPress OBA
      2. WordPress requests authentication token from IdP
      3. IdP validates credentials and returns JWT token
      4. WordPress stores token in secure cookie/session
      5. When accessing MDCLARA resources, token is passed in request header
      6. MDCLARA validates token signature and grants access

### JWT Token Structure

```json
{
  "header": {
    "alg": "RS256",
    "typ": "JWT"
  },
  "payload": {
    "sub": "user123",
    "name": "John Doe",
    "email": "john.doe@example.com",
    "roles": ["patient"],
    "oba_id": 456,
    "mdclara_id": 789,
    "iat": 1619884091,
    "exp": 1619887691,
    "iss": "https://idp.example.com",
    "aud": ["wordpress-oba", "laravel-mdclara"]
  },
  "signature": "..."
}
```

### Implementation Code (Example)

**WordPress OBA (OAuth Client)**:
```php
<?php
// Install and use oauth2-client library

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => 'wordpress-oba',
    'clientSecret'            => 'your-client-secret',
    'redirectUri'             => 'https://oba.example.com/callback',
    'urlAuthorize'            => 'https://idp.example.com/oauth2/authorize',
    'urlAccessToken'          => 'https://idp.example.com/oauth2/token',
    'urlResourceOwnerDetails' => 'https://idp.example.com/oauth2/userinfo'
]);

// Store token in user session
$_SESSION['oauth_token'] = $token->getToken();
```

**Laravel MDCLARA (Token Validation)**:
```php
<?php
// In middleware/AuthenticateSSO.php

use Firebase\JWT\JWT;

public function handle($request, Closure $next)
{
    $token = $request->bearerToken();
    
    try {
        $decoded = JWT::decode($token, $this->publicKey, ['RS256']);
        
        // Find or create local user based on token data
        $user = User::firstOrCreate(
            ['email' => $decoded->email],
            [
                'name' => $decoded->name,
                'oba_id' => $decoded->oba_id,
                // other user attributes
            ]
        );
        
        Auth::login($user);
        return $next($request);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
```

## Data Synchronization Architecture

### Synchronization Methods

1. **Real-time API Communication**:
   - Direct API calls between systems when data changes
   - Used for time-sensitive data (appointments, patient registration)

2. **Scheduled Batch Synchronization**:
   - Cron jobs for regular data alignment
   - Used for non-critical updates (doctor profiles, medical history)

3. **Event-Driven Updates**:
   - Webhook notifications for important events
   - Queue-based processing for reliability

### Synchronization Flow

```
                       +----------------------+
                       |  Synchronization     |
                       |     Controller       |
                       +-----------+----------+
                                   |
           +------------------------+------------------------+
           |                        |                        |
+----------v----------+  +---------v----------+  +----------v---------+
|    Real-time API    |  |   Scheduled Batch   |  |    Event-Driven    |
|    Communication    |  |   Synchronization   |  |       Updates      |
+---------------------+  +--------------------+  +--------------------+
```

### Data Consistency Strategies

1. **Conflict Resolution**:
   - Last-write-wins strategy for most data
   - Merge strategy for complex objects
   - Conflict flagging for manual resolution when needed

2. **Transaction Management**:
   - Distributed transactions for critical operations
   - Compensation transactions for rollback in case of failures

3. **ID Mapping**:
   - Maintain mapping tables between OBA and MDCLARA IDs:

```
+--------------+-------------+---------------+------------+
| oba_user_id  | mdclara_id  | entity_type   | last_sync  |
+--------------+-------------+---------------+------------+
| 123          | 456         | doctor        | 2025-04-25 |
| 789          | 101         | patient       | 2025-04-28 |
+--------------+-------------+---------------+------------+
```

## Authentication & Authorization

### Authentication Methods

1. **API Authentication**:
   - JWT tokens for authenticated requests
   - Token lifetime: 60 minutes
   - Refresh token lifetime: 14 days

2. **API Keys**:
   - System-to-system communication
   - Rotating keys (90-day rotation)

### Authorization Model

1. **Role-Based Access Control**:
   - Roles synchronized between systems
   - Common roles: `patient`, `doctor`, `admin`

2. **Permission Mapping**:

```
+-------------------+---------------------------+---------------------------+
| Role              | WordPress OBA Permissions | Laravel MDCLARA Actions   |
+-------------------+---------------------------+---------------------------+
| patient           | view_own_appointments     | GET /api/patients/{id}/*  |
|                   | book_appointment          | POST /api/appointments    |
+-------------------+---------------------------+---------------------------+
| doctor            | view_own_schedule         | GET /api/doctors/{id}/*   |
|                   | update_availability       | PUT /api/availability     |
+-------------------+---------------------------+---------------------------+
| admin             | manage_all_users          | Full access               |
+-------------------+---------------------------+---------------------------+
```

## API Structure and Endpoints

### Base URLs
- WordPress OBA API: `https://oba.example.com/wp-json/oba/v1`
- Laravel MDCLARA API: `https://mdclara.example.com/api/v1`

### Core Endpoints

**1. Doctor Management**:

| Operation | OBA Endpoint | MDCLARA Endpoint | Data Direction |
|-----------|-------------|-----------------|----------------|
| List      | GET `/doctors` | GET `/doctors` | OBA ← MDCLARA |
| Create    | POST `/doctors` | POST `/doctors` | OBA → MDCLARA |
| Update    | PUT `/doctors/{id}` | PUT `/doctors/{id}` | OBA → MDCLARA |
| Approve   | POST `/doctors/{id}/approve` | POST `/doctors/{id}/activate` | OBA → MDCLARA |

**2. Patient Management**:

| Operation | OBA Endpoint | MDCLARA Endpoint | Data Direction |
|-----------|-------------|-----------------|----------------|
| Register  | POST `/patients` | POST `/patients` | OBA → MDCLARA |
| Update    | PUT `/patients/{id}` | PUT `/patients/{id}` | OBA → MDCLARA |
| Get       | GET `/patients/{id}` | GET `/patients/{id}` | OBA ← MDCLARA |

**3. Appointment Management**:

| Operation | OBA Endpoint | MDCLARA Endpoint | Data Direction |
|-----------|-------------|-----------------|----------------|
| Book      | POST `/appointments` | POST `/appointments` | OBA → MDCLARA |
| Cancel    | DELETE `/appointments/{id}` | PUT `/appointments/{id}/cancel` | OBA → MDCLARA |
| List      | GET `/users/{id}/appointments` | GET `/patients/{id}/appointments` | OBA ← MDCLARA |

### Webhook Integration Points

**1. From MDCLARA to OBA**:
- Appointment status changes
- Medical record updates
- Doctor availability changes

**2. From OBA to MDCLARA**:
- New user registration
- Profile updates
- Payment confirmations

## Data Models and Mapping

### Core Entity Mapping

**1. User/Patient Entity**:

| WordPress OBA Field | Laravel MDCLARA Field | Data Type | Sync Direction |
|---------------------|----------------------|-----------|----------------|
| wp_user_id          | oba_user_id          | integer   | OBA → MDCLARA |
| email               | email                | string    | Two-way       |
| first_name          | first_name           | string    | Two-way       |
| last_name           | last_name            | string    | Two-way       |
| phone               | phone_number         | string    | Two-way       |
| address             | address              | object    | OBA → MDCLARA |
| dob                 | date_of_birth        | date      | OBA → MDCLARA |
| insurance_info      | insurance            | object    | OBA → MDCLARA |

**2. Doctor Entity**:

| WordPress OBA Field | Laravel MDCLARA Field | Data Type | Sync Direction |
|---------------------|----------------------|-----------|----------------|
| doctor_id           | oba_doctor_id        | integer   | OBA → MDCLARA |
| name                | full_name            | string    | Two-way       |
| specialization      | specialty            | string    | Two-way       |
| bio                 | biography            | text      | OBA → MDCLARA |
| photo_url           | profile_image        | string    | OBA → MDCLARA |
| qualifications      | credentials          | array     | OBA → MDCLARA |
| schedule            | availability         | object    | Two-way       |

**3. Appointment Entity**:

| WordPress OBA Field | Laravel MDCLARA Field | Data Type | Sync Direction |
|---------------------|----------------------|-----------|----------------|
| appointment_id      | oba_appointment_id   | integer   | OBA → MDCLARA |
| patient_id          | patient_id           | integer   | OBA → MDCLARA |
| doctor_id           | doctor_id            | integer   | OBA → MDCLARA |
| date                | appointment_date     | date      | OBA → MDCLARA |
| time                | appointment_time     | time      | OBA → MDCLARA |
| status              | status               | string    | Two-way       |
| notes               | patient_notes        | text      | OBA → MDCLARA |
| n/a                 | medical_notes        | text      | MDCLARA → OBA |
| created_at          | created_at           | datetime  | OBA → MDCLARA |

### Data Transformation Examples

**1. Creating a Patient in MDCLARA from OBA**:

```php
// WordPress OBA data
$obaPatient = [
    'ID' => 123,
    'user_email' => 'patient@example.com',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'meta' => [
        'phone' => '555-123-4567',
        'dob' => '1985-05-15',
        'address' => '123 Main St, City',
        'insurance_provider' => 'BlueCross',
        'insurance_number' => 'BC12345678'
    ]
];

// Transform to MDCLARA format
$mdclaraPatient = [
    'oba_user_id' => $obaPatient['ID'],
    'email' => $obaPatient['user_email'],
    'first_name' => $obaPatient['first_name'],
    'last_name' => $obaPatient['last_name'],
    'phone_number' => $obaPatient['meta']['phone'],
    'date_of_birth' => $obaPatient['meta']['dob'],
    'address' => [
        'full_address' => $obaPatient['meta']['address']
    ],
    'insurance' => [
        'provider' => $obaPatient['meta']['insurance_provider'],
        'policy_number' => $obaPatient['meta']['insurance_number']
    ]
];
```

## Error Handling and Logging

### Error Handling Strategy

1. **Error Types**:
   - Authentication errors (401, 403)
   - Data validation errors (400)
   - System errors (500)
   - Synchronization errors (custom)

2. **Error Response Format**:
```json
{
  "status": "error",
  "code": "SYNC_CONFLICT",
  "message": "Data synchronization conflict detected",
  "details": {
    "entity": "patient",
    "id": 123,
    "fields": ["phone_number", "address"],
    "timestamp": "2025-04-25T14:32:10Z"
  },
  "request_id": "f7cdf6b8-1b1a-4b1d-8b9c-7d4e5f3a2b1c"
}
```

3. **Retry Mechanisms**:
   - Exponential backoff for transient errors
   - Dead letter queue for failed synchronizations
   - Manual resolution interface for conflicts

### Logging Architecture

1. **Log Categories**:
   - Authentication logs
   - Data synchronization logs
   - Error logs
   - Audit logs

2. **Log Format**:
```json
{
  "timestamp": "2025-04-25T14:32:10.123Z",
  "level": "info",
  "service": "sync-service",
  "event": "doctor_created",
  "source_system": "wordpress-oba",
  "target_system": "laravel-mdclara",
  "entity_type": "doctor",
  "entity_id": {
    "oba_id": 123,
    "mdclara_id": 456
  },
  "user_id": "admin@example.com",
  "details": {},
  "request_id": "f7cdf6b8-1b1a-4b1d-8b9c-7d4e5f3a2b1c"
}
```

3. **Monitoring**:
   - Real-time alert thresholds for critical errors
   - Dashboard for synchronization health
   - Performance metrics tracking

## Security Considerations

1. **Data Protection**:
   - All API communications over TLS 1.3
   - PII encrypted at rest and in transit
   - Health data handled according to HIPAA requirements

2. **API Security**:
   - Rate limiting to prevent abuse
   - OWASP top 10 protections implemented
   - Regular security scanning

3. **Authorization**:
   - Fine-grained permissions
   - Claims-based authorization
   - Principle of least privilege enforced

4. **Audit Trail**:
   - All data modifications logged
   - User actions tracked
   - Immutable audit history

### Implementation Checklist

- [ ] Configure identity provider for SSO
- [ ] Implement JWT token handling in both systems
- [ ] Create database mapping tables for entity relationships
- [ ] Implement real-time synchronization endpoints
- [ ] Set up batch synchronization jobs
- [ ] Configure webhook handlers
- [ ] Establish monitoring and alerting
- [ ] Implement error handling and retry mechanisms
- [ ] Conduct security review and penetration testing
- [ ] Create documentation for API consumers