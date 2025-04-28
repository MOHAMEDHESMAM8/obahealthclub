# OBA-MDCLARA System Integration

## 0. SSO Concept

```mermaid
sequenceDiagram
    actor User
    participant OBA as OBA (WordPress)
    participant MDCLARA as MDCLARA (Laravel)
    
    User->>OBA: Login to OBA
    OBA->>OBA: Authenticate User
    User->>OBA: Access MDCLARA Dashboard
    OBA->>MDCLARA: Send Authentication Token
    Note over OBA,MDCLARA: JWT or OAuth
    MDCLARA->>MDCLARA: Validate Token
    MDCLARA->>User: Access Granted (No Login Required)
```

### Approach Options:

**JWT-Based SSO:**
```mermaid
flowchart LR
    A[OBA] -->|Issue JWT| B[Token]
    B -->|Send with Request| C[MDCLARA]
    C -->|Validate Token| D[Grant Access]
```

**OAuth-Based SSO:**
```mermaid
flowchart LR
    A[OBA] -->|OAuth Authorization| B[Authorization Code]
    B -->|Exchange for Token| C[Access Token]
    C -->|Use Token| D[MDCLARA API]
```

## 1. Doctors Integration

```mermaid
sequenceDiagram
    participant OBA as OBA (WordPress)
    participant MDCLARA as MDCLARA (Laravel)
    
    OBA->>OBA: Doctor Approval Process
    OBA->>MDCLARA: API Request (Create Doctor)
    Note over OBA,MDCLARA: Doctor Data Sync
    MDCLARA->>MDCLARA: Create Doctor Account
    MDCLARA->>OBA: Confirmation Response
```

### Doctor Profile Page:

```mermaid
graph TD
    subgraph "Doctor Profile Page"
        A[Doctor Information] -->|From OBA| B[Doctor Profile]
        C[Available Appointments] -->|From MDCLARA| B
    end
```

## 2. Patients Integration

```mermaid
sequenceDiagram
    participant OBA as OBA (WordPress)
    participant MDCLARA as MDCLARA (Laravel)
    
    OBA->>OBA: Patient Registration
    OBA->>MDCLARA: Create Patient Request
    MDCLARA->>MDCLARA: Create Patient Account
    MDCLARA->>OBA: Confirmation Response
```

### Patient Appointment Flow:

```mermaid
flowchart TD
    A[Patient] -->|View| B[Doctor Profile]
    B -->|Select| C[Available Time Slot]
    C -->|Reserve| D[Create Appointment]
    D -->|Store in| E[MDCLARA Database]
    E -->|Sync to| F[OBA My Account]
```

### Patient Dashboard:

```mermaid
graph TD
    subgraph "Patient Dashboard"
        A[My Appointments] -->|From MDCLARA| B[Appointments List]
        C[Doctor Feedback] -->|From MDCLARA| B
        D[Patient Information] -->|From OBA| E[Patient Profile]
    end
```

## Data Flow Architecture

```mermaid
flowchart LR
    subgraph "OBA (WordPress)"
        A[User Authentication]
        B[Doctor Management]
        C[Patient Profiles]
    end
    
    subgraph "MDCLARA (Laravel)"
        D[Appointment System]
        E[Doctor Schedules]
        F[Medical Records]
    end
    
    A -->|SSO| D
    B <-->|Two-way Sync| E
    C <-->|Two-way Sync| F
```