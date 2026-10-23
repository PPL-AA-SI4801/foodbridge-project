# PRD: FoodBridge (Zero Hunger Platform)

**Version:** 1.2 | **Date:** 23 Oct 2026 | **Team:** Kelompok AA, SI4801 | **SDG:** 2 Zero Hunger

## 1. Overview
FoodBridge is a responsive web platform that connects businesses with surplus food (restaurants, hotels, caterers, MBG kitchens) to orphanages and social institutions that need it. It acts as an information and record-keeping hub only. It does not supply food or couriers.

## 2. Problem
- Surplus food is still edible but is wasted because distribution is manual (phone/chat), limited to known contacts, and slow.
- Orphanages and community kitchens struggle to get consistent, nutritious food.
- There is no record of handovers, so impact cannot be measured or held accountable.
- Indonesia's FLW is 23–48M tons/year, and MBG leftovers are estimated at 1.1–1.4M tons/year.

## 3. Goals
| # | Goal |
|---|------|
| G1 | Distribute surplus food quickly, before its safe-consumption deadline |
| G2 | Ensure only verified institutions participate and aid is spread fairly |
| G3 | Make every handover traceable and verifiable |
| G4 | Monitor food quality and handle unfit-food reports |
| G5 | Report distribution impact toward SDG 2 |

## 4. Users
| Role | Description | Key abilities |
|------|-------------|---------------|
| **Donor** | Restaurant, hotel, caterer, supermarket, MBG kitchen | Post food, approve/reject claims, verify handover, view impact dashboard |
| **Penerima** | Orphanage, community kitchen, social institution | Browse catalog, claim (3 per day), post to Request Board, review/report food |
| **Admin** | Platform operator | Verify accounts, set maximum safety hours, handle reports, block accounts after investigation, manage data, view platform-wide dashboard |

## 5. Scope
**In scope:** institution-to-institution food donation, web only, in-app notifications, local/urban pickup area, admin-configurable safety hours, admin account blocking.
**Out of scope:** individual retail sales, payment gateway, in-house courier service, push/email/WhatsApp notifications, admin-configurable pickup radius.

## 6. Core User Flow
1. Donor and Penerima register and upload legal documents, and the Admin approves them.
2. Donor posts surplus food (photo, nutrition category, portions, location, safety hours within the maximum set by the Admin).
3. Penerima finds food in the catalog (filter by nearest location and category) and submits a claim (max 3 per day).
4. Donor approves or rejects the claim.
5. Penerima chooses a pickup method:
   - **Self pickup:** the system generates a 6-digit code, which the Penerima shows to the Donor.
   - **Online courier (Gojek/Grab, ordered outside the system):** the Penerima submits driver name, vehicle number, courier verification code, and an order screenshot, and the Donor matches the driver on arrival.
6. Donor marks the claim as completed, and the handover is logged with a timestamp.
7. Penerima reviews quality or reports unfit food, and the Admin investigates and may block the offending account.
8. Data feeds the impact dashboard and Excel export.

## 7. Functional Requirements
| ID | Requirement | Role | Priority |
|----|-------------|------|----------|
| FR-01 | Registration with legal document upload and Admin approval | All | Must |
| FR-02 | Profile management (including location point) | All | Must |
| FR-03 | Post surplus food; auto-expire after safety hours | Donor | Must |
| FR-04 | Real-time catalog with nearest-location and nutrition filters | Penerima | Must |
| FR-05 | Claim submission, limited to 3 claims per day per Penerima | Penerima | Must |
| FR-06 | Request Board for urgent needs (visible to Donor and Admin) | Penerima | Should |
| FR-07 | Pickup method: self pickup or online courier with driver data form | Penerima | Must |
| FR-08 | Approve/reject claims | Donor | Must |
| FR-09 | 6-digit handover verification code (self pickup) | Penerima, Donor | Must |
| FR-10 | In-app notifications on status changes | All | Must |
| FR-11 | Food quality review | Penerima | Should |
| FR-12 | Report unfit food | Penerima | Must |
| FR-13 | Review and act on unfit-food reports | Admin | Must |
| FR-14 | Impact dashboard: portions distributed, active Donors/Penerima, estimated beneficiaries (Donor: own data; Admin: platform-wide) | Donor, Admin | Should |
| FR-15 | Excel export of impact data | Donor, Admin | Should |

## 8. Non-Functional Requirements
| ID | Requirement |
|----|-------------|
| NFR-01 | Catalog load and claim processing in 2 seconds or less |
| NFR-02 | Secure authentication, role-based access, protection against common web attacks |
| NFR-03/06 | High uptime, available 24/7 (food is often discarded at night) |
| NFR-04 | Handles spikes in registrations and simultaneous posts |
| NFR-05 | Responsive UI for desktop and mobile |
| NFR-07 | No data loss in distribution history (basis for SDG reporting) |
| NFR-08 | Protect location and Penerima/Donor data from external exploitation |

## 9. Technical Stack
- **Backend/Frontend:** Laravel (MVC) + Blade
- **Database:** MySQL (11 entities: users, donor_profiles, recipient_profiles, verification_documents, food_categories, food_posts, food_requests, claims, courier_details, food_reviews, notifications)
- **Maps:** Leaflet.js (OpenStreetMap) or Google Maps API
- **Testing:** Laravel Dusk (E2E)
- **Deployment:** Railway
- **Tooling:** Git/GitHub Organization, Jira (Scrum)

## 10. Success Metrics (proposed targets)
| Metric | Target |
|--------|--------|
| E2E test pass rate on core flows | 100% |

## 11. Risks
| Risk | Mitigation |
|------|------------|
| Unsafe food reaches Penerima | Admin-set maximum safety hours, auto-expiry, quality review, unfit-food reports, Admin investigation |
| Fake or illegitimate accounts | Mandatory document verification by Admin |
| Claim monopolization | Limit of 3 claims per day per Penerima |
| Courier fraud or mismatch | Driver data matched by Donor before handover |
| Repeated misuse or unsafe food from an account | Admin can block the account after investigation |