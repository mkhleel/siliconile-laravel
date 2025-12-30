# Project Goal:
Build a specialized Coworking Space Management System (CSMS) for a single-location business.
The system needs to manage memberships, bookings, payments via Kashier.io, and network access via direct Mikrotik API integration.

# Technology Stack (Strict Requirements):
- **Framework:** Laravel 12
- **Admin Panel:** Filament v4 (Panels, Tables, Forms, Widgets, Notifications).
- **Architecture:** Modular Architecture using `nwidart/laravel-modules`.
- **Frontend/Components:** Livewire Volt (Class-based functional API).
- **Styling:** Tailwind CSS.
- **Database:** MySQL.
- **External Integrations:** Kashier.io (Payments), Mikrotik RouterOS (Network Control).

# Architecture & Modules Structure:
Use `nwidart/laravel-modules` to organize the code into these specific modules:

1. **Membership Module:**
   - **Models:** Member, Plan (Daily, Monthly, Yearly), Contract.
   - **Features:** Member profiles, CRM data, plan subscription logic.
   - **Logic:** When a membership expires, fire an event to trigger the Network Module.
   
2. **Space & Booking Module:**
   - **Models:** Room, Desk, Resource, Booking.
   - **Features:**
     - Real-time calendar availability.
     - Conflict prevention (Double booking protection).
   - **Frontend:** A Livewire Volt component for members to book rooms visually.

3. **Finance Module (Kashier.io Integration):**
   - **Models:** Invoice, Transaction, PaymentMethod.
   - **Service:** `KashierPaymentService`.
     - Handle payment initialization (Checkout API).
     - Handle Webhook/Callback to update Invoice status to 'Paid'.
   - **Features:** Automated invoice generation. If payment is successful, extend membership automatically.

4. **Network Module (Mikrotik Direct Integration):**
   - **Models:** WifiUser (linked to Member), AccessLog.
   - **Service:** `MikrotikRouterService`.
     - **Requirement:** Connect directly to the RouterOS API (e.g., using a package like `benmenking/routeros-api` or raw socket).
     - **Methods:** `addUser()`, `disableUser()`, `kickUser()`, `checkOnlineStatus()`.
   - **Automation:** Listen for `MembershipExpired` or `PaymentFailed` events to immediately call `disableUser()` on the Mikrotik router.

# Functional Requirements:

## 1. Filament Admin Panel (Backend):
- **Resources:** Full CRUD for Members, Plans, Bookings, Invoices.
- **Actions:** - A custom Action button on the Member view: "Sync to Mikrotik" (manual trigger).
  - "Send Payment Link" action that generates a Kashier URL and emails it.
- **Widgets:** Real-time online users count (fetched from Mikrotik).

## 2. Member Portal (Frontend - Livewire Volt):
- **Dashboard:** Show remaining days in subscription & WiFi Credentials.
- **Booking:** Calendar view to select slots.
- **Payments:** List of unpaid invoices with a "Pay Now" button that redirects to Kashier.

# Code Style Guidelines:
- **Strict Types:** Use `declare(strict_types=1);`.
- **Service-Oriented:** Keep Controllers and Volt components thin. Move business logic (especially Mikrotik and Kashier logic) into dedicated Services.
- **Events & Listeners:** Decouple modules. Example: The Finance module fires `InvoicePaid`, the Membership module listens to renew subscription, and the Network module listens to enable WiFi access.
- **Error Handling:** Ensure Mikrotik connection failures do not break the entire app (use Queues or Try-Catch blocks with logging).

# Task:
Analyze this architecture. Then, start by generating the **Folder Structure** for the modules and the **Database Schema (Migrations)** for the `Network` and `Finance` modules specifically, detailing how you will store the Mikrotik credentials and Kashier transaction references.