import os
import uuid
import requests
import pytest

BASE_URL = os.environ.get("API_BASE_URL", "http://localhost:8080/api")


def random_email():
    return f"test+{uuid.uuid4().hex[:8]}@example.com"


def register_user(username=None, email=None, password="password123"):
    username = username or f"u_{uuid.uuid4().hex[:6]}"
    email = email or random_email()
    r = requests.post(f"{BASE_URL}/auth/register", json={
        "username": username,
        "email": email,
        "password": password
    })
    return r


def login_user(email, password="password123"):
    r = requests.post(f"{BASE_URL}/auth/login", json={
        "email": email,
        "password": password
    })
    return r


def get_auth_token(email, password="password123"):
    r = login_user(email, password)
    r.raise_for_status()
    return r.json().get("token")


def test_get_restaurants():
    r = requests.get(f"{BASE_URL}/restaurants")
    assert r.status_code == 200, f"Expected 200 OK, got {r.status_code}: {r.text}"
    data = r.json()
    assert isinstance(data, list)


def test_register_and_login():
    email = random_email()
    r = register_user(email=email)
    assert r.status_code in (200, 201), f"Register failed: {r.status_code} {r.text}"
    json = r.json()
    assert "token" in json, f"No token returned on register: {json}"

    # login
    r2 = login_user(email)
    assert r2.status_code == 200, f"Login failed: {r2.status_code} {r2.text}"
    j2 = r2.json()
    assert "token" in j2


@pytest.mark.parametrize("date,time", [("2026-01-01", "20:00")])
def test_create_reservation_if_restaurant_exists(date, time):
    # ensure we have a user
    email = random_email()
    r = register_user(email=email)
    assert r.status_code in (200, 201)
    token = r.json().get("token")
    assert token

    # get restaurants
    r = requests.get(f"{BASE_URL}/restaurants")
    assert r.status_code == 200
    data = r.json()
    if not data:
        # Try to seed a restaurant for tests using the test seeder endpoint
        seed_key = os.environ.get("SEED_KEY", "devseed")
        rseed = requests.post(f"{BASE_URL}/test/seed", json={"type": "restaurant"}, headers={"X-Seed-Key": seed_key})
        assert rseed.status_code in (200, 201), f"Seeding failed: {rseed.status_code} {rseed.text}"
        r = requests.get(f"{BASE_URL}/restaurants")
        assert r.status_code == 200
        data = r.json()
        if not data:
            pytest.skip("Seeding did not create a restaurant")

    restaurant_id = data[0]["id"]

    headers = {"Authorization": f"Bearer {token}"}
    payload = {
        "restaurant_id": str(restaurant_id),
        "reservation_date": date,
        "reservation_time": time
    }

    r = requests.post(f"{BASE_URL}/reservations", data=payload, headers=headers)
    assert r.status_code == 201, f"Create reservation failed: {r.status_code} {r.text}"
    j = r.json()
    assert j.get("status") == "created"
    assert "code" in j


def test_user_reservations_list():
    # create user and optionally reservation if possible
    email = random_email()
    r = register_user(email=email)
    assert r.status_code in (200, 201)
    token = r.json().get("token")
    assert token

    headers = {"Authorization": f"Bearer {token}"}
    r = requests.get(f"{BASE_URL}/reservations/user", headers=headers)
    assert r.status_code == 200
    assert isinstance(r.json(), list)
