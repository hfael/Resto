import os
import uuid
import mimetypes
from datetime import date, timedelta
from pathlib import Path

import pytest
import requests

BASE_URL = os.environ.get("API_BASE_URL", "http://localhost:8080/api")
TIMEOUT = int(os.environ.get("API_TIMEOUT", "10"))
HEADERS_FLUTTER = {"User-Agent": "Dart/Flutter"}
SEED_KEY = os.environ.get("SEED_KEY", "devseed")
SEED_HEADERS = {"X-Seed-Key": SEED_KEY, **HEADERS_FLUTTER}


def api_url(path: str) -> str:
    return f"{BASE_URL}{path}"


def random_email():
    return f"test+{uuid.uuid4().hex[:8]}@example.com"


def random_name(prefix="resto"):
    return f"{prefix}_{uuid.uuid4().hex[:6]}"


def find_sample_image():
    root = Path(__file__).resolve().parents[1]
    uploads = root / "public" / "uploads"
    candidates = []

    if uploads.exists():
        candidates += list(uploads.glob("*.jpg"))
        candidates += list(uploads.glob("*.jpeg"))
        candidates += list(uploads.glob("*.png"))
        candidates += list(uploads.glob("*.webp"))

    return candidates[0] if candidates else None


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


def seed_user(role="user"):
    r = requests.post(
        api_url("/test/seed"),
        json={"type": "user", "role": role},
        headers=SEED_HEADERS,
        timeout=TIMEOUT,
    )

    assert r.status_code in (200, 201), f"Seed user failed: {r.status_code} {r.text}"

    data = r.json()
    assert data.get("type") == "user"
    assert "token" in data

    return data


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


def create_restaurant(token: str, overrides=None):
    photo = find_sample_image()

    if not photo:
        pytest.skip("No image file found to upload for /restaurants")

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

    mime = mimetypes.guess_type(photo.name)[0] or "image/jpeg"

    with photo.open("rb") as f:
        files = {
            "photo": (photo.name, f, mime)
        }

        r = requests.post(
            api_url("/restaurants"),
            data=payload,
            files=files,
            headers=auth_headers(token),
            timeout=TIMEOUT,
        )

    return r


def create_reservation(token: str, restaurant_id: int, date_str: str, time_str: str):
    payload = {
        "restaurant_id": str(restaurant_id),
        "reservation_date": date_str,
        "reservation_time": time_str,
    }

    r = requests.post(
        api_url("/reservations"),
        data=payload,
        headers=auth_headers(token),
        timeout=TIMEOUT,
    )

    return r


def test_api_register_login_me():
    email = random_email()

    r = register_user(email=email)
    assert r.status_code in (200, 201), f"Register failed: {r.status_code} {r.text}"

    data = r.json()
    assert "token" in data

    r2 = login_user(email)
    assert r2.status_code == 200, f"Login failed: {r2.status_code} {r2.text}"

    data2 = r2.json()
    assert "token" in data2

    rme = requests.get(
        api_url("/auth/me"),
        headers=auth_headers(data2["token"]),
        timeout=TIMEOUT,
    )

    assert rme.status_code == 200, f"/auth/me failed: {rme.status_code} {rme.text}"

    me = rme.json()

    assert me.get("email") == email
    assert "role" in me

def test_api_restaurants_list_and_search():
    admin = seed_user(role="admin")
    owner = register_and_token()

    rcreate = create_restaurant(owner["token"])
    assert rcreate.status_code in (200, 201)

    created = rcreate.json()
    rid = created.get("id")
    name = created.get("name")
    assert rid

    raccept = requests.post(
        api_url(f"/restaurants/{rid}/accept"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )
    assert raccept.status_code == 200

    r = requests.get(
        api_url("/restaurants"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )

    assert r.status_code == 200
    data = r.json()
    assert isinstance(data, list)

    rsearch = requests.get(
        api_url("/restaurants/search"),
        params={"q": name},
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )

    assert rsearch.status_code == 200

    results = rsearch.json()
    assert isinstance(results, list)

    assert any(name.lower() in (item.get("name", "").lower()) for item in results)


def test_api_restaurant_mine_update_cancel_delete():
    user = register_and_token()

    rcreate = create_restaurant(user["token"])
    assert rcreate.status_code in (200, 201)

    rid = rcreate.json().get("id")
    assert rid

    rmine = requests.get(
        api_url("/restaurants/mine"),
        headers=auth_headers(user["token"]),
        timeout=TIMEOUT,
    )

    assert rmine.status_code == 200

    mine = rmine.json()
    assert any(str(item.get("id")) == str(rid) for item in mine)

    rupdate = requests.put(
        api_url(f"/restaurants/{rid}"),
        json={
            "name": random_name("updated"),
            "description": "Updated description from API tests",
            "average_price": 42,
        },
        headers=auth_headers(user["token"]),
        timeout=TIMEOUT,
    )

    assert rupdate.status_code == 200

    rcancel = requests.post(
        api_url(f"/restaurants/{rid}/cancel"),
        headers=auth_headers(user["token"]),
        timeout=TIMEOUT,
    )

    assert rcancel.status_code == 200
    assert rcancel.json().get("status") == "cancelled"

    rdelete = requests.delete(
        api_url(f"/restaurants/{rid}"),
        headers=auth_headers(user["token"]),
        timeout=TIMEOUT,
    )

    assert rdelete.status_code == 200

    rshow = requests.get(
        api_url(f"/restaurants/{rid}"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    )

    assert rshow.status_code == 404


def test_api_admin_pending_accept_reject():
    admin = seed_user(role="admin")

    owner_a = register_and_token()
    owner_b = register_and_token()

    r_a = create_restaurant(owner_a["token"])
    r_b = create_restaurant(owner_b["token"])

    rid_a = r_a.json().get("id")
    rid_b = r_b.json().get("id")

    rpending = requests.get(
        api_url("/restaurants/pending"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )

    assert rpending.status_code == 200

    pending = rpending.json()

    assert any(str(item.get("id")) == str(rid_a) for item in pending)
    assert any(str(item.get("id")) == str(rid_b) for item in pending)

    raccept = requests.post(
        api_url(f"/restaurants/{rid_a}/accept"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )

    assert raccept.status_code == 200

    rreject = requests.post(
        api_url(f"/restaurants/{rid_b}/reject"),
        json={"reason": "Incomplete info"},
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )

    assert rreject.status_code == 200

    show_a = requests.get(
        api_url(f"/restaurants/{rid_a}"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    ).json()

    show_b = requests.get(
        api_url(f"/restaurants/{rid_b}"),
        headers=HEADERS_FLUTTER,
        timeout=TIMEOUT,
    ).json()

    assert show_a.get("status") == "accepted"
    assert show_a.get("owner_id") is not None
    assert show_b.get("status") == "rejected"
    assert show_b.get("rejection_reason") == "Incomplete info"


def test_api_reservations_and_bookings():
    admin = seed_user(role="admin")

    owner = register_and_token()
    customer = register_and_token()

    rcreate = create_restaurant(owner["token"])
    assert rcreate.status_code in (200, 201)

    restaurant_id = rcreate.json().get("id")
    assert restaurant_id

    raccept = requests.post(
        api_url(f"/restaurants/{restaurant_id}/accept"),
        headers=auth_headers(admin["token"]),
        timeout=TIMEOUT,
    )

    assert raccept.status_code == 200

    date_str = (date.today() + timedelta(days=7)).isoformat()
    time_str = "20:00"

    r = create_reservation(customer["token"], restaurant_id, date_str, time_str)

    assert r.status_code == 201

    created = r.json()
    assert created.get("status") == "created"

    code = created.get("code")
    assert code

    rlist = requests.get(
        api_url("/reservations/user"),
        headers=auth_headers(customer["token"]),
        timeout=TIMEOUT,
    )

    assert rlist.status_code == 200

    items = rlist.json()
    match = next((x for x in items if x.get("code") == code), None)

    assert match

    reservation_id = match.get("id")
    assert reservation_id

    rlist_owner = requests.get(
        api_url(f"/reservations/restaurant/{restaurant_id}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )

    assert rlist_owner.status_code == 200

    owner_items = rlist_owner.json()
    owner_match = next((x for x in owner_items if x.get("code") == code), None)

    assert owner_match
    assert "username" in owner_match and "email" in owner_match

    rbookings = requests.get(
        api_url(f"/restaurants/{restaurant_id}/bookings"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )

    assert rbookings.status_code == 200

    bookings = rbookings.json()
    assert "restaurant" in bookings and "bookings" in bookings

    rdel = requests.delete(
        api_url(f"/reservations/{reservation_id}"),
        headers=auth_headers(owner["token"]),
        timeout=TIMEOUT,
    )

    assert rdel.status_code == 200
    assert rdel.json().get("status") == "deleted"