# Software Requirements Specification for Waste Optimization Management Portal

## 1. Introduction

### 1.1 Purpose of the Document
This Software Requirements Specification (SRS) document details the functional and non-functional requirements for an online management portal designed for a waste optimization company. The primary goal of this portal is to streamline the process of waste collection, sorting, and reporting, providing transparency and efficiency for both the waste optimization company and its clients. This document serves as a foundational agreement between the development team and the client, ensuring a shared understanding of the system to be developed before any quotation or development work commences. It will be used to guide the design, development, and testing phases of the project.

### 1.2 Scope of the System
The online management portal will provide a centralized platform for managing waste collection orders, tracking the sorting of various waste types, generating comprehensive reports, and offering clients secure access to their specific data and reports. The system will support the input of waste collection data, including actual quantities collected versus ordered, and detailed breakdowns of sorted waste by type and weight. It will also facilitate the assignment of commodity values to certain waste types and enable the generation of various reports for internal use and client viewing. The system will cater to a diverse client base, ranging from small, single-site operations to large enterprises with multiple companies, branches, and sites.

### 1.3 Target Audience
The primary audience for this document includes:
*   **Client Stakeholders:** Individuals from the waste optimization company responsible for overseeing the project, providing business requirements, and making key decisions.
*   **Development Team:** Software engineers, designers, and quality assurance personnel responsible for building and testing the system.
*   **Project Managers:** Individuals responsible for planning, executing, and closing the project.
*   **End-Users:** Internal staff (data captures, super admins) and external clients (client administrators, site managers) who will interact with the portal.

### 1.4 Definitions and Acronyms
*   **SRS:** Software Requirements Specification
*   **Portal:** The online management system being developed.
*   **Client:** The waste optimization company commissioning the software.
*   **End-Client:** The businesses that use the waste optimization company's services (e.g., Woolworths).
*   **Waste Order:** An order placed by the client for the collection of general waste, where specific categories are not yet known.
*   **Recycling Order:** An order placed by the client for the collection of specific, pre-sorted waste types (e.g., aluminum, paper).
*   **Service Provider:** The external entity responsible for waste collection (also referred to as Waste Collector).
*   **Slip Number:** A unique identifier provided by the Service Provider upon collection.
*   **Commodity:** A type of waste that has an assigned monetary value.
*   **Super Admin:** An internal user with full administrative privileges over the portal.
*   **Data Capture:** An internal user responsible for inputting and managing data within the portal.




## 2. Overall Description

### 2.1 Product Perspective
The Waste Optimization Management Portal will be a standalone web-based application accessible via standard web browsers. It will not directly integrate with existing client systems initially, but will be designed with a modular architecture to allow for future integrations if required. The system will primarily serve as a data management and reporting tool, providing a comprehensive overview of waste collection and recycling operations.

### 2.2 Product Functions
The core functions of the portal include:
*   **Order Management:** Creation, tracking, and finalization of waste and recycling collection orders.
*   **Data Input:** Recording of actual collected quantities, discrepancies, and detailed sorted waste breakdowns.
*   **Waste Stream Tracking:** Monitoring of various waste types from collection to sorting, including commodity valuation.
*   **Reporting:** Generation of customizable reports for internal analysis and client consumption.
*   **Client Access:** Secure login for end-clients to view and download their specific reports.
*   **User and Role Management:** Administration of internal and external user accounts with defined roles and permissions.

### 2.3 User Characteristics
*   **Super Admins:** Possess full administrative rights, including user management, system configuration, and access to all data and reports. They will be responsible for setting up client accounts, managing internal users, and overseeing the overall system operation.
*   **Data Captures:** Primarily responsible for inputting data related to waste collection and sorting. This includes creating orders, updating collection details (actual quantities, slip numbers), and recording sorted waste breakdowns. They require an intuitive interface for efficient data entry.
*   **Client Administrators:** End-client users with administrative privileges for their respective company/branch. They can manage user access for their own organization and view/download reports relevant to their company.
*   **Client Site Managers:** End-client users with access to view/download reports specifically for their assigned sites or branches.

### 2.4 General Constraints
*   **Web-based Access:** The portal must be accessible via modern web browsers (e.g., Chrome, Firefox, Edge, Safari).
*   **Security:** All data, especially client-specific information, must be secured against unauthorized access. User authentication and authorization mechanisms are critical.
*   **Scalability:** The system should be designed to handle a growing number of clients, branches, sites, and increasing data volumes without significant performance degradation.
*   **Usability:** The user interface should be intuitive and easy to navigate for all user types, minimizing training requirements.
*   **Performance:** The system must provide timely responses to user interactions and report generation requests.

### 2.5 Assumptions and Dependencies
*   The client will provide clear definitions and classifications for all waste types and commodities, including any associated values.
*   The waste collection process, including the provision of slip numbers by service providers, will remain consistent with the described process flow.
*   Users will have reliable internet access to utilize the web portal.
*   The client will provide necessary infrastructure or hosting environment details if not deployed in a managed cloud environment.




## 3. Functional Requirements

### 3.1 User Management

#### FR-UM-001: User Authentication
The system shall allow users to securely log in using a unique username and password. The system shall implement industry-standard authentication protocols to protect user credentials.

#### FR-UM-002: Role-Based Access Control
The system shall implement a role-based access control (RBAC) mechanism to define and enforce permissions for different user types. Initially, the system shall support the following roles:
*   **Super Admin:** Full access to all system functionalities, including user creation, role assignment, client setup, and data management.
*   **Data Capture:** Permissions to create and update orders, input collection data (quantities, slip numbers), and record sorted waste breakdowns.
*   **Client Administrator:** Access to view and download reports for their associated company and manage user access within their organization.
*   **Client Site Manager:** Access to view and download reports for specific sites or branches under their management.

#### FR-UM-003: User Account Management
Super Admins shall be able to create, edit, deactivate, and delete user accounts. This includes assigning roles and associating client users with specific companies, branches, or sites.

### 3.2 Client Management

#### FR-CM-001: Client Onboarding
Super Admins shall be able to create and manage client profiles, including company details, associated branches, and individual sites. This will allow for hierarchical structuring of client data.

#### FR-CM-002: Client-Specific Configuration
The system shall allow Super Admins to configure client-specific settings, such as waste types, commodity values, and reporting preferences.

### 3.3 Order Management

#### FR-OM-001: Order Creation
Data Captures shall be able to create new waste collection orders. Orders shall include details such as the client (company, branch, site), requested collection date, type of order (Waste Order or Recycling Order), and initial estimated quantities (e.g., number of wheelie bins, type of waste).

#### FR-OM-002: Auto-Generated Tracking Numbers
Upon creation, each order shall be assigned a unique, auto-generated tracking number. This tracking number will serve as the primary identifier for the order throughout its lifecycle.

#### FR-OM-003: Order Status Tracking
The system shall maintain a status for each order, reflecting its current stage in the collection and sorting process. Initial statuses shall include, but not be limited to: `Pending`, `Scheduled`, `Collected`, `Sorted`, and `Finalized`. The system shall allow Data Captures to update the order status.

#### FR-OM-004: Collection Data Input
Data Captures shall be able to input actual collection details for an order. This includes:
*   **Actual Quantities:** Recording the actual number of bins or containers collected, even if it differs from the initial order. The system should highlight any discrepancies between ordered and collected quantities.
*   **Slip Number:** Manual input of the slip number provided by the service provider. An order can only be `Finalized` once a slip number has been captured.

#### FR-OM-005: Waste Order vs. Recycling Order Handling
*   **Waste Order:** For 


Waste Orders, the system will allow for the input of initial estimated quantities (e.g., number of wheelie bins) without requiring specific waste types at the order creation stage.
*   **Recycling Order:** For Recycling Orders, the system will allow for the input of specific waste types and quantities (e.g., aluminum, paper) at the order creation stage.

### 3.4 Waste Processing and Tracking

#### FR-WPT-001: Sorted Waste Input
Data Captures shall be able to manually input the breakdown of sorted waste for a collected order. This includes specifying the type of waste (e.g., glass, electronics, aluminum cans, paper) and its corresponding weight or quantity (e.g., "x amount was glass, y amount was aluminium").

#### FR-WPT-002: Commodity Valuation
The system shall allow for the assignment of a monetary value to specific waste types identified as commodities. This value can be configured by Super Admins and will be used in financial reporting.

#### FR-WPT-003: Waste Stream Data Storage
The system shall store detailed information for each waste stream, including:
*   Waste Type (e.g., General Waste, PET Clear, Cardboard)
*   Gross Weight
*   Tare Weight
*   Nett Weight
*   Associated Commodity Value (if applicable)

### 3.5 Reporting

#### FR-REP-001: Internal Reporting
The system shall enable internal users (Super Admins, Data Captures) to generate various reports based on collected and sorted waste data. These reports shall include, but not be limited to:
*   Waste volume reports per client, per branch, per site.
*   Waste type breakdown reports.
*   Commodity value reports.
*   Environmental impact reports (similar to the provided sample report, showing diverted from landfill, lifecycle savings, etc.).

#### FR-REP-002: Client-Facing Reports
Client users (Client Administrators, Client Site Managers) shall be able to view and download reports relevant to their access level. These reports will provide insights into their waste generation, recycling efforts, and environmental impact. Reports shall be available on-demand.

#### FR-REP-003: Report Customization
The system shall provide options for filtering and customizing reports based on date ranges, client entities (company, branch, site), and waste types.

#### FR-REP-004: Report Export
All generated reports shall be exportable in common formats such as PDF and CSV/Excel.

### 3.6 Client Portal

#### FR-CP-001: Secure Client Login
End-clients shall have a secure login interface to access their personalized portal.

#### FR-CP-002: Client Dashboard
Upon login, clients shall see a dashboard providing an overview of their waste management activities, including recent collections, overall waste metrics, and quick access to reports.

#### FR-CP-003: Report Access and Download
Clients shall be able to view and download all reports relevant to their assigned company, branch, or site directly from the portal.




## 4. Non-Functional Requirements

### 4.1 Performance

#### NFR-PER-001: Response Time
The system shall respond to user interactions (e.g., page loads, form submissions, data queries) within 3 seconds under normal load conditions.

#### NFR-PER-002: Report Generation Time
Complex reports involving large datasets shall be generated and available for viewing/download within 30 seconds.

#### NFR-PER-003: Scalability
The system shall be capable of supporting up to 100 concurrent users and managing data for up to 1000 clients, each with multiple branches and sites, without significant degradation in performance.

### 4.2 Security

#### NFR-SEC-001: Data Encryption
All sensitive data, including user credentials and client-specific information, shall be encrypted both in transit (using HTTPS/TLS) and at rest (database encryption).

#### NFR-SEC-002: Access Control
Access to system functionalities and data shall be strictly controlled based on assigned user roles and permissions (as defined in FR-UM-002).

#### NFR-SEC-003: Audit Trails
The system shall maintain audit trails for all critical actions performed by users (e.g., order creation, data modification, report generation) to ensure accountability and traceability.

#### NFR-SEC-004: Protection Against Vulnerabilities
The system shall be developed with security best practices to protect against common web vulnerabilities such as SQL injection, cross-site scripting (XSS), and cross-site request forgery (CSRF).

### 4.3 Usability

#### NFR-US-001: Intuitive User Interface
The user interface shall be intuitive and easy to navigate, requiring minimal training for new users. It shall follow consistent design principles throughout the application.

#### NFR-US-002: Error Handling
The system shall provide clear, concise, and user-friendly error messages that guide users on how to resolve issues.

#### NFR-US-003: Accessibility
The portal shall adhere to basic web accessibility standards (e.g., WCAG 2.1 AA) to ensure it is usable by individuals with disabilities.

### 4.4 Reliability

#### NFR-REL-001: System Availability
The system shall be available 99.5% of the time, excluding scheduled maintenance windows.

#### NFR-REL-002: Data Backup and Recovery
Regular data backups shall be performed, and a robust data recovery plan shall be in place to restore the system to a recent state in the event of data loss or system failure.

### 4.5 Maintainability

#### NFR-MAI-001: Code Quality
The codebase shall be well-structured, documented, and follow established coding standards to facilitate future maintenance and enhancements.

#### NFR-MAI-002: Modularity
The system architecture shall be modular, allowing for independent development, testing, and deployment of components.

### 4.6 Portability

#### NFR-POR-001: Browser Compatibility
The system shall be compatible with the latest stable versions of major web browsers, including Google Chrome, Mozilla Firefox, Microsoft Edge, and Apple Safari.

## 5. Conclusion

This Software Requirements Specification document outlines the essential functional and non-functional requirements for the Waste Optimization Management Portal. It is intended to serve as a comprehensive guide for the development team and a reference point for all stakeholders. By adhering to these requirements, the aim is to deliver a robust, efficient, and user-friendly system that meets the client's business objectives and enhances their waste management operations. This document will be subject to review and feedback from the client to ensure all requirements are accurately captured and understood before proceeding to the next phases of the project.

## 6. References

[1] Sample Report: `file:///home/ubuntu/upload/samplereport(2).pdf`
[2] Competitor Report: `file:///home/ubuntu/upload/competitorsreport.PDF`
[3] Competitor Sample Report 2: `file:///home/ubuntu/CompetitorSamplereport2(1).csv`
[4] Process Flow Diagram: `file:///home/ubuntu/upload/Screenshot2025-09-23at13.33.24.png`


