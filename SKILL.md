---
name: book-it-api
description: API integration for the Book-it Appointment System, allowing agents to check availability and book meetings.
---

# Book-it API Skill

This skill provides access to the Book-it meeting scheduler system.
Base URL: `http://book-it.test/api/v1` (or local equivalent)
Authentication: Bearer Token (Laravel Sanctum)

## Endpoints

### 1. Get Availability
- **Method:** GET
- **Path:** `/availability`
- **Description:** Returns available booking slots.
- **Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "team_id": 1,
      "type": "recurring",
      "day_of_week": 1,
      "start_time": "09:00:00",
      "end_time": "17:00:00",
      "is_available": true
    }
  ]
}
```

### 2. Book a Slot
- **Method:** POST
- **Path:** `/book`
- **Description:** Books an appointment.
- **Payload:**
```json
{
  "team_id": 1,
  "user_id": 1,
  "guest_name": "John Doe",
  "guest_email": "john@example.com",
  "guest_timezone": "America/New_York",
  "start_time": "2026-08-15 10:00:00",
  "end_time": "2026-08-15 11:00:00",
  "lead_data": {
    "question_1": "answer"
  }
}
```
- **Response:**
```json
{
  "status": "success",
  "message": "Booking created successfully.",
  "data": {
    "id": 1,
    "team_id": 1,
    "user_id": 1,
    "guest_name": "John Doe",
    "status": "confirmed"
  }
}
```
