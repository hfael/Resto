import os
import uuid
import mimetypes
import base64
import tempfile
from datetime import date, timedelta
from pathlib import Path

import pytest
import requests

BASE_URL = os.environ.get("API_BASE_URL", "http://localhost:8080/api")
TIMEOUT = int(os.environ.get("API_TIMEOUT", "10"))
HEADERS_FLUTTER = {"User-Agent": "Dart/Flutter"}
SEED_KEY = os.environ.get("SEED_KEY", "devseed")
SEED_HEADERS = {"X-Seed-Key": SEED_KEY, **HEADERS_FLUTTER}

_TEMP_IMAGE = None


def env_truthy(name: str, default: bool = False) -> bool:
    value = os.environ.get(name)
    if value is None:
        return default
    return value.strip().lower() in ("1", "true", "yes", "on")


RESET_DB = env_truthy("API_RESET", False)


def api_url(path: str) -> str:
    return f"{BASE_URL}{path}"


def random_email():
    return f"test+{uuid.uuid4().hex[:8]}@example.com"


def random_name(prefix="resto"):
    return f"{prefix}_{uuid.uuid4().hex[:6]}"


def ensure_temp_image():
    global _TEMP_IMAGE
    if _TEMP_IMAGE and _TEMP_IMAGE.exists():
        return _TEMP_IMAGE

    png_base64 = (
        "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/wwAAgMB"
        "gL8GZ4AAAAAASUVORK5CYII="
    )

    tmp_path = Path(tempfile.gettempdir()) / f"resto_test_{uuid.uuid4().hex}.png"
    tmp_path.write_bytes(base64.b64decode(png_base64))
    _TEMP_IMAGE = tmp_path
    return tmp_path


def find_sample_image():
    root = Path(__file__).resolve().parents[1]
    uploads = root / "public" / "uploads"
    candidates = []

    if uploads.exists():
        candidates += list(uploads.glob("*.jpg"))
        candidates += list(uploads.glob("*.jpeg"))
        candidates += list(uploads.glob("*.png"))
        candidates += list(uploads.glob("*.webp"))

    return candidates[0] if candidates else ensure_temp_image()


def assert_status(resp, expected, label):
    assert resp.status_code == expected, (
        f"{label}: expected {expected}, got {resp.status_code} {resp.text}"
    )


def json_or_fail(resp, label):
    try:
        return resp.json()
    except Exception as exc:
        pytest.fail(f"{label}: expected JSON, got {exc} {resp.text}")


def assert_error(resp, status, code, label):
    assert_status(resp, status, label)
    data = json_or_fail(resp, f"{label} JSON")
    assert data.get('error') == code, (
        f"{label}: expected error '{code}', got {data} {resp.text}"
    )
    return data


def maybe_auth_headers(token=None):
    headers = dict(HEADERS_FLUTTER)
    if token:
        headers["Authorization"] = f"Bearer {token}"
    return headers


def register_user(username=None, email=None, password="password123"):
    username = username or f"u_{uuid.uuid4().hex[:6]}"
    email = email or random_email()

    r = requests.post(
        api_url("/auth/register"),
        json={"username": username, "email": email, "password": password},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    return r


def login_user(email, password="password123"):
    r = requests.post(
        api_url("/auth/login"),
        json={"email": email, "password": password},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    return r


def auth_headers(token: str):
    return {"Authorization": f"Bearer {token}", **HEADERS_FLUTTER}


def register_and_token():
    email = random_email()

    r = register_user(email=email)
    assert r.status_code in (200, 201), f"Register failed: {r.status_code} {r.text}"

    token = r.json().get("token")
    assert token, f"No token returned on register: {r.text}"

    return {"email": email, "token": token, "user": r.json()}


def seed_user(role="user", reset=False, username=None, email=None, password=None):
    payload = {"type": "user", "role": role}
    if reset:
        payload["reset"] = True
    if username:
        payload["username"] = username
    if email:
        payload["email"] = email
    if password:
        payload["password"] = password

    r = requests.post(
        api_url("/test/seed"),
        json=payload,
        headers=SEED_HEADERS,
        timeout=TIMEOUT,
    )

    assert r.status_code in (200, 201), f"Seed user failed: {r.status_code} {r.text}"

    data = r.json()
    assert data.get("type") == "user"
    assert "token" in data

    return data


@pytest.fixture(scope="session", autouse=True)
def _maybe_reset_db():
    if not RESET_DB:
        return
    seed_user(role="admin", reset=True)


def seed_restaurant(name=None, owner_id=None):
    payload = {"type": "restaurant"}

    if name:
        payload["name"] = name

    if owner_id:
        payload["owner_id"] = owner_id

    r = requests.post(
        api_url("/test/seed"),
        json=payload,
        headers=SEED_HEADERS,
        timeout=TIMEOUT,
    )

    assert r.status_code in (200, 201), f"Seed restaurant failed: {r.status_code} {r.text}"

    data = r.json()
    assert data.get("type") == "restaurant"
    assert "id" in data

    return data


def restaurant_payload(overrides=None):
    payload = {
        "name": random_name(),
        "description": "Test restaurant created by API tests",
        "event_date": date.today().isoformat(),
        "average_price": "25",
        "latitude": "48.8566",
        "longitude": "2.3522",
        "contact_name": "Test Contact",
        "contact_email": random_email(),
    }

    if overrides:
        payload.update(overrides)

    return payload


def create_restaurant(token: str, overrides=None, photo_path=None, photo_mime=None):
    photo = photo_path or find_sample_image()
    payload = restaurant_payload(overrides)

    mime = photo_mime or (mimetypes.guess_type(photo.name)[0] or "image/jpeg")

    with photo.open("rb") as f:
        files = {"photo": (photo.name, f, mime)}

        r = requests.post(
            api_url("/restaurants"),
            data=payload,
            files=files,
            headers=auth_headers(token),
            timeout=TIMEOUT,
        )

    return r


def create_pending_restaurant(owner_token: str, overrides=None):
    rcreate = create_restaurant(owner_token, overrides=overrides)
    assert rcreate.status_code in (200, 201), f"Create restaurant failed: {rcreate.status_code} {rcreate.text}"

    created = rcreate.json()
    rid = created.get("id")
    assert rid, f"Missing restaurant id: {rcreate.text}"

    return created


def accept_restaurant(admin_token: str, restaurant_id: int):
    r = requests.post(
        api_url(f"/restaurants/{restaurant_id}/accept"),
        headers=auth_headers(admin_token),
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "accept restaurant")
    return r


def reject_restaurant(admin_token: str, restaurant_id: int, reason: str):
    r = requests.post(
        api_url(f"/restaurants/{restaurant_id}/reject"),
        json={"reason": reason},
        headers=auth_headers(admin_token),
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "reject restaurant")
    return r


def update_restaurant_status(admin_token: str, restaurant_id: int, status: str, reason=None):
    payload = {"status": status}
    if reason:
        payload["reason"] = reason

    return requests.put(
        api_url(f"/admin/restaurants/{restaurant_id}/status"),
        json=payload,
        headers=auth_headers(admin_token),
        timeout=TIMEOUT,
    )


def create_and_accept_restaurant(owner_token: str, admin_token: str):
    created = create_pending_restaurant(owner_token)
    accept_restaurant(admin_token, created["id"])
    return created


def create_reservation(token: str, restaurant_id: int, date_str: str, time_str: str):
    payload = {
        "restaurant_id": restaurant_id,
        "reservation_date": date_str,
        "reservation_time": time_str,
    }

    r = requests.post(
        api_url("/reservations"),
        json=payload,
        headers=auth_headers(token),
        timeout=TIMEOUT,
    )

    return r


def create_reservation_with_id(token: str, restaurant_id: int, date_str: str, time_str: str):
    r = create_reservation(token, restaurant_id, date_str, time_str)
    assert_status(r, 201, "create reservation")

    data = json_or_fail(r, "reservation create JSON")
    code = data.get("code")
    assert code, f"Missing reservation code: {r.text}"

    rlist = requests.get(
        api_url("/reservations/user"),
        headers=auth_headers(token),
        timeout=TIMEOUT,
    )
    assert_status(rlist, 200, "list user reservations")

    items = json_or_fail(rlist, "reservations list JSON")
    match = next((x for x in items if x.get("code") == code), None)
    assert match, "Reservation not found in user list"

    reservation_id = match.get("id")
    assert reservation_id, "Missing reservation id in user list"

    return reservation_id, code


def assert_pdf_response(resp, label):
    assert_status(resp, 200, label)
    content_type = resp.headers.get("Content-Type", "")
    assert "application/pdf" in content_type, f"{label}: expected PDF, got {content_type}"


# --- Tests ---

def test_api_auth_and_seed():
    r_bad_seed = requests.post(
        api_url("/test/seed"),
        json={"type": "user"},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(r_bad_seed, 401, "/test/seed without key")

    admin = seed_user(role="admin")
    assert admin.get("role") == "admin"

    email = random_email()

    r = register_user(email=email)
    assert r.status_code in (200, 201), f"Register failed: {r.status_code} {r.text}"

    data = json_or_fail(r, "register JSON")
    token = data.get("token")
    assert token

    r2 = login_user(email)
    assert_status(r2, 200, "login user")

    data2 = json_or_fail(r2, "login JSON")
    assert "token" in data2

    rlogout_get = requests.get(
        api_url("/auth/logout"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rlogout_get, 200, "logout GET")

    rlogout_post = requests.post(
        api_url("/auth/logout"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rlogout_post, 200, "logout POST")

    rme_unauth = requests.get(
        api_url("/auth/me"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rme_unauth, 401, "/auth/me without token")

    rme_user = requests.get(
        api_url("/auth/me"),
        headers=auth_headers(token),
        timeout=TIMEOUT,
    )
    assert_status(rme_user, 200, "/auth/me user")

    me = json_or_fail(rme_user, "me JSON")
    assert me.get("email") == email
    assert "role" in me

    rme_admin = requests.get(
        api_url("/auth/me"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rme_admin, 200, "/auth/me admin")


def test_api_auth_validation_errors():
    r_missing = requests.post(
        api_url("/auth/register"),
        json={},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_error(r_missing, 400, "missing_fields", "register missing fields")

    r_invalid_email = register_user(
        username="user_invalid",
        email="not-an-email",
        password="password123",
    )
    assert_error(r_invalid_email, 400, "invalid_email", "register invalid email")

    r_short_user = register_user(
        username="ab",
        email=random_email(),
        password="password123",
    )
    assert_error(r_short_user, 400, "username_too_short", "register username too short")

    r_short_pass = register_user(
        username=random_name("user"),
        email=random_email(),
        password="123",
    )
    assert_error(r_short_pass, 400, "password_too_short", "register password too short")

    dup_username = f"dup_{uuid.uuid4().hex[:6]}"
    dup_email = random_email()
    r_ok = register_user(username=dup_username, email=dup_email)
    assert r_ok.status_code in (200, 201), f"Register failed: {r_ok.status_code} {r_ok.text}"

    r_dup_user = register_user(username=dup_username, email=random_email())
    assert_error(r_dup_user, 400, "username_exists", "register duplicate username")

    r_dup_email = register_user(
        username=f"other_{uuid.uuid4().hex[:6]}",
        email=dup_email,
    )
    assert_error(r_dup_email, 400, "email_exists", "register duplicate email")

    r_login_missing = requests.post(
        api_url("/auth/login"),
        json={},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_error(r_login_missing, 400, "missing_fields", "login missing fields")

    r_login_invalid = login_user(random_email(), "wrongpass")
    assert_error(r_login_invalid, 401, "invalid_credentials", "login invalid credentials")


def test_api_auth_token_errors():
    r_bad_format = requests.get(
        api_url("/auth/me"),
        headers={"Authorization": "Token nope", **HEADERS_FLUTTER},
        timeout=TIMEOUT,
    )
    assert_error(r_bad_format, 401, "invalid_format", "auth me invalid format")

    r_bad_token = requests.get(
        api_url("/auth/me"),
        headers={"Authorization": "Bearer invalidtoken", **HEADERS_FLUTTER},
        timeout=TIMEOUT,
    )
    assert_error(r_bad_token, 401, "invalid_token", "auth me invalid token")


def test_api_seed_invalid_and_unknown_type():
    r_wrong_key = requests.post(
        api_url("/test/seed"),
        json={"type": "user"},
        headers={"X-Seed-Key": "wrong", **HEADERS_FLUTTER},
        timeout=TIMEOUT,
    )
    assert_error(r_wrong_key, 401, "invalid_seed_key", "seed wrong key")

    r_unknown = requests.post(
        api_url("/test/seed"),
        json={"type": "unknown"},
        headers=SEED_HEADERS,
        timeout=TIMEOUT,
    )
    assert_error(r_unknown, 400, "unknown_type", "seed unknown type")


def test_api_pdf_missing_id():
    r = requests.get(
        api_url("/pdf/restaurant"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_error(r, 400, "missing_id", "pdf missing id")


def test_api_restaurant_search_empty_query():
    r = requests.get(
        api_url("/restaurants/search"),
        params={"q": ""},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "restaurants search empty")
    data = json_or_fail(r, "restaurants search empty JSON")
    assert isinstance(data, list)
    assert len(data) == 0


def test_api_unknown_route():
    r = requests.get(
        api_url("/does-not-exist"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(r, 404, "unknown route")
    data = json_or_fail(r, "unknown route JSON")
    assert data.get("error") == "Route not found"


def test_api_restaurants_public_endpoints_admin_user():
    admin = seed_user(role="admin")
    owner = register_and_token()

    created = create_pending_restaurant(owner["token"])
    rid = created.get("id")
    name = created.get("name") or created.get("restaurant", {}).get("name")
    assert name

    rlist_before = requests.get(
        api_url("/restaurants"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rlist_before, 200, "/restaurants public list")
    data_before = json_or_fail(rlist_before, "restaurants list JSON")
    assert all(str(item.get("id")) != str(rid) for item in data_before)

    accept_restaurant(admin["token"], rid)

    for label, token in (
        ("public", None),
        ("user", owner["token"]),
        ("admin", admin["token"]),
    ):
        rlist = requests.get(
            api_url("/restaurants"),
            headers=maybe_auth_headers(token),
            timeout=TIMEOUT,
        )
        assert_status(rlist, 200, f"/restaurants list {label}")
        items = json_or_fail(rlist, f"restaurants list JSON {label}")
        assert isinstance(items, list)
        assert any(str(item.get("id")) == str(rid) for item in items)

        rsearch = requests.get(
            api_url("/restaurants/search"),
            params={"q": name},
            headers=maybe_auth_headers(token),
            timeout=TIMEOUT,
        )
        assert_status(rsearch, 200, f"/restaurants/search {label}")
        results = json_or_fail(rsearch, f"restaurants search JSON {label}")
        assert isinstance(results, list)
        assert any(name.lower() in (item.get("name", "").lower()) for item in results)

        rshow = requests.get(
            api_url(f"/restaurants/{rid}"),
            headers=maybe_auth_headers(token),
            timeout=TIMEOUT,
        )
        assert_status(rshow, 200, f"/restaurants/:id show {label}")
        show = json_or_fail(rshow, f"restaurant show JSON {label}")
        assert str(show.get("id")) == str(rid)

    rpdf_public = requests.get(
        api_url(f"/restaurants/{rid}/pdf"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_pdf_response(rpdf_public, "/restaurants/:id/pdf public")

    rpdf_admin = requests.get(
        api_url(f"/restaurants/{rid}/pdf"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_pdf_response(rpdf_admin, "/restaurants/:id/pdf admin")

    rpdf_query = requests.get(
        api_url("/pdf/restaurant"),
        params={"id": rid},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_pdf_response(rpdf_query, "/pdf/restaurant?id")


def test_api_restaurant_validation_errors():
    owner = register_and_token()

    r_missing = create_restaurant(owner["token"], overrides={"description": ""})
    assert_error(r_missing, 400, "missing_fields", "create restaurant missing fields")

    r_invalid_email = create_restaurant(
        owner["token"],
        overrides={"contact_email": "not-an-email"},
    )
    assert_error(r_invalid_email, 400, "invalid_email", "create restaurant invalid email")

    payload = restaurant_payload()
    r_missing_photo = requests.post(
        api_url("/restaurants"),
        data=payload,
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_error(r_missing_photo, 400, "missing_photo", "create restaurant missing photo")

    r_invalid_photo = create_restaurant(owner["token"], photo_mime="text/plain")
    assert_error(r_invalid_photo, 400, "invalid_photo_type", "create restaurant invalid photo")


def test_api_restaurant_update_invalid_photo_type():
    owner = register_and_token()
    created = create_pending_restaurant(owner["token"])
    rid = created["id"]

    photo = find_sample_image()
    with photo.open("rb") as f:
        files = {"photo": (photo.name, f, "text/plain")}
        r = requests.post(
            api_url(f"/restaurants/{rid}"),
            data={"name": random_name("updated")},
            files=files,
            headers=auth_headers(owner["token"]),
            timeout=TIMEOUT,
        )

    assert_error(r, 400, "invalid_photo_type", "update restaurant invalid photo")


def test_api_restaurant_cancel_invalid_status():
    admin = seed_user(role="admin")
    owner = register_and_token()

    created = create_pending_restaurant(owner["token"])
    rid = created["id"]
    accept_restaurant(admin["token"], rid)

    r = requests.post(
        api_url(f"/restaurants/{rid}/cancel"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_error(r, 400, "invalid_status", "cancel restaurant invalid status")


def test_api_restaurant_update_post_multipart():
    owner = register_and_token()
    created = create_pending_restaurant(owner["token"])
    rid = created["id"]
    new_name = random_name("updated_post")

    r = requests.post(
        api_url(f"/restaurants/{rid}"),
        data={"name": new_name},
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "update restaurant post")
    data = json_or_fail(r, "update restaurant post JSON")
    assert data.get("restaurant", {}).get("name") == new_name


def test_api_restaurant_resubmit_after_reject():
    admin = seed_user(role="admin")
    owner = register_and_token()
    created = create_pending_restaurant(owner["token"])
    rid = created["id"]

    reject_restaurant(admin["token"], rid, "Incomplete")

    r = requests.put(
        api_url(f"/restaurants/{rid}"),
        json={"description": "Resubmitted after reject"},
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "restaurant resubmit")
    payload = json_or_fail(r, "restaurant resubmit JSON")
    restaurant = payload.get("restaurant", {})
    assert restaurant.get("status") == "pending"
    assert not restaurant.get("rejection_reason")


def test_api_restaurant_cancel_put():
    owner = register_and_token()
    created = create_pending_restaurant(owner["token"])
    rid = created["id"]

    r = requests.put(
        api_url(f"/restaurants/{rid}/cancel"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "cancel restaurant put")


def test_api_admin_status_patch():
    admin = seed_user(role="admin")
    owner = register_and_token()
    created = create_pending_restaurant(owner["token"])
    rid = created["id"]

    r = requests.patch(
        api_url(f"/admin/restaurants/{rid}/status"),
        json={"status": "accepted"},
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(r, 200, "admin status patch")


def test_api_restaurant_crud_and_access_controls():
    admin = seed_user(role="admin")
    owner = register_and_token()
    other = register_and_token()

    created_owner = create_pending_restaurant(owner["token"])
    rid_owner = created_owner["id"]

    created_admin = create_pending_restaurant(admin["token"])
    rid_admin = created_admin["id"]

    rmine_owner = requests.get(
        api_url("/restaurants/mine"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rmine_owner, 200, "/restaurants/mine owner")
    mine_owner = json_or_fail(rmine_owner, "mine owner JSON")
    assert any(str(item.get("id")) == str(rid_owner) for item in mine_owner)

    rmine_admin = requests.get(
        api_url("/restaurants/mine"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rmine_admin, 200, "/restaurants/mine admin")
    mine_admin = json_or_fail(rmine_admin, "mine admin JSON")
    assert any(str(item.get("id")) == str(rid_admin) for item in mine_admin)

    rmine_unauth = requests.get(
        api_url("/restaurants/mine"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rmine_unauth, 401, "/restaurants/mine without token")

    rupdate_owner = requests.put(
        api_url(f"/restaurants/{rid_owner}"),
        json={
            "name": random_name("updated"),
            "description": "Updated description from API tests",
            "average_price": 42,
        },
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rupdate_owner, 200, "update restaurant owner")

    rupdate_other = requests.put(
        api_url(f"/restaurants/{rid_owner}"),
        json={"name": random_name("blocked")},
        headers=auth_headers(other["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rupdate_other, 403, "update restaurant other user")

    rupdate_admin = requests.put(
        api_url(f"/restaurants/{rid_owner}"),
        json={"name": random_name("admin")},
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rupdate_admin, 200, "update restaurant admin")

    cancel_owner_id = create_pending_restaurant(owner["token"])["id"]
    rcancel_owner = requests.post(
        api_url(f"/restaurants/{cancel_owner_id}/cancel"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rcancel_owner, 200, "cancel restaurant owner")

    cancel_other_id = create_pending_restaurant(owner["token"])["id"]
    rcancel_other = requests.post(
        api_url(f"/restaurants/{cancel_other_id}/cancel"),
        headers=auth_headers(other["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rcancel_other, 403, "cancel restaurant other user")

    cancel_admin_id = create_pending_restaurant(owner["token"])["id"]
    rcancel_admin = requests.post(
        api_url(f"/restaurants/{cancel_admin_id}/cancel"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rcancel_admin, 200, "cancel restaurant admin")

    delete_other_id = create_pending_restaurant(owner["token"])["id"]
    rdelete_other = requests.delete(
        api_url(f"/restaurants/{delete_other_id}"),
        headers=auth_headers(other["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_other, 403, "delete restaurant other user")

    delete_owner_id = create_pending_restaurant(owner["token"])["id"]
    rdelete_owner = requests.delete(
        api_url(f"/restaurants/{delete_owner_id}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_owner, 200, "delete restaurant owner")

    delete_admin_id = create_pending_restaurant(owner["token"])["id"]
    rdelete_admin = requests.delete(
        api_url(f"/restaurants/{delete_admin_id}"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_admin, 200, "delete restaurant admin")


def test_api_admin_endpoints_access_controls():
    admin = seed_user(role="admin")
    owner = register_and_token()

    pending = create_pending_restaurant(owner["token"])
    pending_id = pending["id"]

    rpending_admin = requests.get(
        api_url("/restaurants/pending"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rpending_admin, 200, "/restaurants/pending admin")
    pending_list = json_or_fail(rpending_admin, "pending list JSON")
    assert any(str(item.get("id")) == str(pending_id) for item in pending_list)

    rpending_user = requests.get(
        api_url("/restaurants/pending"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rpending_user, 403, "/restaurants/pending user")

    rpending_unauth = requests.get(
        api_url("/restaurants/pending"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rpending_unauth, 401, "/restaurants/pending without token")

    raccept_user = requests.post(
        api_url(f"/restaurants/{pending_id}/accept"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(raccept_user, 403, "/restaurants/:id/accept user")

    raccept_admin = requests.post(
        api_url(f"/restaurants/{pending_id}/accept"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(raccept_admin, 200, "/restaurants/:id/accept admin")

    pending_reject = create_pending_restaurant(owner["token"])
    pending_reject_id = pending_reject["id"]

    rreject_no_reason = requests.post(
        api_url(f"/restaurants/{pending_reject_id}/reject"),
        json={},
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rreject_no_reason, 400, "/restaurants/:id/reject missing reason")

    rreject_user = requests.post(
        api_url(f"/restaurants/{pending_reject_id}/reject"),
        json={"reason": "No access"},
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rreject_user, 403, "/restaurants/:id/reject user")

    rreject_admin = requests.post(
        api_url(f"/restaurants/{pending_reject_id}/reject"),
        json={"reason": "Incomplete info"},
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rreject_admin, 200, "/restaurants/:id/reject admin")

    pending_status = create_pending_restaurant(owner["token"])
    pending_status_id = pending_status["id"]

    rstatus_user = update_restaurant_status(owner["token"], pending_status_id, "accepted")
    assert_status(rstatus_user, 403, "/admin/restaurants/:id/status user")

    rstatus_invalid = update_restaurant_status(admin["token"], pending_status_id, "unknown")
    assert_status(rstatus_invalid, 400, "/admin/restaurants/:id/status invalid")

    pending_status_ok = create_pending_restaurant(owner["token"])
    pending_status_ok_id = pending_status_ok["id"]

    rstatus_admin = update_restaurant_status(admin["token"], pending_status_ok_id, "accepted")
    assert_status(rstatus_admin, 200, "/admin/restaurants/:id/status admin")

    pending_status_reject = create_pending_restaurant(owner["token"])
    pending_status_reject_id = pending_status_reject["id"]

    rstatus_reject_missing = update_restaurant_status(admin["token"], pending_status_reject_id, "rejected")
    assert_status(rstatus_reject_missing, 400, "/admin/restaurants/:id/status missing reason")

    rstatus_reject = update_restaurant_status(
        admin["token"],
        pending_status_reject_id,
        "rejected",
        reason="Not ready",
    )
    assert_status(rstatus_reject, 200, "/admin/restaurants/:id/status rejected")


def test_api_not_found_responses():
    admin = seed_user(role="admin")
    owner = register_and_token()

    created = create_pending_restaurant(owner["token"])
    rid = created["id"]

    rdelete = requests.delete(
        api_url(f"/restaurants/{rid}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete, 200, "delete restaurant for not found")

    rshow = requests.get(
        api_url(f"/restaurants/{rid}"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_error(rshow, 404, "not_found", "restaurant show not found")

    rpdf = requests.get(
        api_url(f"/restaurants/{rid}/pdf"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_error(rpdf, 404, "not_found", "restaurant pdf not found")

    rbookings = requests.get(
        api_url(f"/restaurants/{rid}/bookings"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_error(rbookings, 404, "not_found", "restaurant bookings not found")

    rres_list = requests.get(
        api_url(f"/reservations/restaurant/{rid}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_error(rres_list, 404, "not_found", "reservations restaurant not found")

    rstatus = update_restaurant_status(admin["token"], rid, "accepted")
    assert_error(rstatus, 404, "not_found", "admin status not found")

    rdelete_res = requests.delete(
        api_url("/reservations/999999"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_error(rdelete_res, 404, "not_found", "reservation delete not found")


def test_api_reservation_validation_errors():
    user = register_and_token()

    r_missing = requests.post(
        api_url("/reservations"),
        json={},
        headers=auth_headers(user["token"]),
        timeout=TIMEOUT,
    )
    assert_error(r_missing, 400, "missing_fields", "reservation missing fields")


def test_api_reservations_and_bookings_access_controls():
    admin = seed_user(role="admin")
    owner = register_and_token()
    customer = register_and_token()
    other = register_and_token()

    restaurant = create_and_accept_restaurant(owner["token"], admin["token"])
    restaurant_id = restaurant["id"]

    date_str = (date.today() + timedelta(days=7)).isoformat()

    rcreate_unauth = requests.post(
        api_url("/reservations"),
        json={
            "restaurant_id": restaurant_id,
            "reservation_date": date_str,
            "reservation_time": "19:00",
        },
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rcreate_unauth, 401, "/reservations without token")

    reservation_id, code = create_reservation_with_id(
        customer["token"],
        restaurant_id,
        date_str,
        "20:00",
    )

    rlist_user = requests.get(
        api_url("/reservations/user"),
        headers=auth_headers(customer["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rlist_user, 200, "/reservations/user customer")
    items_user = json_or_fail(rlist_user, "reservations/user JSON")
    assert any(item.get("code") == code for item in items_user)

    rlist_admin = requests.get(
        api_url("/reservations/user"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rlist_admin, 200, "/reservations/user admin")

    rlist_unauth = requests.get(
        api_url("/reservations/user"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rlist_unauth, 401, "/reservations/user without token")

    rlist_rest_owner = requests.get(
        api_url(f"/reservations/restaurant/{restaurant_id}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rlist_rest_owner, 200, "/reservations/restaurant owner")
    items_rest_owner = json_or_fail(rlist_rest_owner, "reservations/restaurant JSON")
    assert any(item.get("code") == code for item in items_rest_owner)

    rlist_rest_admin = requests.get(
        api_url(f"/reservations/restaurant/{restaurant_id}"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rlist_rest_admin, 200, "/reservations/restaurant admin")

    rlist_rest_other = requests.get(
        api_url(f"/reservations/restaurant/{restaurant_id}"),
        headers=auth_headers(other["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rlist_rest_other, 403, "/reservations/restaurant other user")

    rlist_rest_unauth = requests.get(
        api_url(f"/reservations/restaurant/{restaurant_id}"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rlist_rest_unauth, 401, "/reservations/restaurant without token")

    rbookings_owner = requests.get(
        api_url(f"/restaurants/{restaurant_id}/bookings"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rbookings_owner, 200, "/restaurants/:id/bookings owner")
    bookings_payload = json_or_fail(rbookings_owner, "bookings JSON")
    assert "restaurant" in bookings_payload and "bookings" in bookings_payload
    assert any(item.get("code") == code for item in bookings_payload.get("bookings", []))

    rbookings_admin = requests.get(
        api_url(f"/restaurants/{restaurant_id}/bookings"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rbookings_admin, 200, "/restaurants/:id/bookings admin")

    rbookings_other = requests.get(
        api_url(f"/restaurants/{restaurant_id}/bookings"),
        headers=auth_headers(other["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rbookings_other, 403, "/restaurants/:id/bookings other user")

    rbookings_unauth = requests.get(
        api_url(f"/restaurants/{restaurant_id}/bookings"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rbookings_unauth, 401, "/restaurants/:id/bookings without token")

    reservation_id_2, _ = create_reservation_with_id(
        customer["token"],
        restaurant_id,
        date_str,
        "20:30",
    )

    rdelete_unauth = requests.delete(
        api_url(f"/reservations/{reservation_id_2}"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )
    assert_status(rdelete_unauth, 401, "/reservations/:id delete without token")

    rdelete_other = requests.delete(
        api_url(f"/reservations/{reservation_id_2}"),
        headers=auth_headers(other["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_other, 403, "/reservations/:id delete other user")

    rdelete_owner = requests.delete(
        api_url(f"/reservations/{reservation_id_2}"),
        headers=auth_headers(customer["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_owner, 200, "/reservations/:id delete reservation owner")

    reservation_id_3, _ = create_reservation_with_id(
        customer["token"],
        restaurant_id,
        date_str,
        "21:00",
    )

    rdelete_rest_owner = requests.delete(
        api_url(f"/reservations/{reservation_id_3}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_rest_owner, 200, "/reservations/:id delete restaurant owner")

    reservation_id_4, _ = create_reservation_with_id(
        customer["token"],
        restaurant_id,
        date_str,
        "21:30",
    )

    rdelete_admin = requests.delete(
        api_url(f"/reservations/{reservation_id_4}"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert_status(rdelete_admin, 200, "/reservations/:id delete admin")


def test_api_protected_endpoints_require_token():
    admin = seed_user(role="admin")
    owner = register_and_token()

    restaurant = create_pending_restaurant(owner["token"])
    rid = restaurant["id"]

    accepted = create_and_accept_restaurant(owner["token"], admin["token"])
    accepted_id = accepted["id"]

    date_str = (date.today() + timedelta(days=7)).isoformat()
    reservation_id, _ = create_reservation_with_id(owner["token"], accepted_id, date_str, "22:00")

    cases = [
        ("GET", "/auth/me", None),
        ("GET", "/restaurants/mine", None),
        ("GET", "/restaurants/pending", None),
        ("GET", f"/restaurants/{accepted_id}/bookings", None),
        ("POST", "/restaurants", {}),
        ("PUT", f"/restaurants/{rid}", {"json": {"name": random_name("blocked")}}),
        ("DELETE", f"/restaurants/{rid}", None),
        ("POST", f"/restaurants/{rid}/cancel", None),
        ("POST", f"/restaurants/{rid}/accept", None),
        ("POST", f"/restaurants/{rid}/reject", {"json": {"reason": "no"}}),
        ("PUT", f"/admin/restaurants/{rid}/status", {"json": {"status": "accepted"}}),
        ("POST", "/reservations", {"json": {"restaurant_id": accepted_id, "reservation_date": date_str, "reservation_time": "22:30"}}),
        ("GET", "/reservations/user", None),
        ("GET", f"/reservations/restaurant/{accepted_id}", None),
        ("DELETE", f"/reservations/{reservation_id}", None),
    ]

    for method, path, payload in cases:
        payload = payload or {}
        r = requests.request(
            method,
            api_url(path),
            headers=HEADERS_FLUTTER,
            timeout=TIMEOUT,
            **payload,
        )
        assert_status(r, 401, f"{method} {path} requires token")

