# Product Recommendation Algorithm Documentation

## Overview
This document explains the product recommendation algorithm visualized in the provided flowchart. The algorithm collects user data and determines which products to display based on a prioritized matching system.

## Categories and Tags

```mermaid
graph LR
    subgraph Age Categories
        Y[Youth<br/>Under 25] --> YT[Tag: youth]
        YA[Young Adult<br/>25-35] --> YAT[Tag: young-adult]
        A[Adult<br/>36-50] --> AT[Tag: adult]
        MA[Mature Adult<br/>51+] --> MAT[Tag: mature-adult]
    end
```

```mermaid
graph LR
    subgraph Gender Categories
        M[Male] --> MT[Tag: Male]
        F[Female] --> FT[Tag: Female]
    end
```

## Process Flow

```mermaid
flowchart TD
    A[Start] --> B[Get User Data]
    B --> C{Is User Logged In?}
    
    C -->|Yes| D[Get User Info]
    D --> D1[Gender]
    D --> D2[Age Category]
    D --> D3[Last 5 Orders Categories]
    
    C -->|No| E[Skip User Data]
    
    D --> F[Priority 1: Perfect Match]
    E --> F
    
    F[Priority 1: Perfect Match] -->|"Check Products"| F1{"Has ALL:
        - Gender Match
        - Age Match
        - Interest Categories"}
    F1 -->|Yes| F2[Add to Display List]
    F1 -->|No| G[Priority 2: User Interests]
    F2 -->|"If < 6 products"| G
    
    G[Priority 2: User Interests] -->|"Check Products"| G1{"Has:
        - Interest Categories Match"}
    G1 -->|Yes| G2[Add to Display List]
    G1 -->|No| H[Priority 3: User Data]
    G2 -->|"If < 6 products"| H
    
    H[Priority 3: User Data] -->|"Check Products"| H1{"Has Either:
        - Gender Match
        - Age Match"}
    H1 -->|Yes| H2[Add to Display List]
    H1 -->|No| I[Priority 4: Latest Products]
    H2 -->|"If < 6 products"| I
    
    I[Priority 4: Latest Products] --> I1[Fill Remaining Slots]
```

## Visual Indicators

```mermaid
graph TD
    subgraph Product Display Styles
        P1[Priority 1] --> G[Gold Border<br/>Perfect Match Badge]
        P2[Priority 2] --> S[Silver Border<br/>Based on Interests Badge]
        P3[Priority 3] --> B[Bronze Border<br/>Recommended Badge]
        P4[Priority 4] --> N[Regular Border<br/>No Badge]
    end
```

### Data Collection
1. The process begins by collecting user data
2. The system determines the user's age category:
   - Youth: Under 25 years old
   - Young Adult: 25-35 years old
   - Adult: 36-50 years old
   - Mature Adult: Over 50 years old
3. The system captures the user's gender
4. The system extracts interest categories from the user's last 5 orders

### Data Processing
1. All demographic data (age category and gender) is combined with the user's interests
2. This combined data is used to determine which products to display

### Product Display Logic
The system follows a priority-based approach for displaying products:

#### Priority 1
Show products that match ALL of the following criteria:
- User's gender
- User's age category
- User's interest categories

#### Priority 2 (if slots available)
Show products that match:
- User's interest categories only

#### Priority 3 (if slots available)
Show products that match EITHER:
- User's gender, OR
- User's age category

#### Priority 4 (if slots available)
Show the latest products added to the catalog

## Implementation Notes
- The algorithm ensures users always see some product recommendations
- The prioritization system attempts to show the most personalized products first
- If highly personalized matches aren't available, the system gradually broadens the matching criteria
- Maximum of 6 products are displayed in total
- Products are never duplicated across priority levels

## Visual Style Guide
```mermaid
graph TD
    subgraph Product Card Styles
        G[Gold Tier] --> GB["• Gold Border (#FFD700)
                                • Perfect Match Badge
                                • Top Priority Display"]
        S[Silver Tier] --> SB["• Silver Border (#C0C0C0)
                                • Interest Match Badge
                                • Second Priority Display"]
        B[Bronze Tier] --> BB["• Bronze Border (#CD7F32)
                                • Basic Match Badge
                                • Third Priority Display"]
        R[Regular] --> RB["• Standard Border (#eee)
                                • No Badge
                                • Fill Remaining Slots"]
    end
```

## Next Steps
Consider implementing A/B testing to determine the effectiveness of each priority level in generating conversions.
