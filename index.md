# OBA-MDCLARA System Integration

%%{init: {'theme': 'dark', 'themeVariables': { 'darkMode': true }}}%%

## 1. SSO Concept
### a. Doctors

```mermaid
%%{init: {'theme': 'dark'}}%%
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
%%{init: {'theme': 'dark'}}%%
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
%%{init: {'theme': 'dark'}}%%
graph TD
    subgraph "Doctor Profile Page"
        A[Doctor Information] -->|From OBA| B[Doctor Profile]
        C[Schedule] -->|From MDCLARA| B
    end
    style A fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style B fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style C fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
```

## 2. Patients Integration

### Patient Appointment Flow:

```mermaid
%%{init: {'theme': 'dark'}}%%
flowchart TD
    A[Patient] -->|View| B[Doctor Profile]
    B -->|Select| C[Available Time Slot]
    C -->|Reserve| D[Create Appointment]
    D -->|Store in| E[MDCLARA Database]
    E -->|Sync to| F[OBA My Account]
    style A fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style B fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style C fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style D fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style E fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style F fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
```

### Patient profile:

```mermaid
%%{init: {'theme': 'dark'}}%%
graph TD
    subgraph "DATA"
        A[My Appointments] -->|From MDCLARA| E[Patient Profile]
        D[Patient Information] -->|From OBA| E[Patient Profile]
    end
    style A fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style D fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
    style E fill:#2A2A2A,stroke:#666,stroke-width:2px,color:#fff
```
