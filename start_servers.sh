#!/bin/bash

echo "🚀 Starting FacePoint Unifil Application..."

# Kill any existing processes on our ports
echo "📋 Cleaning up existing processes..."
lsof -ti:8000 | xargs kill -9 2>/dev/null || true
lsof -ti:5001 | xargs kill -9 2>/dev/null || true

# Wait a moment for ports to be freed
sleep 2

# Start DeepFace API server in background
echo "🤖 Starting DeepFace API server on http://localhost:5001..."
cd "$(dirname "$0")"
source deepface_env/bin/activate
python deepface_server.py &
DEEPFACE_PID=$!
deactivate

# Wait for DeepFace to start
sleep 5

# Start Laravel server in background
echo "🌐 Starting Laravel server on http://localhost:8000..."
php artisan serve --host=127.0.0.1 --port=8000 &
LARAVEL_PID=$!

echo ""
echo "✅ Both servers are starting up!"
echo "📱 Laravel App: http://localhost:8000"
echo "🤖 DeepFace API: http://localhost:5001"
echo ""
echo "Press Ctrl+C to stop both servers"

# Function to clean up when script is terminated
cleanup() {
    echo ""
    echo "🛑 Stopping servers..."
    kill $DEEPFACE_PID 2>/dev/null || true
    kill $LARAVEL_PID 2>/dev/null || true
    echo "✅ Servers stopped"
    exit 0
}

# Set up trap to catch Ctrl+C
trap cleanup SIGINT SIGTERM

# Wait for background processes
wait