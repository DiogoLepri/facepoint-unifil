#!/bin/bash

# DeepFace Server Startup Script
echo "Starting DeepFace API Server..."

# Check if Python 3 is available
if ! command -v python3 &> /dev/null; then
    echo "Error: Python 3 is not installed or not in PATH"
    exit 1
fi

# Check if pip is available
if ! command -v pip3 &> /dev/null; then
    echo "Error: pip3 is not installed or not in PATH"
    exit 1
fi

# Create virtual environment if it doesn't exist
if [ ! -d "deepface_env" ]; then
    echo "Creating Python virtual environment..."
    python3 -m venv deepface_env
fi

# Activate virtual environment
echo "Activating virtual environment..."
source deepface_env/bin/activate

# Install dependencies
echo "Installing Python dependencies..."
pip install -r requirements.txt

# Create face database directory
mkdir -p face_database

# Set environment variables
export PYTHONPATH="${PYTHONPATH}:$(pwd)"

# Start the server
echo "Starting DeepFace API server on http://localhost:5000"
python deepface_server.py