# Todo List API Specification

This document outlines the specifications for three API endpoints related to managing and reporting on a Todo list.

---

## 1. API: Create Todo Item

### Fields

| Field           | Type     | Required | Description / Notes                         |
|-----------------|----------|----------|---------------------------------------------|
| `title`         | string   | ✅ Yes    | Title of the todo item                      |
| `assignee`      | string   | ❌ No     | Person assigned to the todo                 |
| `due_date`      | date     | ✅ Yes    | Deadline for the todo (must not be in past) |
| `time_tracked`  | numeric  | ❌ No     | Time spent (default: `0`)                   |
| `status`        | enum     | ❌ No     | Status: `pending`, `open`, `in_progress`, `completed` (default: `pending`) |
| `priority`      | enum     | ✅ Yes    | Priority: `low`, `medium`, `high`           |

### Validation Rules
- `due_date` must be today or a future date.
- If `status` is not provided, it defaults to `pending`.

### Expected Result
- **Postman Collection demonstrating:**
  - A POST request with valid body structure.
  - Response showing the created Todo object.

---

## 2. API: Get Todo List to Generate Excel Report

### Excel Columns
- `title`
- `assignee`
- `due_date`
- `time_tracked`
- `status`
- `priority`

### Summary Row
- Total number of todos
- Total time tracked across all todos

### Filtering Support

| Field          | Filter Type                | Example Format                             |
|----------------|----------------------------|--------------------------------------------|
| `title`        | Partial match              | `?title=task`                              |
| `assignee`     | Multiple strings (CSV)     | `?assignee=John,Doe`                       |
| `due_date`     | Date range                 | `?start=2023-01-01&end=2023-12-31`        |
| `time_tracked` | Numeric range              | `?min=5&max=10`                            |
| `status`       | Multiple strings (CSV)     | `?status=pending,in_progress`              |
| `priority`     | Multiple strings (CSV)     | `?priority=low,high`                       |

### Expected Result
- **Postman Collection demonstrating:**
  - GET request with filtering query parameters.
  - Successful download of an Excel file containing filtered data and summary row.

---

## 3. API: Get Todo List to Provide Chart Data

### Endpoints

#### 1. Status Summary
- **Endpoint:** `GET /chart?type=status`
- **Response:**
```json
{
  "status_summary": {
    "pending": 123456,
    "open": 123456,
    "in_progress": 123456,
    "completed": 123456
  }
}
```

#### 2. Priority Summary
- **Endpoint:** `GET /chart?type=priority`
- **Response:**
```json
{
  "priority_summary": {
    "low": 123456,
    "medium": 123456,
    "high": 123456
  }
}
```

#### 3. Assignee Summary
- **Endpoint:** `GET /chart?type=assignee`
- **Response:**
```json
{
  "assignee_summary": {
    "John": {
      "total_todos": 123456,
      "total_pending_todos": 123456,
      "total_timetracked_completed_todos": 123456
    },
    "Doe": {
      "total_todos": 123456,
      "total_pending_todos": 123456,
      "total_timetracked_completed_todos": 123456
    }
  }
}
```
### Expected Result
- **Postman Collection demonstrating:**
  - GET requests using type parameter (status, priority, assignee)
  - JSON responses containing aggregated chart-ready data.
