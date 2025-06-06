# API Documentation

## Base URL

All endpoints are prefixed with:

```
/api/todos
```

---

## Endpoints

### 1. Create Todo Item

* **URL**: `/api/todos`
* **Method**: `POST`
* **Request Body**:

```json
{
  "title": "Finish report",
  "assignee": "john",
  "due_date": "2025-06-15",
  "time_tracked": 120,
  "status": "pending",
  "priority": "high"
}
```

* **Response (Success)**:

```json
{
    "status": "success",
    "code": 201,
    "message": "success save todo",
    "data": {
        "todo": {
            "id": 3,
            "title": "Finish report",
            "assignee": "john",
            "due_date": "2025-06-15 00:00:00",
            "status": "pending",
            "priority": "high",
            "time_tracked": 120,
            "updated_at": "2025-06-06T09:45:01.000000Z",
            "created_at": "2025-06-06T09:45:01.000000Z",
        }
    }
}
```

---

### 2. Get Todos (with Filters)

* **URL**: `/api/todos`

* **Method**: `GET`

* **Query Parameters**:

  * `title`
  * `assignee`
  * `start` (UTC format)
  * `end` (UTC format)
  * `status`
  * `priority`
  * `min` (minimum `time_tracked`)
  * `max` (maximum `time_tracked`)
  * `paginate` (optional: `true` or `false`)
  * `page` (set current_page pagination)
  * `per_page` (set number of item on pagination)

* **Response**:

```json
{
    "status": "success",
    "code": 200,
    "message": "success",
    "data": {
        "todos": [
            {
                "id": 4,
                "title": "sleeping",
                "assignee": "dedi",
                "due_date": "2025-06-08 00:00:00",
                "time_tracked": 100,
                "status": "pending",
                "priority": "high",
                "created_at": "2025-06-06T09:46:06.000000Z",
                "updated_at": "2025-06-06T09:46:06.000000Z"
            },
            ...
        ]
    }
}
```

---

### 3. Get Single Todo

* **URL**: `/api/todos/{id}`
* **Method**: `GET`
* **Response**:

```json
{
    "status": "success",
    "code": 200,
    "message": "success get todo",
    "data": {
        "todo": {
            "id": 4,
            "title": "sleeping",
            "assignee": "dedi",
            "due_date": "2025-06-08 00:00:00",
            "time_tracked": 100,
            "status": "pending",
            "priority": "high",
            "created_at": "2025-06-06T09:46:06.000000Z",
            "updated_at": "2025-06-06T09:46:06.000000Z"
        }
    }
}
```

---

### 4. Export Todos to Excel

* **URL**: `/api/todos/export`
* **Method**: `GET`
* **Query Parameters**:

  * `title`
  * `assignee`
  * `start` (UTC format)
  * `end` (UTC format)
  * `status`
  * `priority`
  * `min` (minimum `time_tracked`)
  * `max` (maximum `time_tracked`)

* **Response**:

```json
{
    "status": "success",
    "code": 200,
    "message": "success export",
    "data": {
        "total_row": 3,
        "total_time_tracked": 100,
        "filename": "todo_export_20250606_095014.xlsx",
        "url": "http://127.0.0.1:8000/api/todos/download/todo_export_20250606_095014.xlsx"
    }
}
```

* **Download File**: `GET /api/todos/download/{filename}`

---

### 5. Chart Data Endpoint

* **URL**: `/api/todos/chart`

* **Method**: `GET`

* **Query Parameters**:
  * `type` (required): `status`, `priority`, or `assignee`
  * `title`
  * `assignee`
  * `start` (UTC format)
  * `end` (UTC format)
  * `status`
  * `priority`
  * `min` (minimum `time_tracked`)
  * `max` (maximum `time_tracked`)

* **Response**:

```json
{
    "status": "success",
    "code": 200,
    "message": "success get chart",
    "data": {
        "status_summary": {
            "pending": 3,
            "open": 0,
            "in_progress": 0,
            "completed": 0
        },
        "priority_summary": {
            "low": 0,
            "medium": 0,
            "high": 3
        },
        "assignee_summary": {
            "dedi": {
                "total_todos": 3,
                "total_pending_todos": 3,
                "total_timetracked_completed_todos": 0
            }
        }
    }
}
```

---

## Response Format

All responses follow a modified [JSend](https://github.com/omniti-labs/jsend) structure:

* **Success**:

```json
{
  "status": "success",
  "message" : "success message",
  "data": { ... }
}
```

* **Fail**:

```json
{
  "status": "fail",
  "message": "Validation error",
  "data" : {
      "error": { "title": ["The title field is required."] }
  }
}
```

* **Error**:

```json
{
  "status": "error",
  "message": "Something went wrong.",
  "data" : {
      "error": { ... }
  }
}
```
