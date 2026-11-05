"""cPanel startup file; application root must be outside public_html."""
import os
import sys

sys.path.insert(0, os.path.dirname(__file__))
from app import application
