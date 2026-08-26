# SPRINT 64 — Assistant Dashboard & Permission-Aware Navigation Report

## 1. Scope
- Permission-aware Assistant Sidebar (desktop + mobile)
- Permission-aware Assistant Dashboard Cards
- No-Permissions fallback correctness
- No business-logic changes; no route/middleware/permission-definition changes

## 2. Source of Truth
- `docs/specifications/assistant_features.md` (Sections 3, 4, 14 — Business Rule 12)
- Existing middleware (`assistant:<permission>`) and permission keys in `assistant_permissions`

## 3. Existing Behavior (before fix)
- Sidebar rendered ALL assistant links unconditionally → violated Business Rule 12.
- Dashboard showed cards for only 6 permissions; missing cards for `can_manage_lessons`, `can_manage_exams`, `can_manage_grades`, profile, notifications.
- Fallback checked only 6 permissions (including `can_restore_students`, which produces no card) → assistant with only `can_manage_lessons` saw "no permissions" message incorrectly.

## 4. Problems Found
1. Sidebar links not gated by permissions.
2. Mobile bottom nav "Groups" link not gated by `can_view_groups`.
3. Missing dashboard cards: lessons, exams, grades, profile, notifications.
4. Fallback permission list mismatched actual card set.

## 5. Root Causes
- Views were written before later permissions (`can_manage_lessons`, `can_manage_exams`, `can_manage_grades`) were added; never updated.

## 6. Files Modified
| File | Change |
|---|---|
| `resources/views/components/dashboard-layout.blade.php` | Wrapped each permission-gated sidebar link with `@if($currentUser->canPerm(...))`; resources link now points to `assistant.resources.index`; question-bank grouped under `can_manage_exams`; mobile bottom-nav Groups link gated. |
| `resources/views/assistant/dashboard.blade.php` | Added cards for lessons, exams, grades, profile, notifications; resources/reservations cards point to index pages; fallback now computed from exact card-producing permission set. |
| `tests/Feature/Sprint64AssistantDashboardPermissionsTest.php` | New focused feature tests (Cases A–E). |

No changes to routes, middleware, controllers, models, or permission definitions.

## 7. Fixes
- Sidebar: each feature link hidden unless `$currentUser->canPerm('<key>')`. Links requiring no independent permission (dashboard, notifications, profile) always visible.
- Dashboard: one card per permission-based feature + always-available Notifications & Profile cards.
- Fallback: shown only when NONE of the 8 card-producing permissions is granted:
  `can_take_attendance, can_manage_students, can_view_groups, can_manage_lessons, can_upload_resources, can_manage_reservations, can_manage_exams, can_manage_grades`.
  `can_restore_students` excluded (produces no separate card).

## 8. Tests
`php artisan test --filter=Sprint64AssistantDashboardPermissionsTest` → **5 passed (39 assertions)**

| Case | Test | Result |
|---|---|---|
| A — Full permissions | all 10 card URLs visible, no fallback | ✅ |
| B — No permissions | fallback shown, gated links absent, notifications/profile still visible | ✅ |
| C — Single permission (`can_manage_lessons`) | only Lessons card/link, no fallback | ✅ |
| D — Multiple selective | only matching cards, others absent | ✅ |
| E — Revoke | card disappears after revoke; direct URL to `/assistant/lessons` returns **403** via existing middleware | ✅ |

Desktop/Mobile use the same `canPerm()` checks in both the desktop sidebar and mobile bottom nav — single permission logic, layout-only differences.

## 9. Regression Results
Full suite: 46 passed / 13 failed. The 13 failures are **pre-existing** and unrelated:
- `Auth\AuthenticationTest`, `EmailVerificationTest`, `RegistrationTest`: reference a non-existent `dashboard` route (stale Breeze boilerplate).
- `Sprint30AuditTest`: uses sqlite `:memory:` without running migrations.

Verified pre-existing by stashing all working-tree changes and re-running: same tests fail on clean tree (14 failed). All Sprint 63 student reservation/resource tests pass. Assistant login/dashboard/profile/grades flows unaffected (views-only change).

## 10. Permission Mapping (Sidebar & Cards)

| Permission | Sidebar Link(s) | Dashboard Card |
|---|---|---|
| `can_take_attendance` | الحضور → `assistant.attendance.index` | تسجيل الحضور |
| `can_manage_students` | طلاب المدرس → `assistant.students.index` | إدارة الطلاب |
| `can_view_groups` | المجموعات → `assistant.groups.index` (+ mobile nav) | مشاهدة المجموعات |
| `can_manage_lessons` | الدروس → `assistant.lessons.index` | إدارة الدروس |
| `can_upload_resources` | المصادر → `assistant.resources.index` | إدارة الموارد |
| `can_manage_reservations` | الحجوزات → `assistant.reservations.index` | إدارة الحجوزات |
| `can_manage_exams` | الامتحانات + بنك الأسئلة → `assistant.exams.index`, `assistant.question-bank.index` | إدارة الامتحانات |
| `can_manage_grades` | الدرجات → `assistant.grades.index` | إدارة الدرجات |
| *(none)* | لوحة المساعد، الإشعارات، حسابي | الإشعارات، الملف الشخصي |
| `can_restore_students` | *(no standalone link/card — used inside students views)* | *(none — excluded from fallback)* |

## 11. Dashboard Card Mapping
Granted Permissions ↔ Visible Cards verified 1:1 by Case A–D tests.

## 12. Final Status

| Area | Required | Implemented | Verified | Status |
|---|---|---|---|---|
| Permission-aware Sidebar | Yes | ✅ | ✅ | Done |
| Dashboard Cards | Yes | ✅ | ✅ | Done |
| No-Permissions fallback | Yes | ✅ | ✅ | Done |
| Permission revoke behavior | Yes | ✅ | ✅ | Done |
| Desktop navigation | Yes | ✅ | ✅ | Done |
| Mobile navigation | Yes | ✅ | ✅ | Done |

Completion criteria: all met (sidebar gating, full card coverage, per-permission visibility, correct fallback, single/multiple/revoke cases, direct URL protection via existing middleware, desktop+mobile, no regression from this sprint's changes).

## 13. Remaining Issues
- Pre-existing test-suite failures (stale Auth boilerplate tests, Sprint30AuditTest sqlite setup) — out of scope for Sprint 64, unrelated to this work.