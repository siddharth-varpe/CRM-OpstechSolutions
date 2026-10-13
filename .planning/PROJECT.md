# PROJECT: SR Enterprises CRM — Laravel Migration

## Vision & Scope
Migrate the complete SR Enterprises Commercial & Domestic RO Water Purifier CRM/SRM SaaS from TypeScript/Fastify/React into a high-performance, enterprise-grade **Laravel (PHP 8.5+), Blade, Tailwind CSS, AJAX, and jQuery** application.

## Core Mandates & Constraints
1. **Zero UI/UX Degradation**: All pages, sidebars, topbars, responsive layouts, data tables, search filters, modal dialogues, toasts, badges, print formats, and theme styling must 100% replicate the existing React interface.
2. **Zero Business Logic Degradation**: 100% porting of all calculation engines:
   - GST tax calculations (CGST, SGST, IGST)
   - FIFO inventory stock valuation and deduction
   - Sequential document numbering (INV-YYYY-XXXX, WAR-YYYY-XXXX, JC-YYYY-XXXX, etc.)
   - Warranty contract boundaries and periodic maintenance schedule generator
   - Due dates, aging buckets, and rental billing cycles
   - RBAC security matrix (Admin, Manager, Technician, Operator)
   - Technician portal, mobile-first design, signatures, and geolocation tracking
   - Meta WhatsApp API, SMTP / PHPMailer transactional notifications
3. **Tech Stack**:
   - Backend: Laravel (PHP 8.5)
   - Database: SQLite (Development) with zero-configuration setup, fully compatible with MySQL/PostgreSQL for production
   - Frontend: Blade Templates + Tailwind CSS + jQuery & AJAX (retaining rich interactivity without SPA overhead)
   - Storage & Assets: Local public storage, SVG icon system (Lucide compatible)
4. **Source of Truth Reference**:
   - The existing code is preserved under `.legacy_monorepo/` for 1:1 code and asset parity.
