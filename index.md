---
layout: default
title: OBA-MDCLARA System Integration
mermaid: true
---

# OBA-MDCLARA System Integration

## 1. SSO Concept
### a. Doctors

```mermaid
sequenceDiagram
    actor Doctor
    participant OBA as OBA (WordPress)
    participant MDCLARA as MDCLARA (Laravel)
    Doctor->>OBA: Register/Login
    OBA->>OBA: Verify Doctor Credentials
    OBA->>OBA: Doctor Approval Process
    Doctor->>OBA: Access Dashboard
    OBA->>MDCLARA: POST /api/doctors
    Note over OBA,MDCLARA: Send Complete Doctor Data
    MDCLARA->>MDCLARA: Create Doctor Account or Verify Doctor Status 
    MDCLARA->>OBA: Return Reference ID & JWT
    OBA->>OBA: Store Reference ID
    OBA->>Doctor: Show MDCLARA Dashboard
```

### Doctor Data:

```json
{
    "reference_id": "DOC123456",
    "first_name": "mohammad",
    "last_name": "hisham",
    "email": "m.hisham@iislb.com",
    "date_of_birth": "04/04/2025",
    "gender": "Male",
    "speciality": "dr",
    "biography": "",
    "phone_type": "Home",
    "npi": "qwq",
    "dea": "qw",
    "license": "qwq",
    "dea_expiry_date": "mm/dd/yyyy",
    "license_expiry_date": "mm/dd/yyyy",
    "degree": "qw",
    "licensed_states": "mm/dd/yyyy"
}
``` 
## b. Patients  

```mermaid
sequenceDiagram
    actor Patient
    participant OBA as OBA (WordPress)
    participant MDCLARA as MDCLARA (Laravel)
    Patient->>OBA: Register/Login
    OBA->>MDCLARA: POST /api/patients
    Note over OBA,MDCLARA: Send patient Data
    MDCLARA->>MDCLARA: Create Patient Account or Verify Patient Status 
    MDCLARA->>OBA: Return Reference ID & JWT
    OBA->>OBA: Store Reference ID
    OBA->>Patient: Home

```
### Patient Data:

```json
{
    "reference_id": "DOC123456",
    "first_name": "mohammad",
    "last_name": "hisham",
    "email": "m.hisham@iislb.com",
    "date_of_birth": "04/04/2025",
    "gender": "Male",
    "Phone  ": "1212",
    "Country ": "EG",
}
```




## 2. Doctor:
The medical experts page retrieves doctor information from the OBA (WordPress) system.

### Doctor Profile Page:


```mermaid
graph TD
    subgraph "Doctor Profile Page"
        A[Doctor Information] -->|From OBA| B[Doctor Profile]
        C[Schedule] -->|From MDCLARA| B
    end
```

## 2. Patients Integration


### Patient Appointment Flow:

```mermaid
flowchart TD
    A[Patient] -->|View| B[Doctor Profile]
    B -->|Select| C[Available Time Slot]
    C -->|Reserve| D[Create Appointment]
    D -->|Store in| E[MDCLARA Database]
    E -->|Sync to| F[OBA My Account]
```

### Patient profile:

```mermaid
graph TD
    subgraph "DATA"
        A[My Appointments] -->|From MDCLARA| E[Patient Profile]
        D[Patient Information] -->|From OBA| E[Patient Profile]
    end
```
