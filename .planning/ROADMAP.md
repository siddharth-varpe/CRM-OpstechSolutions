# ROADMAP: SR Enterprises CRM — Laravel Conversion

## Phase 1: Foundation, Workspace Architecture & Authentication
- [x] 1.1 Archive legacy monorepo to `.legacy_monorepo/`
- [x] 1.2 Scaffold root Laravel project with Composer (PHP 8.5, SQLite database configuration)
- [x] 1.3 Setup design system: Tailwind CSS, custom assets, Blade master layouts (App, Auth, Technician Portal), Lucide SVG icons, jQuery and AJAX helpers
- [x] 1.4 Database migrations: `users`, `roles`, `permissions`, `sessions`, `audit_logs`
- [x] 1.5 Authentication: Login with CAPTCHA, RBAC middleware, session management, logout, password reset
- [x] 1.6 Dashboard shell with live KPI cards and navigation parity

## Phase 2: Core Master Data & Customer Domain
- [x] 2.1 Database migrations: `customers`, `customer_addresses`, `products`, `inventory_items`, `inventory_movements`, `documents`
- [x] 2.2 Models & Relationships (Eloquent)
- [x] 2.3 Customers Module (List, Details, Create/Edit Modals, Address management, Export)
- [x] 2.4 Products & Inventory Management (Catalog, Stock ledger, Low stock alerts, Warehouses)
- [x] 2.5 Document & Attachment storage engine

## Phase 3: Sales, Invoicing, Numbering & Payments
- [x] 3.1 Database migrations: `sales_orders`, `sales_order_items`, `invoices`, `invoice_items`, `payments`, `sequences`
- [x] 3.2 Sequential Numbering Service (`INV-YYYY-XXXX`, `SO-YYYY-XXXX`, etc.)
- [x] 3.3 Sales Orders Workflow (Create, Approve, Convert to Invoice, Auto FIFO stock deduction)
- [x] 3.4 Invoicing & GST Tax Engine (CGST, SGST, IGST calculations, Tax Invoices, PDF print layout)
- [x] 3.5 Payments & Ledger (Receipts, Partial payments, Balance dues, Payment modes)

## Phase 4: Service Management, Job Cards, Warranties & Dues
- [x] 4.1 Database migrations: `assets`, `services`, `job_cards`, `job_card_items`, `warranties`, `warranty_items`, `service_reminders`
- [x] 4.2 Asset tracking & lifecycle linking
- [x] 4.3 Warranty Contracts & Automated Periodic Service Schedule Generator
- [x] 4.4 Service Requests & Job Cards (Dispatch, Checklist, Parts consumed, Signature capture)
- [x] 4.5 Date-wise Service Dues Ledger & Heatmap Calendar

## Phase 5: Technician Field Portal, Live Maps & GPS Tracking
- [x] 5.1 Database migrations: `technicians`, `technician_locations`, `technician_sessions`
- [x] 5.2 Mobile-first Technician Portal layout & PIN auth
- [x] 5.3 Technician dashboard: Assigned tasks, status updates, completion flow
- [x] 5.4 Live GPS Tracking & Route Map (Google Maps / Leaflet integration with fallback)
- [x] 5.5 Digital signature pad and customer review collection

## Phase 6: Rentals, Analytics, Automated Communications & System Operations
- [x] 6.1 Database migrations: `rentals`, `rental_payments`, `notifications`, `system_settings`, `whatsapp_messages`
- [x] 6.2 Machine Rentals & Subscription billing engine
- [x] 6.3 Analytics Engine & Reports (Revenue, TAT, Technician efficiency, Inventory turnover)
- [x] 6.4 Communications: PHPMailer / SMTP email dispatch & Meta WhatsApp API integration
- [x] 6.5 System Settings, Backup/Restore engine & automated cron jobs
