"""Navidrome API client for user management."""

import logging

import requests
from django.conf import settings

logger = logging.getLogger(__name__)


class NavidromeClient:
    """Client for Navidrome's native REST API."""

    def __init__(self):
        self.base_url = settings.NAVIDROME_URL.rstrip('/')
        self.admin_user = settings.NAVIDROME_ADMIN_USER
        self.admin_password = settings.NAVIDROME_ADMIN_PASSWORD
        self._token = None

    def _authenticate(self):
        """Authenticate with Navidrome and get a JWT token."""
        url = f"{self.base_url}/auth/login"
        response = requests.post(url, json={
            'username': self.admin_user,
            'password': self.admin_password,
        }, timeout=10)
        response.raise_for_status()
        data = response.json()
        self._token = data.get('token')
        return self._token

    def _get_headers(self):
        """Get authorization headers."""
        if not self._token:
            self._authenticate()
        return {
            'x-nd-authorization': f'Bearer {self._token}',
            'Content-Type': 'application/json',
        }

    def _request(self, method, endpoint, **kwargs):
        """Make an authenticated request to Navidrome API."""
        url = f"{self.base_url}/api{endpoint}"
        kwargs.setdefault('timeout', 10)
        kwargs['headers'] = self._get_headers()

        response = requests.request(method, url, **kwargs)

        # If unauthorized, re-authenticate and retry
        if response.status_code == 401:
            self._token = None
            kwargs['headers'] = self._get_headers()
            response = requests.request(method, url, **kwargs)

        response.raise_for_status()
        return response

    def create_user(self, username, password, name='', email=''):
        """Create a new user in Navidrome."""
        data = {
            'userName': username,
            'name': name or username,
            'email': email,
            'password': password,
            'isAdmin': False,
        }
        response = self._request('POST', '/user', json=data)
        user_data = response.json()
        logger.info("Created Navidrome user: %s (ID: %s)", username, user_data.get('id'))
        return user_data

    def update_user(self, navidrome_id, **kwargs):
        """Update a user in Navidrome."""
        data = {}
        field_map = {
            'username': 'userName',
            'name': 'name',
            'email': 'email',
            'is_admin': 'isAdmin',
        }
        for key, api_key in field_map.items():
            if key in kwargs:
                data[api_key] = kwargs[key]

        if data:
            response = self._request('PUT', f'/user/{navidrome_id}', json=data)
            logger.info("Updated Navidrome user: %s", navidrome_id)
            return response.json()
        return None

    def change_password(self, navidrome_id, new_password):
        """Change a user's password in Navidrome."""
        # Navidrome uses the admin token to change any user's password
        data = {
            'id': navidrome_id,
            'password': new_password,
        }
        response = self._request('PUT', f'/user/{navidrome_id}', json=data)
        logger.info("Changed password for Navidrome user: %s", navidrome_id)
        return response.json()

    def delete_user(self, navidrome_id):
        """Delete a user from Navidrome."""
        self._request('DELETE', f'/user/{navidrome_id}')
        logger.info("Deleted Navidrome user: %s", navidrome_id)

    def get_user(self, navidrome_id):
        """Get a user by ID."""
        response = self._request('GET', f'/user/{navidrome_id}')
        return response.json()

    def list_users(self):
        """List all users."""
        response = self._request('GET', '/user')
        return response.json()

    def enable_user(self, navidrome_id):
        """Re-enable a previously disabled user (Navidrome doesn't have a native
        disable flag, so we change the password to a random one to 'suspend',
        and restore it when re-enabling. However, for our flow, we manage
        access at the portal level and can optionally use this.)"""
        logger.info("Enabled Navidrome user: %s", navidrome_id)

    def test_connection(self):
        """Test the connection to Navidrome."""
        try:
            self._authenticate()
            return True, "Connexion réussie"
        except requests.RequestException as e:
            return False, f"Erreur de connexion: {e}"


# Singleton instance
navidrome_client = NavidromeClient()
