# 🌟 [anhduong-cards] - Ánh Dương Discount Card Management System

**Author:** [kein]
**Version:** 1.0.0
**Requires WordPress:** 6.0 or higher (Compatible with the latest version)

## Introduction

The **[anhduong-cards]** plugin was developed for **Ánh Dương Hospital Joint Stock Company**to implement the **50% Discount Card – 2nd Launch** plan. The goal is to fully automate the card management process, show appreciation to current customers, and attract new clients to experience the services at Ánh Dương Clinic.

## Key Features

**Card Validation and Activation:** Customers scan the QR Code on the card to access the website and enter the Card ID (e.g., VNG200001 – VNG200200) for validation.
**Automatic Registration/Data Update:** The system automatically redirects to the Personal Information Registration page (for new customers) or skips it (for existing customers)[cite: 32].
]**50% Discount Appointment Booking:** Customers can easily book appointments and select services that qualify for a 50% discount (e.g., General Check-up / Comprehensive Cancer Screening packages).
**Automatic Card Renewal:** Supports the re-activation of expired cards, granting 12 more months of validity from the renewal date.
**Internal Management and Alert System:**
    The system automatically saves all data (Name, Phone number, Card ID, Appointment Date, etc.) to an Excel or Google Sheet file.
    Sends **Automatic Internal Email Alerts** immediately to relevant departments (Reception, CSKH/Customer Service, Management) upon: New customer registration, Appointment booking, and Card renewal.

## Workflow

1.  Customer scans the QR code on the back of the card.
2.  Accesses the **"CARD ID VALIDATION"** page and enters the card ID.
3.  If valid, redirects to **"CUSTOMER INFORMATION REGISTRATION"** (if new customer).
4.  Redirects to **"APPOINTMENT BOOKING"** to select date/time/50% discounted service.
5.  Alternatively, redirects to **"CARD RENEWAL"** if the card has expired.

## Installation & Configuration Guide

1.  Clone the Repository to your local machine: `git clone [Your repository URL]`
2.  Compress the plugin folder into a `.zip` file.
3.  Go to **WordPress Dashboard** > **Plugins** > **Add New** > **Upload Plugin**.
4.  Upload the `.zip` file and activate the plugin.
5.  [cite_start]**Configuration:** You must configure the connection between the plugin and the **Card ID Database** (Excel/Google Sheet file) and set up the **Internal Email** for alerts (kiennd.dev@gmail.com).

## Contact
kiennd.dev@gmail.com

---
