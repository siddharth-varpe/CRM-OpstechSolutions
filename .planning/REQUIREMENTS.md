# REQUIREMENTS: SR Enterprises CRM (Laravel Edition)

## 1. Functional Requirements

### 1.1 Authentication & Security (RBAC)
- User Authentication with secure password hashing (Bcrypt / Argon2id).
- Built-in CAPTCHA generation and validation on login.
- Account lockout policy after failed login attempts.
- RBAC roles: `ADMIN`, `MANAGER`, `TECHNICIAN`, `OPERATOR`.
- Granular permissions per module (View, Create, Edit, Delete, Export).
- Dedicated Technician PIN/Password authentication portal.

### 1.2 Customer & Asset Management
- Customer profiles with GSTIN, billing/shipping addresses, contact numbers, email.
- Asset tracking with machine model, serial number, membrane type, booster pump specs, installation date, warranty link, and service history.
- Document storage and attachment uploads.

### 1.3 Inventory & Products
- Products master (Commercial RO, Domestic RO, Spares, Filters, Membranes, Pumps).
- Multi-warehouse/van inventory tracking.
- FIFO stock deduction upon sales and job card completion.
- Low-stock alerts and movement logs.

### 1.4 Sales, Invoices & Payments
- Sales orders with customer linking, asset creation, and automated invoice trigger.
- GST-compliant invoices (INV-YYYY-XXXX) with auto CGST/SGST/IGST breakdown.
- Payment recording (Cash, UPI, NEFT, Cheque) with receipts and ledger balance tracking.
- Sequential numbering engine with lock protection.

### 1.5 Service Management & Job Cards
- Service scheduling and dispatching to technicians.
- Job cards (JC-YYYY-XXXX) with fault diagnosis, service checklists, spare parts consumption, and customer digital signatures.
- Date-wise service dues ledger and visual heatmap.

### 1.6 Warranties & Maintenance Schedulers
- Warranty contract generation (WAR-YYYY-XXXX) with start/end dates.
- Automatic creation of quarterly/periodic maintenance visits (4 services/year).
- Warranty coverage rules (parts vs consumable exclusions).

### 1.7 Field Technician Portal & Live Maps
- Mobile-first responsive UI for field technicians.
- Assigned services queue, accept/start service workflow.
- Live GPS location logging and technician route/map view.
- Digital signature capture (Canvas/Touch) and customer rating.

### 1.8 Rentals & Subscriptions
- Commercial machine rental agreements.
- Security deposit tracking and monthly recurring invoice generation.

### 1.9 Communications & External Integrations
- Meta WhatsApp Business API webhook & message dispatch.
- SMTP / PHPMailer email delivery with invoice attachments.
- In-app notification bell and unread indicators.

---

## 2. UI/UX Requirements
- Identical layout: Left sidebar navigation with collapsible groups, persistent topbar with global search, notifications, active user profile.
- Consistent color palette: Slate/Indigo modern SaaS theme, clean borders, badge colors for statuses (`PENDING`, `COMPLETED`, `CANCELLED`, `OVERDUE`).
- Interactive tables with pagination, sorting, filters, export buttons, and quick search.
- Responsive design for Desktop, Tablet, and Mobile.
- Fast AJAX modal dialogues for quick creation without full page reloads.
